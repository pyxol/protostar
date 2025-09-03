<?php
	namespace Protostar\Queue\Exception;
	
	use Exception;
	
	/**
	 * Throw this exception during a job's handle() method to indicate that the job needs to be retried.
	 */
	class RetryJobException extends Exception {
		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct("Job needs to be retried.");
		}
	}