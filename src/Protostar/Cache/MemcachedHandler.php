<?php
	namespace Protostar\Cache;
	
	use Exception;
	
	use Protostar\Cache\CacheHandler;
	use Protostar\Cache\Exception\ConnectionFailedException;
	
	/**
	 * Handles the connection to a memcached data store using the memcached PHP extension.
	 */
	class MemcachedHandler extends CacheHandler {
		/**
		 * The driver name of this handler
		 * @var string
		 */
		protected string $driverName = 'memcached';
		
		/**
		 * Generate the connection instance to the memcached data store
		 * @return mixed
		 * @throws \Protostar\Cache\Exception\ConnectionFailedException If the connection fails.
		 * @throws \Exception If the memcached extension is not loaded.
		 * @throws \RuntimeException If the connection cannot be established.
		 */
		protected function generateConnection(): mixed {
			if(!extension_loaded('memcached')) {
				throw new Exception('memcached extension not loaded');
			}
			
			try {
				$instance = new \Memcached();
				
				$instance->addServer(
					$this->connectionHost,
					(int)$this->connectionPort
				);
				
				if('' !== $this->keyPrefix) {
					$instance->setOption(\Memcached::OPT_PREFIX_KEY, $this->keyPrefix);
				}
				
				return $instance;
			} catch(Exception $e) {
				throw new ConnectionFailedException("Failed to connect to Redis server: ". $e->getMessage(), 0, $e);
			}
		}
	}