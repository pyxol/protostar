<?php
	namespace Protostar\Http\Traits;
	
	/**
	 * Handles the headers of an HTTP request or response.
	 */
	trait HasHeadersTrait {
		/**
		 * The HTTP method of the request
		 * @var string
		 */
		protected array $headers;
		
		/**
		 * Determine the request headers
		 * @return void
		 */
		protected function determineHeaders(array|null $headers=null): void {
			if(null !== $headers) {
				if(is_array($headers)) {
					$this->headers = $headers;
				}
				
				return;
			}
			
			$this->headers = [];
			
			foreach($_SERVER as $name => $value) {
				if(0 === strpos($name, 'HTTP_')) {
					$headerName = str_replace('_', '-', strtolower(substr($name, 5)));
					
					if('' === $headerName) {
						continue;
					}
					
					$this->headers[ $headerName ] = $value;
				}
			}
		}
		
		/**
		 * Get all request headers
		 * @return array
		 */
		public function getHeaders(): array {
			return $this->headers;
		}
		
		/**
		 * Get a specific header by name. If the header does not exist, returns null
		 * @param string $name The name of the header
		 * @return void
		 */
		public function getHeader(string $name): mixed {
			return $this->headers[ strtolower($name) ] ?? null;
		}
		
		/**
		 * Check if a specific header exists
		 * @param string $name The name of the header
		 * @return bool
		 */
		public function hasHeader(string $name): bool {
			return isset($this->headers[ strtolower($name) ]);
		}
	}