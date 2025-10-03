<?php
	namespace Protostar\Cache;
	
	use Exception;
	
	use Protostar\Cache\CacheHandler;
	use Protostar\Cache\Exception\ConnectionFailedException;
	
	/**
	 * Handles the connection to a Redis data store using the Redis PHP extension.
	 */
	class RedisHandler extends CacheHandler {
		/**
		 * The driver name of this handler
		 * @var string
		 */
		protected string $driverName = 'redis';
		
		
		protected function setKeyPrefix(string $prefix): void {
			if('' !== ($prefix = trim($prefix))) {
				// Ensure the prefix ends with a colon for separation in redis keys
				if(':' !== substr($prefix, -1)) {
					$prefix .= ':';
				}
			}
			
			$this->keyPrefix = $prefix;
		}
		
		/**
		 * Generate the connection instance to the Redis data store
		 * @return mixed
		 * @throws \Protostar\Cache\Exception\ConnectionFailedException If the connection fails.
		 * @throws \Exception If the Redis extension is not loaded.
		 * @throws \RuntimeException If the connection cannot be established.
		 */
		protected function generateConnection(): mixed {
			if(!extension_loaded('redis')) {
				throw new Exception('Redis extension not loaded');
			}
			
			try {
				$instance = new \Redis();
				
				$instance->connect(
					$this->connectionHost,
					$this->connectionPort,
					$this->connectionTimeoutSeconds
				);
				
				if(!empty($this->connectionPass)) {
					$instance->auth([$this->connectionPass]);
				}
				
				if('' !== $this->keyPrefix) {
					$instance->setOption(\Redis::OPT_PREFIX, $this->keyPrefix);
				}
				
				return $instance;
			} catch(Exception $e) {
				throw new ConnectionFailedException("Failed to connect to Redis server: ". $e->getMessage(), 0, $e);
			}
		}
	}