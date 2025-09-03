<?php
	namespace Protostar\Model;
	
	use ArrayAccess;
	use ReflectionClass;
	use InvalidArgumentException;
	
	class Model implements ArrayAccess {
		protected static string $table;
		protected static string $primaryKey = 'id';
		
		protected mixed $identifier;
		protected array|null $_data = null;
		protected bool $_dirty = false;
		
		public function __construct(string|int $id) {
			if($this->_expectsNumericPrimaryKey()) {
				if(!is_numeric($id)) {
					throw new InvalidArgumentException("ID must be numeric");
				}
				
				$this->identifier = (int)$id;
			} else {
				if(!is_string($id)) {
					throw new InvalidArgumentException("ID must be a string");
				}
				
				$this->identifier = $id;
			}
			
			$this->_fetchRecord();
		}
		
		
		protected function _expectsNumericPrimaryKey(): bool {
			return static::$primaryKey === 'id' || in_array(static::$primaryKey, ['id', 'ID', 'Id'], true);
		}
		
		protected function _tableName(): string {
			if(!isset(static::$table)) {
				static::$table = strtolower((new ReflectionClass($this))->getShortName());
			}
			
			return static::$table;
		}
		
		protected function _fetchRecord(bool $force=false): void {
			// get the record from the database
			if(null !== $this->_data && !$force) {
				return;
			}
			
			$this->_data = db()->get_row("
				SELECT
					*
				FROM `". $this->_tableName() ."`
				WHERE
					`". db()->escape(static::$primaryKey) ."` = '". db()->escape($this->identifier) ."'
				LIMIT 1
			");
		}
		
		
		public function __get(string $name) {
			// convert the property name to CamelCase if it exists
			$camelCaseName = str_replace('_', '', ucwords($name, '_'));
			
			// if 'get$CamelCaseAttribute' method exists, call it
			$attribute_method = 'get' . $camelCaseName .'Attribute';
			
			if(method_exists($this, $attribute_method)) {
				return $this->$attribute_method();
			}
			
			if(array_key_exists($name, $this->_data)) {
				return $this->_data[ $name ];
			} elseif(array_key_exists($camelCaseName, $this->_data)) {
				return $this->_data[ $camelCaseName ];
			}
			
			throw new InvalidArgumentException("Property '$name' does not exist on " . static::class);
		}
		
		public function __set(string $name, mixed $value): void {
			// convert the property name to CamelCase if it exists
			$camelCaseName = str_replace('_', '', ucwords($name, '_'));
			
			// if 'set$CamelCaseAttribute' method exists, call it
			$attribute_method = 'set' . $camelCaseName .'Attribute';
			
			if(method_exists($this, $attribute_method)) {
				$this->$attribute_method($value);
				return;
			}
			
			if(array_key_exists($name, $this->_data)) {
				$this->_data[ $name ] = $value;
				
				$this->_dirty = true;   // @TODO method called later to save to database
				
				return;
			}
			
			throw new InvalidArgumentException("Property '$name' does not exist on " . static::class);
		}
		
		public function offsetExists(mixed $offset): bool {
			return isset($this->_data[$offset]);
		}
		
		public function offsetGet(mixed $offset): mixed {
			if(isset($this->_data[$offset])) {
				return $this->_data[$offset];
			}
			
			throw new InvalidArgumentException("Offset '$offset' does not exist on " . static::class);
		}
		
		public function offsetSet(mixed $offset, mixed $value): void {
			if($offset === null) {
				throw new InvalidArgumentException("Offset cannot be null");
			}
			
			$this->_data[$offset] = $value;
			$this->_dirty = true;   // @TODO method called later to save to database
		}
		
		public function offsetUnset(mixed $offset): void {
			if(isset($this->_data[$offset])) {
				unset($this->_data[$offset]);
				$this->_dirty = true;   // @TODO method called later to save to database
			} else {
				throw new InvalidArgumentException("Offset '$offset' does not exist on " . static::class);
			}
		}
	}