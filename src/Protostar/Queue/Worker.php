<?php
	namespace Protostar\Queue;
	
	use Exception;
	
	use Protostar\Queue\Queue;
	use Protostar\Queue\Job;
	use Protostar\System\Log;
	use Protostar\Queue\Exception\JobMaxAttemptsException;
	use Protostar\Queue\Exception\JobFinishedException;
	use Protostar\Queue\Exception\RetryJobException;
	use Protostar\Queue\Exception\RestartWorkerException;
	
	class Worker {
		/**
		 * Unique worker ID
		 * @var string
		 */
		protected string $worker_id = '';
		
		/**
		 * Queue name to process jobs from
		 * @var string
		 */
		protected string $queue_name = '';
		
		/**
		 * Worker constructor
		 */
		public function __construct(string|null $queue_name = null, string|null $worker_id = null) {
			// set the queue name if provided
			$this->queue_name = Queue::getQueueName($queue_name);
			
			// if not set, generate the unique worker ID by using the computer's hostname and the current timestamp
			$this->worker_id = $worker_id ?? $this->generateWorkerId();
		}
		
		/**
		 * Generate a unique worker ID
		 * @return string
		 */
		protected function generateWorkerId(): string {
			if($this->worker_id) {
				// if the worker ID is already set, return it
				return $this->worker_id;
			}
			
			// generate a unique worker ID based on the hostname and current timestamp
			$hostname = false;
			
			if(function_exists('gethostname')) {
				// use the server's hostname if available
				$hostname = gethostname();
			}
			
			if(((false === $hostname) || ('' === $hostname)) && function_exists('php_uname')) {
				// if gethostname fails, use a fallback method
				$hostname = php_uname('n');
			}
			
			return $hostname .'-'. time();
		}
		
		/**
		 * Get the unique worker ID
		 * @return string
		 */
		protected function getWorkerID(): string {
			return $this->worker_id;
		}
		
		/**
		 * Get the name of the queue this worker is processing
		 * @return string
		 */
		public function getQueueName(): string {
			return $this->queue_name;
		}
		
		/**
		 * Get the next job from the queue
		 * @return Job|null
		 */
		protected function getNextJob(): Job|null {
			return Queue::get($this->getQueueName());
		}
		
		/**
		 * Validate the data retrieved from the queue
		 * @param mixed $job The job instance retrieved from the queue
		 * @return bool
		 */
		protected function validateJob(mixed $job): bool {
			if(!($job instanceof Job)) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Validate the cargo data retrieved from the job
		 * @param array $cargo The cargo data retrieved from the job
		 * @return bool
		 */
		protected function validateCargo(array $cargo): bool {
			if(!isset($cargo['class']) || !is_string($cargo['class'])) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Create an instance of the handler from the cargo
		 * @param array $cargo The cargo data retrieved from the job
		 * @return mixed
		 */
		protected function makeHandlerInstanceFromCargo(array $cargo): mixed {
			$instance = $cargo['class']::reconstruct($cargo);
			
			return $instance;
		}
		
		/**
		 * Validate the handler instance object created from the cargo
		 * @param mixed $instance The instance of the handler created from the cargo
		 * @return bool
		 */
		protected function validateHandlerInstance(mixed $instance): bool {
			if(!is_object($instance) || !method_exists($instance, 'handle')) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Run the handler instance to process the job
		 * @param mixed $instance The instance of the handler created from the cargo
		 * @return void
		 */
		protected function runHandlerInstance(mixed $instance): void {
			$instance->handle();
		}
		
		/**
		 * Run the worker to process jobs from the queue
		 * This method will run indefinitely until a RestartWorkerException is thrown
		 */
		public function run() {
			while(true) {
				try {
					// clear previous job status (if any)
					$this->setCurrentJobStatus(null);
					
					// poll for the next job in the queue
					if(null === ($job = $this->getNextJob())) {
						continue;
					}
					
					// validate the job data
					if(!$this->validateJob($job)) {
						Log::warning("Got raw data from queue:\n". print_r($job, true));
						
						continue;
					}
					
					try {
						// get the cargo data from the job
						$cargo = $job->getCargo();
						
						// validate the cargo data
						if(!$this->validateCargo($cargo)) {
							Log::error("No class provided for job in queue");
							
							continue;
						}
						
						// create an instance of the handler from the cargo
						$instance = $this->makeHandlerInstanceFromCargo($cargo);
						
						if(!$this->validateHandlerInstance($instance)) {
							Log::error("Invalid cargo instance for job in queue");
							
							continue;
						}
						
						// run the pre-handler logic
						$this->preHandlerRun($instance, $job);
						
						// run the handler instance to process the job
						$this->runHandlerInstance($instance);
						
						// run the post-handler logic
						$this->postHandlerRun($instance, $job);
					} catch(RetryJobException $e) {
						// job needs to be retried
						
						Log::info("Retrying job (". get_class($instance) ."): ". $e->getMessage());
						
						$job->retry();
					} catch(JobFinishedException $e) {
						// job has finished, do nothing
					} catch(Exception $e) {
						Log::error("Job (". get_class($instance) .") Error: ". $e->getMessage());
						
						throw $e;
					}
				} catch(RestartWorkerException $e) {
					// worker needs to be restarted
					//Log::info("Caught RestartWorkerException: ". $e->getMessage());
					
					// break the loop to restart the worker
					break;
				} catch(JobMaxAttemptsException $e) {
					// job has failed too many times
					Log::error("Job (". get_class($instance) .") Exceeded maximum number of attempts (". $e->getMaxAttempts() ."): ". $e->getMessage());
				} catch(Exception $e) {
					Log::error([
						'message' => "Queue Error: ". $e->getMessage(),
						'trace' => $e->getTraceAsString(),
						'queue' => $job,
						'queue_data' => $job->getCargo(),
						'queue_class' => get_class($job),
					]);
				}
			}
		}
		
		/**
		 * Get the cache key for the current job status
		 * @return string
		 */
		protected function getStatusCacheKey(): string {
			return Queue::getQueueName() .':workers:'. $this->getWorkerID() .':current_job';
		}
		
		/**
		 * Set the current job status in the queue log
		 * @param array $status
		 * @return void
		 */
		protected function setCurrentJobStatus(mixed $status=null): void {
			$cache_key = $this->getStatusCacheKey();
			
			if(null === $status) {
				// if no status is provided, clear the current job status
				Queue::connection()->del($cache_key);
				
				return;
			}
			
			// Update the current job status in the queue log
			Queue::connection()->set($cache_key, $status, [
				'ex' => 60 * 60,   // expire in 1 hour
			]);
		}
		
		/**
		 * Pre-handler run logic
		 * @param mixed $instance The instance of the handler created from the cargo
		 * @param Job $job The job instance
		 * @return void
		 */
		protected function preHandlerRun(mixed $instance, Job $job): void {
			// tell the queue log about the current job's details
			$this->setCurrentJobStatus(json_encode([
				'queue_name' => $this->getQueueName(),
				'version' => Queue::getWorkerVersion($this->getQueueName()),
				'timestamp' => time(),
				'worker_id' => $this->getWorkerID(),
				'job' => [
					'cargo' => $job->getCargo(),
					'tries' => $job->getTries(),
					'timestamp' => $job->getTimestamp(),
				],
			]), [
				'ex' => 60 * 60,   // expire in 1 hour
			]);
			
			// This method can be overridden in subclasses to add pre-handler logic
			$job->starting();
		}
		
		/**
		 * Post-handler run logic
		 * @param mixed $instance The instance of the handler created from the cargo
		 * @param Job $job The job instance
		 * @return void
		 */
		protected function postHandlerRun(mixed $instance, Job $job): void {
			// Remove the job details from the queue log
			$this->setCurrentJobStatus(null);
			
			// This method can be overridden in subclasses to add post-handler logic
			$job->done();
		}
	}