<?php
	namespace Protostar\Http\Traits;
	
	/**
	 * Handles the URI of an HTTP request.
	 */
	trait HasUriTrait {
		/**
		 * The URI of the request
		 * @var string
		 */
		protected string $uri;
		
		/**
		 * The real URI of the request, which may include query strings.
		 * @var string
		 */
		protected string $uri_real;
		
		/**
		 * Determine the request URI
		 * @return void
		 */
		protected function determineUri(string|null $uri=null): void {
			if(null !== $uri) {
				$this->uri = $uri;
			} elseif(isset($_SERVER['REQUEST_URI'])) {
				$this->uri = $_SERVER['REQUEST_URI'];
			} elseif(isset($_SERVER['PHP_SELF'])) {
				$this->uri = $_SERVER['PHP_SELF'];
			} else {
				$this->uri = '/';
			}
			
			// Store the real URI
			$this->uri_real = $this->uri;
			
			// Normalize the URI by removing query strings
			$this->uri = preg_replace('/\?.*/', '', $this->uri);
			$this->uri = trim($this->uri, "/");
		}
		
		/**
		 * Get the request URI
		 * @return string
		 */
		public function getUri(): string {
			return $this->uri;
		}
		
		/**
		 * Get the real request URI, including any optional query strings
		 * @return string
		 */
		public function getRealUri(): string {
			return $this->uri_real;
		}
		
		/**
		 * Determine if the request URI matches a specific pattern
		 * @TODO Implement this method
		 * @param string $pattern The pattern to match against (supports wildcards like '*')
		 * @return bool
		 */
		public function uriMatchesPattern(string $pattern): bool {
			throw new \RuntimeException('Request+HasUri::matchesPattern() is not implemented yet');
		}
	}