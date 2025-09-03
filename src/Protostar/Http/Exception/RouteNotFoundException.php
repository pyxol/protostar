<?php
	namespace Protostar\Http\Exception;
	
	use Exception;
	
	class RouteNotFoundException extends Exception {
		/**
		 * Constructor for the RouteNotFoundException.
		 * @param string $message The error message.
		 * @param int $code The error code (default is 0).
		 */
		public function __construct(string $message = "Route not found", int $code = 0) {
			parent::__construct($message, $code);
		}
	}