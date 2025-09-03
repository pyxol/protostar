<?php
	namespace Protostar\Database;
	
	use Exception;
	use PDO;
	use PDOException;
	use PDOStatement;
	
	use Protostar\Database\DatabaseHandler;
	use Protostar\Database\Exception\ConnectionFailedException;
	
	/**
	 * Handles the connection to a MariaDB database using PDO.
	 */
	class MariaDBHandler extends DatabaseHandler {
		/**
		 * The driver name of this handler
		 * @var string
		 */
		protected string $driverName = 'mariadb';
		
		/**
		 * Generate the Data Source Name (DSN) for the PDO connection.
		 * @return string
		 */
		protected function generateDSN(): string {
			return "mysql:host=". $this->dbHost .";port=". $this->dbPort .";dbname=". $this->dbName .";charset=". $this->dbCharset;
		}
	}