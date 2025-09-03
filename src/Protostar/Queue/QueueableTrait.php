<?php
	namespace Protostar\Queue;
	
	use \ReflectionClass;
	use \ReflectionObject;
	
	use Protostar\Queue\Queue;
	use Protostar\Queue\Job;
	
	trait QueueableTrait {
		/**
		 * Is this job 'high priority'? If true, it will be added to the start of the queue
		 * @return bool
		 */
		public function isHighPriority(): bool {
			// Override this method in the job class to specify if the job is high priority
			return false;
		}
		
		/**
		 * The name of the queue to use. Uses the QUEUE_NAME constant if not overridden
		 * @return string
		 */
		public function getQueueName(): string {
			return QUEUE_NAME ?? throw new \Exception('QUEUE_NAME constant not set');
		}
		
		/**
		 * The number of attempts to retry the job if it fails
		 * @return int
		 */
		public function getQueueRetryAttempts(): int {
			return 3;
		}
		
		/**
		 * Dispatch the job to the queue
		 * @return void
		 */
		public function dispatch(bool $immediate = false): void {
			if($immediate) {
				$this->dispatchInstantly();
				
				return;
			}
			
			$className = get_class($this);
			$properties = [];
			
			$ref = new ReflectionClass($this);
			$constructor = $ref->getConstructor();
			
			$constructor_param_names = [];
			
			if($constructor) {
				$params = $constructor->getParameters();
				
				foreach($params as $param) {
					$constructor_param_names[] = $param->getName();
				}
			}
			
			foreach($ref->getProperties() as $prop) {
				if($prop->isStatic()) {
					continue;
				}
				
				if(!in_array($prop->getName(), $constructor_param_names)) {
					continue;
				}
				
				$prop->setAccessible(true);
				
				$properties[ $prop->getName() ] = $prop->getValue($this);
			}
			
			$job_data = [
				'class' => $className,
				'properties' => $properties,
			];
			
			Job::create(
				$this->getQueueName(),
				$job_data,
				0,
				$this->getQueueRetryAttempts(),
				null,
				0,
				$this->isHighPriority()
			);
		}
		
		/**
		 * Dispatch the job immediately, calling handle() directly and bypassing the queue entirely
		 * @return void
		 */
		public function dispatchInstantly() {
			$this->handle();
		}
		
		/**
		 * [Internal Use] Reconstructs the object from the data stored in the queue
		 * @param array $data
		 * @return static
		 */
		public static function reconstruct(array $data): static {
			$className = $data['class'] ?? throw new \Exception('Class name not provided');
			$properties = $data['properties'];
			
			// get constructor parameters
			$ref = new ReflectionClass($className);
			$constructor = $ref->getConstructor();
			
			if($constructor) {
				$params = $constructor->getParameters();
				
				$values = [];
				foreach($params as $param) {
					$name = $param->getName();
					
					if(isset($properties[$name])) {
						$values[] = $properties[$name];
					} else {
						throw new \Exception('Missing parameter: '. $name);
					}
				}
				
				return new $className(...$values);
			}
			
			// If no constructor, just create the object
			// and set the properties
			$obj = new $className();
			
			$ref = new ReflectionObject($obj);
			
			foreach($properties as $name => $value) {
				if($ref->hasProperty($name)) {
					$prop = $ref->getProperty($name);
					$prop->setAccessible(true);
					$prop->setValue($obj, $value);
				}
			}
			
			return $obj;
		}
	}