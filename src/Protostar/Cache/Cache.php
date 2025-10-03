<?php
	namespace Protostar\Cache;
	
	use RuntimeException;
	
	use Protostar\Cache\CacheHandler;
	use Protostar\Cache\RedisHandler;
	use Protostar\Cache\MemcachedHandler;
	
	class Cache {
		const DEFAULT_DRIVER = 'redis';
		
		/**
		 * The default cache handler class to use.
		 * @var array<string, \Protostar\Cache\CacheHandler>
		 */
		protected static array $handlers = [];
		
		/**
		 * Get the default cache connection name
		 * @return string
		 */
		public static function getDefaultConnectionName(): string {
			return \config('cache.default', 'redis');
		}
		
		/**
		 * Get the default cache handler.
		 * @return \Protostar\Cache\CacheHandler
		 */
		public static function getDefaultHandler(): CacheHandler {
			$default = self::getDefaultConnectionName();
			
			return self::getHandler($default);
		}
		
		/**
		 * Get a specific cache handler by name.
		 * @param string $connection_name The name of the cache connection to retrieve
		 * @return \Protostar\Cache\CacheHandler
		 */
		public static function getHandler(string $connection_name): CacheHandler {
			return self::$handlers[ $connection_name ] ??= self::makeHandler($connection_name);
		}
		
		/**
		 * Get the driver name for a specific cache handler
		 * @param string $connection_name The name of the cache connection
		 * @return string
		 */
		public static function getHandlerDriver(string $connection_name): string {
			return \config('cache.connections.'. $connection_name .'.driver', self::DEFAULT_DRIVER);
		}
		
		/**
		 * Get the configuration for a specific cache handler.
		 * @param string $connection_name The name of the cache connection
		 * @return array
		 */
		public static function getHandlerConfig(string $connection_name): array {
			return \config('cache.connections.'. $connection_name, []);
		}
		
		/**
		 * Create a new cache handler instance.
		 * @param string $connection_name The name of the cache connection to create a handler for
		 * @return CacheHandler
		 * @throws RuntimeException If the cache driver for the connection is not implemented
		 */
		protected static function makeHandler(string $connection_name): CacheHandler {
			switch(self::getHandlerDriver($connection_name)) {
				case 'redis':
					return new RedisHandler(
						self::getHandlerConfig($connection_name)
					);
				break;
				
				case 'memcached':
					return new MemcachedHandler(
						self::getHandlerConfig($connection_name)
					);
				break;
				
				default:
					throw new RuntimeException("Cache driver for connection '$connection_name' is not implemented.");
			}
		}
	}