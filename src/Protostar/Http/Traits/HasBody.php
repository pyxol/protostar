<?php
	namespace Protostar\Http\Traits;
	
	trait HasBody {
		/**
		 * The body of the request, decoded if applicable. Null if no body is present
		 * @var string
		 */
		protected mixed $body = null;
		
		/**
		 * Determine the request body
		 * @param mixed $body The body to set. If false, the body will be determined from the request
		 * @return void
		 */
		protected function determineBody(mixed $body=false): void {
			if(false !== $body) {
				$this->body = $body;
				
				return;
			}
			
			// @TODO better handling of different content types
			// eg application/json, multipart/form-data, etc.
			
			// Check if the request method is POST or PUT
			if($this->isPost() || $this->isPut() || $this->isPatch() || $this->isDelete()) {
				// Get the raw input from the request body
				$this->body = file_get_contents('php://input');
				
				if('' === $this->body) {
					$this->body = null;
				}
				
				return;
			}
		}
		
		/**
		 * Get the request body
		 * @return array|string|null
		 */
		public function getBody(): array|string|null {
			return $this->body;
		}
		
		
	}