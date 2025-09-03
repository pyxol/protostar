<?php
	namespace Protostar\Http;
	
	use Protostar\Http\Traits\HasHeaders;
	use Protostar\Http\Traits\HasMethods;
	use Protostar\Http\Traits\HasParams;
	use Protostar\Http\Traits\HasBody;
	use Protostar\Http\Traits\HasUri;
	
	class Request {
		use HasMethods;
		use HasUri;
		use HasHeaders;
		use HasParams;
		use HasBody;
		
		protected string $uri;
		protected string|null $body;
		
		/**
		 * Constructor
		 * @param string|null $method The HTTP method (GET, POST). Defaults to the method determined from the server environment, or 'GET' if not available.
		 * @param string|null $uri The request URI. If not provided, it will be set to the current request URI.
		 * @param array|null $headers An associative array of headers. Defaults to the headers from the server environment, or an empty array if not available.
		 * @param string|null $body The request body. Defaults to null if not provided.
		 */
		public function __construct(
			string|null $uri=null
		) {
			// Determine the request method
			$this->determineMethod();
			
			// Set the request URI
			$this->determineUri($uri);
			
			// Set the headers
			$this->determineHeaders();
			
			// Set the parameters
			$this->determineParams();
			
			// Set the body
			$this->determineBody();
		}
		
		/**
		 * Check if the request content type is JSON
		 * @return bool
		 */
		public function isJson(): bool {
			return isset($this->headers['Content-Type']) && strpos($this->headers['Content-Type'], 'application/json') !== false;
		}
		
		/**
		 * Get the JSON-decoded body of the request
		 * @return void
		 */
		public function getJsonBody() {
			if($this->isJson()) {
				return json_decode($this->body, true);
			}
			
			return null;
		}
	}