<?php
	namespace Protostar\Model;
	
	use ArrayAccess;
	use ReflectionClass;
	use InvalidArgumentException;
	
	use Protostar\Model\ArrayAccessTrait;
	
	/**
	 * Base model class for Protostar framework.
	 * Provides basic functionality for database interaction and property access.
	 */
	class Model implements ArrayAccess {
		use ArrayAccessTrait;
		
		/**
		 * The table name for the model.
		 * If not set, it will be derived from the class name.
		 * @var string
		 */
		protected static string $table;
		
		/**
		 * The primary key for the model.
		 * Default is 'id', but can be overridden in subclasses.
		 * @var string
		 */
		protected static string $primaryKey = 'id';
		
		/**
		 * The identifier for the model instance.
		 * Can be a string or an integer, depending on the primary key type.
		 * @var string|int
		 */
		protected mixed $_identifier;
		
		/**
		 * Indicates whether the model instance has unsaved changes.
		 * @var bool
		 */
		protected bool $_dirty = false;
		
		/**
		 * Constructor for the model.
		 * Accepts an identifier (string or integer) to fetch the record from the database.
		 * @param string|int $identifier The identifier for the model instance.
		 * @throws InvalidArgumentException If the ID is not of the expected type.
		 */
		public function __construct(string|int $identifier) {
			if($this->_expectsNumericPrimaryKey()) {
				if(!is_numeric($identifier)) {
					throw new InvalidArgumentException("ID must be numeric");
				}
				
				$this->_identifier = (int)$identifier;
			} else {
				if(!is_string($identifier)) {
					throw new InvalidArgumentException("ID must be a string");
				}
				
				$this->_identifier = $identifier;
			}
			
			$this->_fetchRecord();
		}
		
		/**
		 * Checks if the model expects a numeric primary key.
		 * This is used to determine how the identifier should be treated.
		 * @return bool
		 * @throws InvalidArgumentException If the primary key is not set correctly.
		 * @internal
		 */
		protected function _expectsNumericPrimaryKey(): bool {
			return static::$primaryKey === 'id' || in_array(static::$primaryKey, ['id', 'ID', 'Id'], true);
		}
		
		/**
		 * Returns the table name for the model.
		 * If not set, it will be derived from the class name.
		 * @return string The table name.
		 */
		protected function _tableName(): string {
			return static::$table ??= strtolower((new ReflectionClass($this))->getShortName());
		}
		
		/**
		 * Fetches the record from the database based on the identifier.
		 * If the record is already fetched, it will not fetch it again unless forced.
		 * @return void
		 */
		protected function _fetchRecord(): void {
			// get the record from the database
			$row = db()->get_row("
				SELECT
					*
				FROM `". $this->_tableName() ."`
				WHERE
					`". db()->escape(static::$primaryKey) ."` = '". db()->escape($this->_identifier) ."'
				LIMIT 1
			");
			
			if(is_array($row)) {
				$this->_data = $row;
			} else {
				$this->_data = [];
			}
		}
		
		/**
		 * Magic method to get a property
		 * @param string $name
		 * @return mixed
		 */
		public function __get(string $name): mixed {
			return $this[ $name ] ?? null;
		}
		
		/**
		 * Magic method to set a property
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function __set(string $name, mixed $value): void {
			$this[ $name ] = $value;
		}
	}