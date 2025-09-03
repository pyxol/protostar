<?php
	namespace Protostar\Database;
	
	use Exception;
	use PDO;
	use PDOException;
	use PDOStatement;
	
	use Protostar\Database\Database;
	use Protostar\Database\Exception\ConnectionFailedException;
	
	class DatabaseHandler {
		/**
		 * The driver name of this handler
		 * @var string
		 */
		protected string $driverName = 'mariadb';
		
		/**
		 * Configuration settings for the database connection
		 * @var array
		 */
		protected array $config = [];
		
		protected string $dbName = 'database';
		protected string $dbHost = 'localhost';
		protected string $dbUser = 'user';
		protected string $dbPass = 'password';
		
		protected int $dbPort = 3306;
		protected string $dbCharset = 'utf8mb4';
		
		protected ?PDO $connection = null;
		
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
			
			$this->dbHost = $this->config['host'] ?? $this->dbHost;
			$this->dbPort = intval($this->config['port'] ?? 3306);
			$this->dbUser = $this->config['user'] ?? $this->dbUser;
			$this->dbPass = $this->config['password'] ?? $this->dbPass;
			$this->dbName = $this->config['database'] ?? $this->dbName;
			$this->dbCharset = $this->config['charset'] ?? 'utf8mb4';
		}
		
		/**
		 * Get the name of this driver
		 * @return string
		 */
		public function getDriverName(): string {
			return $this->driverName;
		}
		
		/**
		 * Generate the Data Source Name (DSN) for the PDO connection
		 * @return string
		 */
		protected function generateDSN(): string {
			throw new Exception("Method 'generateDSN' must be implemented in a subclass.");
		}
		
		/**
		 * Connect to the MariaDB database using PDO.
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
				// create a new PDO connection
				$this->connection = new PDO(
					$this->generateDSN(),
					$this->dbUser,
					$this->dbPass,
					[
						PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
						PDO::ATTR_EMULATE_PREPARES => false,
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '". $this->dbCharset ."'",
					]
				);
				
				//$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				return $this;
			} catch(PDOException $e) {
				throw new ConnectionFailedException("Failed to connect to the database: " . $e->getMessage(), $e->getCode());
			} catch(Exception $e) {
				throw new ConnectionFailedException("Exception thrown while trying to connect to the database: " . $e->getMessage(), $e->getCode());
			}
		}
		
		/**
		 * Get the current connection instance
		 * @return \PDO|null Returns the PDO connection instance or null if not connected
		 */
		public function getConnection(): ?PDO {
			$this->connect();
			
			return $this->connection;
		}
		
		/**
		 * Escape a string for use in SQL queries
		 * @param array|string $value The string to escape
		 * @return array|string
		 */
		public function escape(array|string $value): array|string {
			// Use the esc_sql method to escape the value
			return $this->esc_sql($value);
		}
		
		/**
		 * Escape a string for use in SQL queries
		 * @param array|string $value The string to escape
		 * @return array|string The escaped string or an array of escaped strings if an array was provided
		 */
		public function esc_sql(array|string $value): string|array {
			if(is_array($value)) {
				return array_map([$this, 'esc_sql'], $value);
			}
			
			$escaped_value = $this->getConnection()?->quote($value) ?? false;
			
			if(false === $escaped_value) {
				$escaped_value = addslashes($value);
			}
			
			return $escaped_value;
		}
		
		/**
		 * Execute a SQL query. If the query is an INSERT, it returns the last inserted ID.
		 * If the query is a SELECT, it returns the result set.
		 * If the query fails, it returns false
		 * @param string $query The SQL query to execute
		 * @return int|bool Returns the last inserted ID for INSERT queries, the number of affected rows for other queries, or false on failure.
		 */
		public function query(string $query): int|bool {
			try {
				$result = $this->getConnection()?->exec($query) ?? false;
				
				if(false === $result) {
					throw new Exception("Query failed to run");
				}
				
				if(preg_match("#^\s*INSERT#si", $query)) {
					return $this->connection->lastInsertId();
				}
				
				return $result;
			} catch(PDOException $e) {
				if(config('database.show_errors', false)) {
					die("<pre>". print_r([
						'error' => $this->connection?->errorInfo() ?? [],
						'query' => $query,
						'error_message' => $e->getMessage(),
						'error_code' => $e->getCode(),
						'error_trace' => $e->getTrace(),
					], true) ."</pre>");
				}
				
				return false;
			} catch(Exception $e) {
				throw $e;
			}
		}
		
		/**
		 * Get rows from the database based on a query
		 * @param string $query The SQL query to execute
		 * @param bool $column_key If set to a string, the rows will be indexed by this column key. If false, the rows will be returned as a simple array
		 * @return array
		 */
		public function get_rows(string $query, string|bool $column_key=false): array {
			try {
				$result = $this->getConnection()?->query($query) ?? false;
				
				if(false === $result) {
					throw new Exception("Query failed to run");
				}
				
				if(!($result instanceof PDOStatement)) {
					throw new Exception("Query did not return a valid PDOStatement");
				}
			} catch(Exception $e) {
				return [];
			}
			
			$rows = [];
			
			if($result->rowCount()) {
				while($row = $result->fetch(PDO::FETCH_ASSOC)) {
					if((false !== $column_key) && isset($row[ $column_key ])) {
						$rows[ $row[ $column_key ] ] = $row;
					} else {
						$rows[] = $row;
					}
				}
			}
			
			return $rows;
		}
		
		/**
		 * Get a single column from the result set of a query
		 * @param string $query The SQL query to execute
		 * @param string|null $column_key The column key to retrieve. If null, the first column of each row will be returned.
		 * @return array
		 */
		public function get_col(string $query, string|null $column_key=null): array {
			try {
				$result = $this->getConnection()?->query($query) ?? false;
				
				if((false === $result) || !($result instanceof PDOStatement)) {
					return [];
				}
				
				if(!$result->rowCount()) {
					return [];
				}
				
				$rows = [];
				
				while($row = $result->fetch(PDO::FETCH_ASSOC)) {
					if(isset($row[ $column_key ])) {
						$rows[] = $row[ $column_key ];
					} else {
						$rows[] = array_shift($row);
					}
				}
				
				return $rows;
			} catch(Exception $e) {
				return [];
			}
		}
		
		/**
		 * Get a column from the result set of a query, indexed by an associative key.
		 * @param string $query The SQL query to execute.
		 * @param string|null $assoc_key The column key to use as the associative key for the returned array. If not specified, the first column of each row will be used.
		 * @param string|null $column_key The column key to use as the value for the associative array. If not specified, the first column of each row will be used.
		 * @return array
		 */
		public function get_col_assoc(
			string $query,
			string $assoc_key=null,
			string|null $column_key=null
		): array {
			try {
				$result = $this->getConnection()?->query($query) ?? false;
				
				if((false === $result) || !($result instanceof PDOStatement)) {
					return [];
				}
				
				if(!$result->rowCount()) {
					return [];
				}
				
				$rows = [];
				
				$assoc_col = null;
				$value_col = null;
				
				while($row = $result->fetch(PDO::FETCH_ASSOC)) {
					// determine key column
					if(is_null($assoc_col)) {
						if(isset($row[ $assoc_key ]) && ($assoc_key !== $column_key)) {
							$assoc_col = $assoc_key;
						} else {
							//$assoc_possibilities = array_keys($row);
							//$assoc_col = array_shift($assoc_possibilities);
							$assoc_col = false;
						}
					}
					
					// determine value column
					if(is_null($value_col)) {
						if(isset($row[ $column_key ])) {
							$value_col = $column_key;
						} else {
							$assoc_possibilities = array_keys($row);
							$value_col = array_pop($assoc_possibilities);
						}
					}
					
					// assign rows
					if(false !== $assoc_col) {
						$rows[ $row[ $assoc_col ] ] = $row[ $value_col ];
					} else {
						$rows[] = $row[ $value_col ];
					}
				}
				
				return $rows;
			} catch(Exception $e) {
				return [];
			}
		}
		
		/**
		 * Get a single assoc row from the result set of a query
		 * @param string $query The SQL query to execute
		 * @return array|false
		 */
		public function get_row(string $query): array|false {
			try {
				$result = $this->getConnection()?->query($query) ?? false;
				
				if((false === $result) || !($result instanceof PDOStatement)) {
					return false;
				}
				
				if(!$result->rowCount()) {
					return false;
				}
				
				return $result->fetch(PDO::FETCH_ASSOC);
			} catch(Exception $e) {
				return false;
			}
		}
		
		/**
		 * Get a single variable from the first row of the result set.
		 * @param string $query The SQL query to execute.
		 * @param string|bool $column The column name to retrieve. If false, the first column of the first row will be returned.
		 * @return mixed Returns the value of the specified column from the first row, or false if no rows are returned.
		 * @throws \Exception If the query fails to execute.
		 * @throws \Exception If an unexpected error occurs while fetching the data.
		 */
		public function get_var(string $query, string|bool $column=false): mixed {
			if(false === ($row = $this->get_row($query))) {
				return false;
			}
			
			if(false !== $column) {
				return $row[ $column ] ?? false;
			}
			
			return array_shift($row);
		}
	}