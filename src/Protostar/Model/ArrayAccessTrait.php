<?php
	namespace Protostar\Model;
	
	/**
	 * Trait that provides ArrayAccess functionality for models.
	 * Allows models to be accessed like arrays.
	 * 
	 * @TODO Turn this into 'DataStore' class that the Model uses.
	 */
	trait ArrayAccessTrait {
		/**
		 * The data for the model instance.
		 * This is fetched from the database when the model is instantiated.
		 * @var array
		 */
		protected array $_data = [];
		
		/**
		 * Checks if a property exists.
		 * @param string $name
		 * @return bool
		 */
		public function offsetExists(mixed $offset): bool {
			return isset($this->_data[ $offset ]);
		}
		
		/**
		 * Gets a property by offset.
		 * @param mixed $offset
		 * @return mixed
		 */
		public function offsetGet(mixed $offset): mixed {
			return $this->_data[ $offset ] ?? null;
		}
		
		/**
		 * Sets a property by offset.
		 * @param mixed $offset
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet(mixed $offset, mixed $value): void {
			$this->_data[ $offset ] = $value;
			
			$this->_dirty = true;   // @TODO method called later to save to database
		}
		
		/**
		 * Unsets a property by offset.
		 * @param mixed $offset
		 * @return void
		 */
		public function offsetUnset(mixed $offset): void {
			if(isset($this->_data[ $offset ])) {
				$this->_dirty = true;   // @TODO method called later to save to database
			}
			
			unset($this->_data[ $offset ]);
		}
	}