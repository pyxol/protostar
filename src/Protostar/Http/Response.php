<?php
	namespace Protostar\Http;
	
	class Response {
		protected int $statusCode = 200;
		protected array $headers = [];
		protected string|null $body = null;
		
		/**
		 * Constructor for the Response class.
		 * @param int|null $statusCode The HTTP status code for the response, default is 200.
		 * @param array|null $headers An associative array of headers to set for the response, default is an empty array.
		 * @param string|null $body The body content of the response, default is null.
		 */
		public function __construct(
			int|null $statusCode=null,
			array|null $headers=null,
			string|null $body=null
		) {
			// Set the status code if provided
			if(null !== $statusCode) {
				$this->setStatusCode($statusCode);
			}
			
			// Set the headers if provided
			if(null !== $headers) {
				foreach($headers as $name => $value) {
					$this->setHeader($name, $value);
				}
			}
			
			// Set the body if provided
			if(null !== $body) {
				$this->setBody($body);
			}
		}
		
		/**
		 * Get the HTTP status code for the response.
		 * @return int The HTTP status code.
		 */
		public function getStatusCode(): int {
			return $this->statusCode;
		}
		
		/**
		 * Set the HTTP status code for the response.
		 * @param int $code The HTTP status code.
		 */
		public function setStatusCode(int $code): Response {
			$this->statusCode = $code;
			
			return $this;
		}
		
		/**
		 * Get the headers for the response.
		 * @return array An associative array of headers.
		 */
		public function getHeaders(): array {
			return $this->headers;
		}
		
		/**
		 * Set a header for the response.
		 * @param string $name The name of the header.
		 * @param string $value The value of the header.
		 */
		public function setHeader(string $name, string $value): Response {
			$this->headers[ $name ] = $value;
			
			return $this;
		}
		
		/**
		 * Get the body of the response.
		 * @return string|null The body content.
		 */
		public function getBody(): string|null {
			return $this->body;
		}
		
		/**
		 * Set the body of the response.
		 * @param string|null $body The body content.
		 */
		public function setBody(string|null $body): Response {
			$this->body = $body;
			
			return $this;
		}
		
		/**
		 * Send the response to the client.
		 */
		public function send(): void {
			if(!headers_sent()) {
				http_response_code($this->getStatusCode());
				
				// Set the headers
				header('Content-Type: text/html; charset=UTF-8');
				
				foreach($this->getHeaders() as $name => $value) {
					if('' === ($name = trim($name))) {
						continue; // Skip empty header names
					}
					
					header($name .": ". trim($value));
				}
			}
			
			if(null !== $this->body) {
				print $this->getBody();
			}
		}
	}