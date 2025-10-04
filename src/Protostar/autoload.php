<?php
	// Helper methods
	
	/**
	 * Get the current application instance or a specific instance by name
	 * @param string $instance_name The name of the instance to retrieve. Defaults to 'app'
	 * @return mixed The application instance or the specific instance by name
	 * @throws \InvalidArgumentException If the instance name is not recognized
	 */
	function app(string $instance_name='app'): mixed {
		return \Protostar\App::instance($instance_name);
	}
	
	/**
	 * Get a configuration value by config name and key
	 * @param string $key Dotted key for the configuration value (e.g., 'app.base_url')
	 * @param mixed $default Default value to return if the configuration key does not exist
	 * @param bool $fresh If true, will return the fresh value from the config file, otherwise will return the cached value
	 * @return mixed
	 */
	function config(string $key, mixed $default=null, bool $fresh=false): mixed {
		return app()->config($key, $default, $fresh);
	}
	
	/**
	 * Get the database handler instance
	 * @param string|null|null $name The name of the database handler to retrieve as defined in configs/database.php under 'connections'. If null, the default handler will be returned.
	 * @return \Protostar\Database\DatabaseHandler
	 */
	function db(string|null $name=null): \Protostar\Database\DatabaseHandler {
		return app('db'. (null !== $name?'.'. $name:''));
	}
	
	/**
	 * Display a template file
	 * @param string $name The name of the template file (without extension)
	 * @param array $data An associative array of data to be passed to the template
	 * @return void
	 */
	function display_tpl(string $__tpl_name, array $__tpl_data = []): void {
		// Extract the data to variables
		extract($__tpl_data, EXTR_SKIP);
		
		// Include the template file
		include '/app/templates/'. $__tpl_name .'.php';
	}
	
	/**
	 * Get an environment variable
	 * @param string $key The name of the environment variable
	 * @param mixed $default The default value to return if the environment variable is not set
	 * @return mixed The value of the environment variable, or the default value if not set. If the env var value is set and is a string that can be interpreted as a boolean, it will return true or false accordingly.
	 */
	function env(string $key, mixed $default = null): mixed {
		// If the value is not set, return the default value
		if(false === ($value = getenv($key))) {
			return $default;
		}
		
		$value = trim($value);
		
		$value_lc = strtolower($value);
		
		// Return the value, converting to boolean if necessary
		if(('true' === $value_lc) || ('yes' === $value_lc)) {
			return true;
		} elseif(('false' === $value_lc) || ('no' === $value_lc)) {
			return false;
		}
		
		return $value;
	}
	
	/**
	 * Escape a string for use in HTML attributes
	 * @param string $string The string to escape
	 * @return string
	 */
	function esc_attr(string $string): string {
		// Escape HTML special characters for use in attributes
		return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
	
	/**
	 * Escape a string for use in HTML content
	 * @param string $string The string to escape
	 * @return string
	 */
	function esc_html(string $string): string {
		// Escape HTML special characters
		return htmlspecialchars($string, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
	}
	
	/**
	 * Encode data to JSON format and return a JSON response
	 * @param mixed $data
	 * @param int $options
	 * @return \Protostar\Http\JsonResponse
	 */
	function json(mixed $data, int $options = 0): \Protostar\Http\JsonResponse {
		// Convert the data to JSON format
		$json = json_encode($data, $options);
		
		// If JSON encoding fails, throw an exception
		if(false === $json) {
			throw new \RuntimeException('JSON encoding error: ' . json_last_error_msg());
		}
		
		// Create a new JSON response
		$response = new \Protostar\Http\JsonResponse();
		$response->setBody($json);
		$response->setHeader('Content-Type', 'application/json');
		$response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
		$response->setHeader('Pragma', 'no-cache');
		$response->setHeader('Expires', '0');
		$response->setStatusCode(200);
		
		// Return the JSON response
		return $response;
	}
	
	/**
	 * Parse a template file and return the rendered content
	 * @param string $name The name of the template file (without extension)
	 * @param array $data An associative array of data to be passed to the template
	 * @return string
	 */
	function parse_template(string $name, array $data = []): string {
		// Extract the data to variables
		extract($data, EXTR_SKIP);
		
		// Start output buffering
		ob_start();
		
		// Include the template file
		include '/app/templates/'. $name .'.php';
		
		// Get the contents of the buffer
		return ob_get_clean();
	}
	
	/**
	 * Crete a redirect response
	 * @param string $url The URL to redirect to
	 * @param int $status_code The HTTP status code for the redirect (default is 302)
	 * @return \Protostar\Http\RedirectResponse
	 */
	function redirect(string $url, int $status_code = 302): \Protostar\Http\RedirectResponse {
		// Create a new response instance
		$response = new \Protostar\Http\RedirectResponse($status_code);
		
		// Set the Location header
		$response->setUrl($url);
		
		return $response;
	}
	
	/**
	 * Get the current request instance
	 * @return \Protostar\Http\Request
	 */
	function request(): \Protostar\Http\Request {
		// Return the current request instance
		return app('request');
	}
	
	/**
	 * Generate a URL for a named route
	 * @param string $name
	 * @param array $params
	 * @return string
	 */
	function route(string $name, array $params = []): string {
		// Get the router instance
		/**
		 * @var \Protostar\Http\Router $router
		 */
		$router = app('router');
		
		// Generate the URL for the named route
		return $router->url($name, $params);
	}
	
	/**
	 * Send a template response
	 * @param string $name The name of the template file (without extension)
	 * @param array $data An associative array of data to be passed to the template
	 * @return \Protostar\Http\Response
	 */
	function template(string $name, array $data = []): \Protostar\Http\Response {
		$response = new \Protostar\Http\Response();
		$response->setBody( parse_template($name, $data) );
		
		return $response;
	}