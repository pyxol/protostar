<?php
	namespace Protostar\Queue\Exception;
	
	use Exception;
	
	class JobMaxAttemptsException extends Exception {
		/**
		 * The maximum number of attempts for the job
		 * @var int
		 */
		protected int $max_attempts;
		
		/**
		 * Constructor
		 * @param int $max_attempts The maximum number of attempts
		 */
		public function __construct(int $max_attempts) {
			$this->max_attempts = $max_attempts;
			
			parent::__construct("Job has exceeded the maximum number of attempts: ". $this->getMaxAttempts());
		}
		
		/**
		 * Get the maximum number of attempts
		 * @return int
		 */
		public function getMaxAttempts(): int {
			return $this->max_attempts;
		}
	}