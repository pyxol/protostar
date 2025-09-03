<?php
	namespace Protostar\Database\Exception;
	
	use Exception;
	
	class ConnectionFailedException extends Exception {
		/**
		 * Constructor for the ConnectionFailedException.
		 * @param string $message The error message.
		 * @param int $code The error code (default is 0).
		 */
		public function __construct(string $message = "Database connection failed", int $code = 0) {
			parent::__construct($message, $code);
		}
	}