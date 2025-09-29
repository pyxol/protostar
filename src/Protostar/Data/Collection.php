<?php
	namespace Protostar\Data;
	
	use ArrayAccess;
	use IteratorAggregate;
	use ArrayIterator;
	use InvalidArgumentException;
	
	class Collection implements ArrayAccess, IteratorAggregate {
		/**
		 * The items in the collection
		 * @var array
		 */
		protected array $items = [];
		
		/**
		 * Create a new collection from an array or another collection
		 * @param array $items
		 * @param callable|null $callback Optional callback to modify items as they are added
		 * @return \Protostar\Data\Collection
		 * @throws InvalidArgumentException
		 */
		public static function collect(
			Collection|array $items = [],
			callable $callback = null
		): Collection {
			if($items instanceof Collection) {
				return $items;
			}
			
			if(!\is_array($items)) {
				throw new InvalidArgumentException('Items must be an array or an instance of Collection');
			}
			
			$collection = new self();
			
			foreach($items as $item) {
				if($callback) {
					$item = $callback($item);
				}
				
				$collection->add($item);
			}
			return $collection;
		}
		
		/**
		 * Check if an item exists at the specified offset
		 * @param mixed $offset
		 * @return bool
		 */
		public function offsetExists(mixed $offset): bool {
			return \isset($this->items[$offset]);
		}
		
		/**
		 * Get an item by offset
		 * @param mixed $offset
		 * @return mixed
		 */
		public function offsetGet(mixed $offset): mixed {
			return $this->get($offset);
		}
		
		/**
		 * Set an item at the specified offset
		 * @param mixed $offset
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet(mixed $offset, mixed $value): void {
			if(\is_null($offset)) {
				$this->add($value);
			} else {
				$this->items[ $offset ] = $value;
			}
		}
		
		/**
		 * Unset an item at the specified offset
		 * @param mixed $offset
		 * @return void
		 */
		public function offsetUnset(mixed $offset): void {
			if(\isset($this->items[$offset])) {
				\unset($this->items[$offset]);
				$this->items = array_values($this->items); // re-index the array
			}
		}
		
		/**
		 * Get an iterator for the collection
		 * @return \ArrayIterator
		 */
		public function getIterator(): ArrayIterator {
			return new ArrayIterator($this->items);
		}
		
		/**
		 * Add an item to the collection
		 * @param mixed $item
		 */
		public function add(mixed $item): void {
			$this->items[] = $item;
		}
		
		/**
		 * Get all items in the collection
		 * @return array
		 */
		public function all(): array {
			return $this->items;
		}
		
		/**
		 * Get the number of items in the collection
		 * @return int
		 */
		public function count(): int {
			return count($this->items);
		}
		
		/**
		 * Check if the collection is empty
		 * @return bool
		 */
		public function isEmpty(): bool {
			return empty($this->items);
		}
		
		/**
		 * Get an item by index
		 * @param int $index
		 * @return mixed
		 */
		public function get(int $index): mixed {
			return $this->items[$index] ?? null;
		}
		
		/**
		 * Remove an item by index
		 * @param int $index
		 * @return bool
		 */
		public function remove(int $index): bool {
			if(isset($this->items[$index])) {
				unset($this->items[$index]);
				$this->items = array_values($this->items); // re-index the array
				return true;
			}
			return false;
		}
		
		/**
		 * Clear the collection
		 */
		public function clear(): void {
			$this->items = [];
		}
		
		/**
		 * Get the first item in the collection
		 * @return mixed
		 */
		public function first(): mixed {
			return $this->items[0] ?? null;
		}
		
		/**
		 * Get the last item in the collection
		 * @return mixed
		 */
		public function last(): mixed {
			return end($this->items) ?: null;
		}
		
		/**
		 * Execute a callback for each item in the collection
		 * @param callable $callback
		 * @return void
		 */
		public function each(callable $callback): void {
			foreach($this->items as $item) {
				$callback($item);
			}
		}
		
		/**
		 * Filter the collection by a callback
		 * @param callable $callback
		 * @return \Protostar\Data\Collection
		 */
		public function filter(callable $callback): Collection {
			$filtered = new Collection();
			
			foreach($this->items as $item) {
				if($callback($item)) {
					$filtered->add($item);
				}
			}
			
			return $filtered;
		}
		
		/**
		 * Map the collection to a new collection using a callback
		 * @param callable $callback
		 * @return \Protostar\Data\Collection
		 */
		public function map(callable $callback): Collection {
			$mapped = new Collection();
			
			foreach($this->items as $item) {
				$mapped->add($callback($item));
			}
			
			return $mapped;
		}
		
		/**
		 * Sort the collection using a callback
		 * @param callable $callback
		 */
		public function sort(callable $callback): Collection {
			usort($this->items, $callback);
			
			return $this;
		}
		
		/**
		 * Check if the collection contains an item
		 * @param mixed $item
		 * @return bool
		 */
		public function contains(mixed $item): bool {
			return in_array($item, $this->items, true);
		}
		
		/**
		 * Get the items as an array
		 * @return array
		 */
		public function toArray(): array {
			return $this->items;
		}
		
		/**
		 * Get the items as a JSON string
		 * @return string
		 */
		public function toJson(bool $pretty=false): string {
			return \json_encode($this->items, $pretty ? JSON_PRETTY_PRINT : 0);
		}
		
		/**
		 * Get the items as a string
		 * @return string
		 */
		public function __toString(): string {
			return \implode(', ', \array_map(function($item) {
				return (string)$item;
			}, $this->items));
		}
		
		/**
		 * Filter out the items that are in the provided array
		 * @param array $values
		 * @return \Protostar\Data\Collection
		 */
		public function except(mixed $values): Collection {
			if(\is_callable($values)) {
				return $this->filter($values);
			} elseif($values instanceof Collection) {
				$values = $values->toArray();
			} elseif(!\is_array($values) && !$values instanceof Collection) {
				$values = [$values];
			}
			
			$filtered = new Collection();
			
			foreach($this->items as $item) {
				if(in_array($item, $values, true)) {
					continue; // Skip items that are in the except list
				}
				
				$filtered->add($item);
			}
			
			return $filtered;
		}
		
		/**
		 * Limit the collection to a specified number of items
		 * @param int $limit
		 * @param int $offset
		 * @return \Protostar\Data\Collection
		 */
		public function limit(int $limit, int $offset=0): Collection {
			return Collection::collect(
				\array_slice(
					$this->items,
					$offset,
					$limit
				)
			);
		}
	}