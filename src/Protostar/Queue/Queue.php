<?php
	namespace Protostar\Queue;
	
	use Redis;
	use Exception;
	
	use Protostar\Queue\Job;
	use Protostar\System\Log;
	
	class Queue {
		/**
		 * The Redis connection instance.
		 * @var Redis|null
		 */
		protected static Redis|null $redis_con = null;
		
		/**
		 * The version (timestamp) of the worker that is currently running.
		 * @var int|null
		 */
		protected static int|null $worker_version = null;
		
		/**
		 * Get a connection to the Redis server
		 * @return \Redis The Redis connection
		 */
		public static function connection(): Redis {
			return static::$redis_con ??= static::createConnection();
		}
		
		/**
		 * Create a new Redis connection
		 * @return Redis
		 * @throws \Exception if the Redis extension is not loaded, or if the connection fails
		 * @throws \Exception if REDIS_HOST or REDIS_PORT is not defined
		 * @throws \Exception if the connection to the Redis server fails
		 */
		protected static function createConnection(): Redis {
			try {
				if(!extension_loaded('redis')) {
					throw new Exception('Redis extension not loaded');
				}
				
				
				$config = \config('queue.connections.default', []);
				
				if(empty($config['host'])) {
					throw new Exception('Queue connection host not set');
				}
				
				if(empty($config['port'])) {
					throw new Exception('Queue connection port not set');
				}
				
				// connect
				$connection = new Redis();
				$connection->connect(
					$config['host'],
					$config['port'],
					(isset($config['timeout']) ? (float)$config['timeout'] : 2.5)
				);
				
				// check if the connection was successful
				if(!$connection->isConnected()) {
					throw new Exception('Failed to connect to Redis server');
				}
				
				// if a password is defined, authenticate
				if(isset($config['password']) && ('' !== $config['password'])) {
					$connection->auth([ $config['password'] ]);
				}
				
				return $connection;
			} catch (\Exception $e) {
				throw new Exception('Redis connection failed: '. $e->getMessage());
			}
		}
		
		/**
		 * Prepare data to be put into queue
		 * @param mixed $data
		 * @return string
		 */
		public static function prepareInputData(mixed $data): string {
			if($data instanceof Job) {
				$data = Job::prepareForQueue($data);
			}
			
			return json_encode($data) ?: throw new Exception('Failed to encode data to JSON');
		}
		
		/**
		 * Prepare data from the queue for output
		 * @param string $queue_name The name of the queue
		 * @param string $data The data from the queue
		 * @return mixed
		 */
		public static function prepareOutputData(string $queue_name, string $data): mixed {
			try {
				$job = new Job($queue_name, $data);
				
				if(!($job instanceof Job)) {
					throw new Exception('Failed to decode data from JSON');
				}
				
				return $job;
			} catch(\Exception $e) {
				// do nothing
				print "[Queue Warning]: ". $e->getMessage() ."\n";
			}
			
			return json_decode($data, true) ?: throw new Exception('Failed to decode JSON data');
		}
		
		/**
		 * Get the default queue name from config if available. If not, throws an exception
		 * @return string
		 * @throws \Exception if the default queue name is not set in configuration
		 */
		public static function defaultQueueName(): string {
			$name = \config('queue.connections.default.queue_name', '');
			
			if('' === $name) {
				throw new Exception('Default queue name not set in configuration');
			}
			
			return $name;
		}
		
		/**
		 * Get the name of the queue
		 * @param string|null $queue_name The name of the queue
		 * @return string
		 */
		public static function getQueueName(string|null $queue_name=null): string {
			if(is_null($queue_name) || ('' === $queue_name)) {
				$queue_name = static::defaultQueueName();
			}
			
			return $queue_name;
		}
		
		/**
		 * Get the name of the delayed queue
		 * @param string|null $queue_name The name of the queue
		 * @return string
		 */
		public static function getDelayedQueueName(string|null $queue_name): string {
			return ($queue_name ?? static::defaultQueueName()) .':delayed';
		}
		
		/**
		 * Get the key for where the version of the current queue worker is stored
		 * @param string|null $queue_name The name of the queue
		 * @return string
		 */
		public static function getQueueVersionName(string|null $queue_name): string {
			return ($queue_name ?? static::defaultQueueName()) .':version';
		}
		
		/**
		 * Add an item to the queue
		 * @param string|null $queue_name The name of the queue
		 * @param mixed $data The item to add
		 * @param int $delay The delay in seconds before the item can be processed
		 * @return void
		 */
		public static function add(
			string|null $queue_name=null,
			mixed $data=null,
			int $delay=0,
			bool $priority=false
		): void {
			if(is_null($queue_name)) {
				$queue_name = static::defaultQueueName();
			}
			
			if(null === $data) {
				throw new Exception('No data provided to add to queue');
			}
			
			if($delay > 0) {
				// Add to delayed queue
				static::connection()->zAdd(
					static::getDelayedQueueName($queue_name),
					time() + $delay,
					static::prepareInputData($data)
				);
				
				return;
			}
			
			if($priority) {
				// add to the front of the queue
				static::connection()->lPush(
					$queue_name,
					static::prepareInputData($data)
				);
			} else {
				// add to the end of the queue
				static::connection()->rPush(
					$queue_name,
					static::prepareInputData($data)
				);
			}
		}
		
		/**
		 * Get the next item from the queue
		 * @param string|null $queue_name The name of the queue
		 * @return mixed The item from the queue
		 * @throws \Exception if a queue name is not set and cannot be derived
		 * @throws \Protostar\Queue\Exceptions\RestartWorkerException if the worker needs to be restarted
		 */
		public static function get(string|null $queue_name=null): mixed {
			$queue_name = static::getQueueName($queue_name);
			
			static::checkWorkerRestart($queue_name);
			
			static::migrateDueDelayedJobs($queue_name);
			
			$data = static::connection()->blPop($queue_name, 3);
			
			if(isset($data[1])) {
				return static::prepareOutputData($queue_name, $data[1]);
			}
			
			return null;
		}
		
		/**
		 * Check if the worker needs to be restarted. Only intended to be called internally
		 * @return void
		 * @throws \Protostar\Queue\Exceptions\RestartWorkerException if the worker needs to be restarted
		 */
		protected static function checkWorkerRestart(string|null $queue_name=null): void {
			// get the current worker version from cache
			$latest_version = static::getWorkerVersion($queue_name);
			
			if(null === $latest_version) {
				if(null === static::$worker_version) {
					// if the worker version is not set, set it to the current timestamp
					static::$worker_version = time();
				}
				
				// if the latest version is not set, set the cache to the current worker version
				static::setWorkerVersion($queue_name, static::$worker_version);
				
				//print "[Queue Debug]: Worker version not set, setting to current timestamp: ". static::$worker_version ."\n";
				
				return;
			}
			
			if(null === static::$worker_version) {
				// if the worker version is not set, set it to the cached version
				static::$worker_version = $latest_version;
				
				return;
			}
			
			// if we're using an older worker version, throw a restart worker exception
			if(static::$worker_version < $latest_version) {
				Log::info("[Queue Debug]: Worker version ". static::$worker_version ." is older than cached version ". $latest_version .", restarting worker.");
				
				// if the cached version is greater than the current version, restart the worker
				throw new \Protostar\Queue\Exceptions\RestartWorkerException("Worker needs to be restarted. Current version: ". static::$worker_version .", Latest version: ". $latest_version);
			}
			
			// at this point, do nothing since we're using the correct worker version
		}
		
		/**
		 * Get the worker version from the cache. Returns null if not set
		 * @param string|null $queue_name
		 * @return int|null
		 */
		protected static function getWorkerVersion(string|null $queue_name=null): int|null {
			$version = static::connection()->get( static::getQueueVersionName($queue_name) );
			
			if((false === $version) || empty($version) || !is_numeric($version)) {
				return null;
			}
			
			return (int)$version;
		}
		
		/**
		 * Set the worker version in the cache
		 * @param int|null $version The version of the worker to set, defaults to the current timestamp if not provided
		 * @param string|null $queue_name The name of the queue to set the version for. If not set, uses the default queue name
		 * @return void
		 */
		protected static function setWorkerVersion(string|null $queue_name=null, int|null $version=null): void {
			if((null === $version) || !is_numeric($version)) {
				// if the version is not set, set it to the current timestamp
				$version = time();
			}
			
			static::connection()->set(static::getQueueVersionName($queue_name), $version);
		}
		
		/**
		 * Restart the workers by updating the worker version
		 * @param string|null $queue_name
		 * @return void
		 */
		public static function restartWorkers(string|null $queue_name=null): void {
			$queue_name ??= static::defaultQueueName();
			
			Log::info("Restarting workers for queue: ". $queue_name ." with version: ". time());
			
			// trigger workers to restart by updating the worker version
			static::setWorkerVersion($queue_name, time());
		}
		
		/**
		 * Migrate due delayed jobs to the main queue
		 * @param string $queue_name The name of the queue
		 * @param int $migrate_limit The maximum number of jobs to migrate
		 * @return void
		 */
		protected static function migrateDueDelayedJobs(string $queue_name, int $migrate_limit=10): void {
			$redis = static::connection();
			$delayed_key = static::getDelayedQueueName($queue_name);
			$now = time();
			
			// Get up to $migrate_limit due jobs
			$jobs = $redis->zRangeByScore(
				$delayed_key,
				'-inf',
				$now,
				[
					'limit' => [0, $migrate_limit],
				]
			);
			
			foreach($jobs as $job) {
				// Atomic removal & push using Lua script (prevents race between zrem and rpush)
				$script = <<<LUA
					if redis.call("zrem", KEYS[1], ARGV[1]) == 1 then
						return redis.call("rpush", KEYS[2], ARGV[1])
					end
					return 0
				LUA;
				
				$redis->eval($script, [
					$delayed_key,
					$queue_name,
					$job,
				], 2);
			}
		}
		
		/**
		 * Empty all items from the queue (and it's delayed queue)
		 * @param string|null $queue_name The name of the queue
		 * @return void
		 * @throws \Exception if a queue name is not set and cannot be derived
		 */
		public static function emptyQueue(string|null $queue_name=null): void {
			static::connection()->del( static::getQueueName($queue_name) );
		}
	}