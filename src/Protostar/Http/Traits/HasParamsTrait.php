<?php
	namespace Protostar\Http\Traits;
	
	/**
	 * Handles the query parameters of an HTTP request.
	 */
	trait HasParamsTrait {
		/**
		 * The query parameters of the request
		 * @var array
		 */
		protected array $params = [];
		
		/**
		 * Determine the query parameters from the request
		 * @return void
		 */
		protected function determineParams(array|null $params=null): void {
			// If $params is provided, use it to set the parameters
			if(null !== $params) {
				if(is_array($params)) {
					$this->setParams($params);
				}
				
				return;
			}
			
			// If the method is GET, use $_GET superglobal
			if($this->isGet()) {
				foreach($_GET as $key => $value) {
					$this->setParam($key, $value);
				}
				
				return;
			}
			
			// If the method is POST, use $_POST superglobal
			if($this->isPost()) {
				foreach($_POST as $key => $value) {
					$this->setParam($key, $value);
				}
				
				// @TODO Handle multipart/form-data, application/json, etc. in the body
				
				return;
			}
			
			// For other methods, parse the query string from the URI
			
			// If the request URI contains a query string, parse it
			if(false !== strpos($this->uri, '?')) {
				// Split the URI into path and query string
				$parts = explode('?', $this->uri, 2);
				
				
				// @TODO remove altering $this->uri here
				$this->uri = $parts[0];
				
				if(isset($parts[1])) {
					// Parse the query string into an associative array
					parse_str($parts[1], $this->params);
				}
				
				// Remove the query string from the URI
				$this->uri = rtrim($this->uri, '?&');
			}
		}
		
		/**
		 * Check if a specific query parameter exists
		 * @param string $name The name of the query parameter
		 * @return bool
		 */
		public function hasParam(string $name): bool {
			return isset($this->params[ $name ]);
		}
		
		/**
		 * Get the query parameters from the request
		 * @return array
		 */
		public function getParams(): array {
			return $this->params;
		}
		
		/**
		 * Get a specific query parameter by name
		 * @param string $name The name of the query parameter
		 * @param mixed $default The default value to return if the parameter does not exist (default: null)
		 * @return mixed The value of the query parameter, or $default if it does not exist
		 */
		public function getParam(string $name, mixed $default=null): mixed {
			return $this->params[ $name ] ?? $default;
		}
		
		/**
		 * Set a single query parameter for the request
		 * @param string $name The name of the query parameter
		 * @param mixed $value The value of the query parameter
		 * @return void
		 */
		public function setParam(string $name, mixed $value): void {
			// null value means we want to remove the parameter
			if(null === $value) {
				unset($this->params[ $name ]);
				
				return;
			}
			
			$this->params[ $name ] = $value;
		}
		
		/**
		 * Set parameters for the request
		 * @param array $params An associative array of parameters to set
		 * @return void
		 */
		public function setParams(array $params): void {
			foreach($params as $name => $value) {
				if(is_null($value)) {
					unset($this->params[ $name ]);
					
					continue;
				}
				
				$this->params[ $name ] = $value;
			}
		}
	}