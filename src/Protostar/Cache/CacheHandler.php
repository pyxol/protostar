<?php
	namespace Protostar\Cache;
	
	use Exception;
	use RuntimeException;
	
	use Protostar\Cache\Cache;
	use Protostar\Cache\Exception\ConnectionFailedException;
	
	class CacheHandler {
		/**
		 * The driver name of this handler
		 * @var string
		 */
		protected string $driverName = 'redis';
		
		/**
		 * Configuration settings for the cache connection
		 * @var array
		 */
		protected array $config = [];
		
		protected string $connectionHost = 'localhost';
		protected int $connectionPort = 6379;
		protected string $connectionPass = '';
		protected float $connectionTimeoutSeconds = 2.5;
		
		/**
		 * The key prefix for cache keys
		 * @var string
		 */
		protected string $keyPrefix = '';
		
		/**
		 * Whether this cache handler is queueable
		 * @var bool
		 */
		protected bool $queueable = false;
		
		/**
		 * The connection instance to the cache data store
		 * @var mixed
		 */
		protected mixed $connection = null;
		
		/**
		 * Constructor for the DatabaseHandler class
		 * @param array $config Configuration settings for the database connection
		 */
		public function __construct(array $config=[]) {
			$this->loadConfig($config);
		}
		
		/**
		 * Load the database configuration into the class
		 * @return void
		 */
		protected function loadConfig($config): void {
			$this->config = $config;
			
			$this->connectionHost = $this->config['host'] ?? $this->connectionHost;
			$this->connectionPort = intval($this->config['port'] ?? $this->connectionPort);
			$this->connectionPass = $this->config['password'] ?? $this->connectionPass;
			
			if(isset($this->config['key_prefix'])) {
				$this->keyPrefix = $this->config['key_prefix'] ?? '';
			}
			
			if(isset($this->config['queueable'])) {
				$this->queueable = !empty($this->config['queueable']);
			}
		}
		
		/**
		 * Generate the connection to the cache data store. Stored in the $connection property.
		 * @return mixed
		 */
		protected function generateConnection(): mixed {
			throw new RuntimeException('Method generateConnection() must be implemented in a subclass.');
		}
		
		/**
		 * Connect to the cache instance. If already connected, this method does nothing.
		 * @return \Protostar\Cache\CacheHandler Returns the current instance for method chaining
		 * @throws \Protostar\Database\Exception\ConnectionFailedException If an unexpected error occurs during connection
		 */
		public function connect(): CacheHandler {
			try {
				// create a new connection
				$this->connection ??= $this->generateConnection();
				
				return $this;
			} catch(Exception $e) {
				throw new ConnectionFailedException("Exception thrown while trying to connect to the database: " . $e->getMessage(), $e->getCode());
			}
		}
		
		/**
		 * Get the current connection instance
		 * @return mixed Returns the raw connection instance or null if not connected
		 */
		public function getConnection(): mixed {
			$this->connect();
			
			return $this->connection;
		}
		
		/**
		 * Get the cache connection instance if the cache can provide queue functionality.
		 * Returns null if the cache is not queueable.
		 * @return mixed
		 */
		public function getQueueConnection(): mixed {
			$this->connect();
			
			return $this->isQueueable() ? $this->connection : null;
		}
		
		/**
		 * Get the name of this driver
		 * @return string
		 */
		public function getDriverName(): string {
			return $this->driverName;
		}
		
		/**
		 * Get the cache key, prefixed with the cache driver's key prefix
		 * @param string $key
		 * @return string
		 */
		public function getCacheKey(string $key): string {
			// Generate a cache key with the prefix
			return $this->keyPrefix . $key;
		}
		
		/**
		 * Check if this cache handler is queueable
		 * @return bool
		 */
		public function isQueueable(): bool {
			return $this->queueable;
		}
	}