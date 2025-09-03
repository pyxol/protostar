<?php
	namespace Protostar\Queue;
	
	use Exception;
	
	use Protostar\Queue\Queue;
	
	class Job {
		/**
		 * The cargo of the job
		 * @var mixed
		 */
		protected mixed $cargo = null;
		
		/**
		 * Number of tries the job has attempted
		 * @var int
		 */
		protected int $tries = 0;
		
		/**
		 * Maximum number of tries before the job is considered failed
		 * @var int
		 */
		protected int $max_tries = 3;
		
		/**
		 * Delay in seconds before the job can be retried
		 * @var int
		 */
		protected int $delay = 0;
		
		/**
		 * The timestamp when the job was created or last retried
		 * @var int|null
		 */
		protected int|null $timestamp = null;
		
		/**
		 * Indicates if the job has finished processing
		 * @var bool
		 */
		protected bool $finished = false;
		
		/**
		 * Constructor
		 * @param string|null $queue_name The name of the queue
		 * @param string|null $data_from_queue The raw data from the queue
		 * @throws Exception if invalid data is provided
		 */
		public function __construct(
			protected string|null $queue_name=null,
			mixed $data_from_queue = null
		) {
			if(!is_string($this->queue_name) || empty($this->queue_name)) {
				$this->queue_name = $queue_name ?? throw new Exception('Queue name not provided and no default set');
				
				throw new Exception('Invalid queue name provided');
			}
			
			if(empty($data_from_queue) || !is_string($data_from_queue)) {
				throw new Exception('No data provided for the job');
			}
			
			if(null !== $data_from_queue) {
				$this->processFromQueue($data_from_queue);
			} else {
				// If no data is provided, set default values
				$this->timestamp = time();
			}
		}
		
		/**
		 * Process the job data from the queue
		 * @param mixed $data The raw data from the queue
		 * @return void
		 */
		protected function processFromQueue(mixed $data): void {
			if($this->isDone()) {
				throw new Exception('Job has already finished processing');
			}
			
			if(is_string($data)) {
				$data = @json_decode($data, true);
			}
			
			if(empty($data) || !is_array($data)) {
				throw new Exception('No data provided for the job');
			}
			
			if(null === $data) {
				throw new Exception('Failed to decode job data');
			}
			
			// Set the timestamp for when the job was created or last retried
			if(isset($data['timestamp']) && is_numeric($data['timestamp'])) {
				$this->timestamp = (int)$data['timestamp'];
			} else {
				$this->timestamp = time();
			}
			
			// Add the delay
			if(isset($data['delay']) && is_numeric($data['delay'])) {
				$this->delay = (int)$data['delay'];
			}
			
			if(!isset($data['cargo'])) {
				throw new Exception('No cargo found in job data');
			}
			
			$this->cargo = $data['cargo'];
			
			// Set the current number of tries
			if(isset($data['tries']) && is_numeric($data['tries'])) {
				$this->tries = (int)$data['tries'];
			}
			
			// Set the maximum number of tries
			if(isset($data['max_tries']) && is_numeric($data['max_tries'])) {
				$this->max_tries = (int)$data['max_tries'];
			}
		}
		
		/**
		 * Form the storage format for the job to be put into the queue
		 * @param mixed $cargo The cargo of the job, which can be any data type
		 * @param int $tries The number of tries the job has attempted
		 * @param int $max_tries The maximum number of tries before the job is considered failed
		 * @param int|null $timestamp The timestamp when the job was created or last retried
		 * @return array
		 */
		public static function prepareForQueue(
			mixed $cargo = null,
			int $tries = 0,
			int $max_tries = 3,
			int|null $timestamp = null
		): array {
			return [
				'cargo' => $cargo,
				'tries' => $tries,
				'max_tries' => $max_tries,
				'timestamp' => $timestamp ?? time(),
			];
		}
		
		/**
		 * Create a new job instance and add it to the queue
		 * @param string|null $queue_name The name of the queue
		 * @param mixed $cargo The cargo of the job, which can be any data type
		 * @param int $max_tries The maximum number of tries before the job is considered failed
		 * @param int|null $timestamp The timestamp when the job was created or last retried. Defaults to current time
		 * @param int $delay The delay in seconds before the job can be retried
		 * @param bool $priority Whether the job should be prioritized in the queue
		 * @return bool
		 * @throws Exception if the job cannot be created or added to the queue
		 */
		public static function create(
			string|null $queue_name=null,
			mixed $cargo,
			int $tries=0,
			int $max_tries=3,
			int|null $timestamp=null,
			int $delay=0,
			bool $priority=false
		): bool {
			try {
				Queue::add(
					$queue_name,
					static::prepareForQueue(
						$cargo,
						$tries,
						$max_tries,
						$timestamp
					),
					$delay,
					$priority
				);
				
				return true;
			} catch (Exception $e) {
				throw new Exception('Failed to create job: '. $e->getMessage());
			}
		}
		
		/**
		 * Check if the job can be processed based on the timestamp and delay
		 * @return bool
		 */
		public function canBeProcessed(): bool {
			return ($this->timestamp + $this->delay) <= time();
		}
		
		/**
		 * Check if this instance of the job is done processing
		 * @return bool
		 */
		public function isDone(): bool {
			return $this->finished;
		}
		
		/**
		 * Mark the job as finished
		 * @return void
		 */
		public function done(): void {
			$this->finished = true;
		}
		
		/**
		 * Get the cargo of the job
		 * @return mixed
		 */
		public function getCargo(): mixed {
			return $this->cargo;
		}
		
		/**
		 * Get the number of tries the job has attempted
		 * @return int
		 */
		public function getTries(): int {
			return $this->tries;
		}
		
		/**
		 * Get the maximum number of tries before the job is considered failed
		 * @return int
		 */
		public function getMaxTries(): int {
			return $this->max_tries;
		}
		
		/**
		 * Get the timestamp when the job was created or last retried
		 * @return int
		 */
		public function getTimestamp(): int {
			return $this->timestamp;
		}
		
		/**
		 * Send the job back to the queue to be retried
		 * @param int|null $delay The delay in seconds before the job can be retried. Defaults to the current delay of the job
		 * @return void
		 * @throws Exception if the job has already finished processing or cannot be retried
		 * @throws \Protostar\QueueExceptions\JobMaxAttemptsException if the job has exceeded the maximum number of attempts
		 * @throws Exception if the job cannot be added back to the queue
		 */
		public function retry(int|null $delay=null): void {
			if($this->finished) {
				throw new Exception('Job has already finished processing');
			}
			
			// Increment the number of tries
			$this->tries++;
			
			// Check if the job has exceeded the maximum number of attempts
			if($this->tries > $this->getMaxTries()) {
				throw new \Protostar\Queue\Exception\JobMaxAttemptsException($this->getMaxTries());
			}
			
			// Add the job back to the queue with updated data
			static::create(
				$this->queue_name,
				$this->cargo,
				$this->tries,
				$this->getMaxTries(),
				time(),
				$delay ?? 0
			);
			
			$this->done();
		}
	}