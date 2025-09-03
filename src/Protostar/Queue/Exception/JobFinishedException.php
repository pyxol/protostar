<?php
	namespace Protostar\Queue\Exception;
	
	use Exception;
	
	/**
	 * Throw this exception during a job's handle() method to indicate that the job has finished. Not required to be thrown to be considered finished.
	 */
	class JobFinishedException extends Exception {
		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct("Job has finished.");
		}
	}