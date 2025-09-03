<?php
	namespace Protostar\Http\Traits;
	
	/**
	 * Handles the HTTP methods of an HTTP request.
	 */
	trait HasMethodsTrait {
		/**
		 * The HTTP method of the request
		 * @var string
		 */
		protected string $method = 'GET';
		
		/**
		 * Array for storing method checks
		 * @var array
		 */
		protected array $isMethodTypes = [];
		
		/**
		 * Determine the HTTP method from the server environment
		 * @return void
		 */
		protected function determineMethod(string|null $method=null): void {
			if(null !== $method) {
				$this->method = strtoupper($method);
			} elseif(isset($_SERVER['REQUEST_METHOD'])) {
				$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
			}
		}
		
		/**
		 * Get the HTTP method of the request
		 * @return string
		 */
		public function getMethod(): string { return $this->method; }
		
		/**
		 * Check if the request method is GET
		 * @return bool
		 */
		public function isGet(): bool { return $this->isMethodTypes['GET'] ??= ('GET' === $this->method); }
		
		/**
		 * Check if the request method is POST
		 * @return bool
		 */
		public function isPost(): bool { return $this->isMethodTypes['POST'] ??= ('POST' === $this->method); }
		
		/**
		 * Check if the request method is PUT
		 * @return bool
		 */
		public function isPut(): bool { return $this->isMethodTypes['PUT'] ??= ('PUT' === $this->method); }
		
		/**
		 * Check if the request method is DELETE
		 * @return bool
		 */
		public function isDelete(): bool { return $this->isMethodTypes['DELETE'] ??= ('DELETE' === $this->method); }
		
		/**
		 * Check if the request method is OPTIONS
		 * @return bool
		 */
		public function isPatch(): bool { return $this->isMethodTypes['PATCH'] ??= ('PATCH' === $this->method); }
		
		/**
		 * Check if the request method is OPTIONS
		 * @return bool
		 */
		public function isOptions(): bool { return $this->isMethodTypes['OPTIONS'] ??= ('OPTIONS' === $this->method); }
		
		/**
		 * Check if the request method is HEAD
		 * @return bool
		 */
		public function isHead(): bool { return $this->isMethodTypes['HEAD'] ??= ('HEAD' === $this->method); }
	}