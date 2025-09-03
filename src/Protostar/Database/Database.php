<?php
	namespace Protostar\Database;
	
	use Exception;
	
	use Protostar\Database\DatabaseHandler;
	use Protostar\Database\MariaDBHandler;
	
	class Database {
		const DEFAULT_DRIVER = 'mariadb';
		
		/**
		 * The default database handler class to use.
		 * @var array<string, \Protostar\Database\DatabaseHandler>
		 */
		protected static array $handlers = [];
		
		/**
		 * Get the default database connection name
		 * @return string
		 */
		public static function getDefaultConnectionName(): string {
			return \config('database.default', 'mariadb');
		}
		
		/**
		 * Get the default database handler.
		 * @return \Protostar\Database\DatabaseHandler
		 */
		public static function getDefaultHandler(): DatabaseHandler {
			$default = self::getDefaultConnectionName();
			
			return self::getHandler($default);
		}
		
		/**
		 * Get a specific database handler by name.
		 * @param string $connection_name The name of the database connection to retrieve
		 * @return \Protostar\Database\DatabaseHandler
		 */
		public static function getHandler(string $connection_name): DatabaseHandler {
			return self::$handlers[ $connection_name ] ??= self::makeHandler($connection_name);
		}
		
		/**
		 * Get the driver name for a specific database handler
		 * @param string $connection_name The name of the database connection
		 * @return string
		 */
		public static function getHandlerDriver(string $connection_name): string {
			return \config('database.connections.'. $connection_name .'.driver', self::DEFAULT_DRIVER);
		}
		
		/**
		 * Get the configuration for a specific database handler.
		 * @param string $connection_name The name of the database connection
		 * @return array
		 */
		public static function getHandlerConfig(string $connection_name): array {
			return \config('database.connections.'. $connection_name, []);
		}
		
		/**
		 * Create a new database handler instance.
		 * @param string $connection_name The name of the database connection to create a handler for
		 * @return \Protostar\Database\DatabaseHandler
		 * @throws Exception If the database driver for the connection is not implemented
		 */
		protected static function makeHandler(string $connection_name): DatabaseHandler {
			switch(self::getHandlerDriver($connection_name)) {
				case 'mariadb':
					return new MariaDBHandler(
						self::getHandlerConfig($connection_name)
					);
				break;
				
				default:
					throw new Exception("Database driver for connection '$connection_name' is not implemented.");
			}
		}
	}