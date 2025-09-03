<?php
	namespace Protostar\Database;
	
	use Exception;
	
	use Protostar\Database\DatabaseHandler;
	use Protostar\Database\Exception\ConnectionFailedException;
	
	/**
	 * Handles the connection to a MongoDB database using the MongoDB extension.
	 * @see https://www.php.net/manual/en/book.mongodb.php
	 */
	class MongoDBHandler extends DatabaseHandler {
		/**
		 * The driver name of this handler
		 * @var string
		 */
		protected string $driverName = 'mongodb';
		
		/**
		 * Generate the Data Source Name (DSN) for the MongoDB connection.
		 * @return string
		 */
		protected function generateDSN(): string {
			return "mongodb://". $this->dbHost .":". $this->dbPort;
		}
		
		/**
		 * Connect to the MariaDB database using the MongoDB PHP extension.
		 * If already connected, this method does nothing.
		 * @return \Protostar\Database\DatabaseHandler Returns the current instance for method chaining.
		 * @throws \Protostar\Database\Exception\ConnectionFailedException If the connection fails.
		 * @throws \Protostar\Database\Exception\ConnectionFailedException If an unexpected error occurs during connection.
		 */
		public function connect(): DatabaseHandler {
			if(null !== $this->connection) {
				// already connected
				return $this;
			}
			
			try {
				// create a new MongoDB connection
				$this->connection = new \Mongo\Driver\Manager(
					$this->generateDSN()
				);
				
				return $this;
			} catch(Exception $e) {
				throw new ConnectionFailedException("Exception thrown while trying to connect to the database: " . $e->getMessage(), $e->getCode());
			}
		}
	}