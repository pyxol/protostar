<?php
	namespace Protostar\Queue\Exception;
	
	use Exception;
	
	/**
	 * Throw this exception at any point during a worker's process to indicate that the worker should be restarted.
	 */
	class RestartWorkerException extends Exception {
		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct("Worker needs to be restarted.");
		}
	}