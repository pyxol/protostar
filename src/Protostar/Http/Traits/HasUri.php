<?php
	namespace Protostar\Http\Traits;
	
	trait HasUri {
		/**
		 * The URI of the request
		 * @var string
		 */
		protected mixed $uri = null;
		
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
		}
		
		/**
		 * Get the request URI
		 * @return string
		 */
		public function getUri(): string {
			return $this->uri;
		}
		
		/**
		 * Determine if the request URI matches a specific pattern
		 * @param string $pattern The pattern to match against (supports wildcards like '*')
		 * @return bool
		 */
		public function uriMatchesPattern(string $pattern): bool {
			throw new \RuntimeException('Request+HasUri::matchesPattern() is not implemented yet');
		}
	}