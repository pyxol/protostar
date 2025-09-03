<?php
	namespace Protostar;
	
	use Protostar\Http\Request;
	use Protostar\Http\Response;
	use Protostar\Http\Router;
	
	class App {
		/**
		 * The static instances of the application.
		 * This is used to store the current instance of the application and its components.
		 * @var array<string, mixed>
		 */
		protected static array $instances = [];
		
		/**
		 * The request object for the application.
		 * @var \Protostar\Http\Request
		 */
		protected Request $request;
		
		/**
		 * The router object for the application.
		 * @var \Protostar\Http\Router
		 */
		protected Router $router;
		
		/**
		 * The response object for the application.
		 * @var \Protostar\Http\Response
		 */
		protected Response|null $response = null;
		
		/**
		 * Constructor for the application.
		 * Initializes the request and router objects.
		 * @param \Protostar\Http\Request|null $request The request object to initialize. If null, a new Request object will be created.
		 * @param \Protostar\Http\Router|null $router The router object to initialize. If null, a new Router object will be created.
		 */
		public function __construct(
			Request $request = null,
			Router $router = null
		) {
			// Include the necessary helper functions
			require_once(__DIR__ .'/helpers.php');
			
			// Initialize the request and router objects
			$this->request = $this->initializeRequest($request);
			$this->router = $this->initializeRouter($router);
			
			// assign the current instance to the static instances array
			self::$instances['app'] = &$this;
			self::$instances['request'] = &$this->request;
			self::$instances['router'] = &$this->router;
		}
		
		/**
		 * Get the application instance or a specific instance by identifier. If the instance does not exist, this will attempt to create it dynamically
		 * @param string $identifier The name of the instance to retrieve. For example, 'app' returns the application instance
		 * @return mixed
		 */
		public static function instance(string $identifier='app'): mixed {
			// Return the instance from the static instances array
			return self::$instances[ $identifier ] ??= self::createInstance($identifier);
		}
		
		
		public function getInstance(string $identifier='app'): mixed {
			// Return the instance from the static instances array
			return self::$instances[ $identifier ] ??= self::createInstance($identifier);
		}
		
		/**
		 * Create a new instance based on the identifier
		 * @param string $identifier The identifier for the instance to create
		 * @return mixed
		 * @throws \InvalidArgumentException If the identifier does not match any known patterns
		 */
		protected static function createInstance(string $identifier): mixed {
			
			
			// @TODO extract this and container-related methods/props into HasContainer trait or similar
			
			
			// application
			if('app' === $identifier) {
				// If the identifier is 'app', return the current application instance
				return self::$instances['app'] ??= (new self());
			}
			
			// request
			if('request' === $identifier) {
				// If the identifier is 'request', return the current request instance
				return self::$instances['request'] ??= (new self())->initializeRequest();
			}
			
			// router
			if('router' === $identifier) {
				// If the identifier is 'router', return the current router instance
				return self::$instances['router'] ??= (new self())->initializeRouter();
			}
			
			// database
			if('db' === $identifier) {
				return \Protostar\Database\Database::getDefaultHandler();
			} elseif(preg_match("#^db\.([A-Za-z0-9_]+)$#", $identifier, $matches)) {
				// If the identifier matches the database handler pattern, create the handler instance
				return \Protostar\Database\Database::getHandler($matches[1]);
			}
			
			// cache
			if('cache' === $identifier) {
				return \Protostar\Cache\Cache::getDefaultHandler();
			} elseif(preg_match("#^cache\.([A-Za-z0-9_]+)$#", $identifier, $matches)) {
				// If the identifier matches the cache handler pattern, create the handler instance
				return \Protostar\Cache\Cache::getHandler($matches[1]);
			}
			
			throw new \InvalidArgumentException("Unknown instance identifier: ". $identifier);
		}
		
		/**
		 * Get the request object for the application.
		 * @return \Protostar\Http\Request
		 */
		public function getRequest(): Request {
			return $this->request;
		}
		
		/**
		 * Set the request object for the application.
		 * @param \Protostar\Http\Request $request The request object to set.
		 */
		public function setRequest(Request $request): void {
			$this->request = &$request;
			
			// Update the static instance for the request
			self::$instances['request'] = &$this->request;
		}
		
		/**
		 * Initialize the request object for the application.
		 * @param \Protostar\Http\Request|null $request The request object to initialize. If null, a new Request object will be created.
		 * @return \Protostar\Http\Request
		 */
		protected function initializeRequest(Request|null $request=null): Request {
			if(null === $request) {
				$request = new Request();
			}
			
			$this->request = &$request;
			
			return $request;
		}
		
		/**
		 * Get the router object for the application.
		 * @return \Protostar\Http\Router
		 */
		public function getRouter(): Router {
			return $this->router;
		}
		
		/**
		 * Set the router object for the application.
		 * @param \Protostar\Http\Router $router The router object to set.
		 */
		public function setRouter(Router $router): void {
			$this->router = &$router;
			
			// Update the static instance for the router
			self::$instances['router'] = &$this->router;
		}
		
		/**
		 * Initialize the router object for the application.
		 * @param \Protostar\Http\Router|null $router The router object to initialize. If null, a new Router object will be created.
		 * @return \Protostar\Http\Router
		 */
		protected function initializeRouter(Router $router = null): Router {
			if(null === $router) {
				$router = new Router();
			}
			
			$this->router = &$router;
			
			return $this->router;
		}
		
		/**
		 * Generate a response for the application.
		 * @return \Protostar\Http\Response
		 */
		protected function generateResponse(): Response {
			if(null !== $this->response) {
				// If a response has already been generated, return it
				return $this->response;
			}
			
			// use router to find the route and call the appropriate controller/handler
			try {
				// Match the request to a route and execute the handler
				$this->response = $this->router->match($this->request);
			} catch (\Exception $e) {
				// If no route matches, set a 404 Not Found response
				$this->response = new Response();
				$this->response->setStatusCode(404);
				$this->response->setHeader('Content-Type', 'text/plain; charset=UTF-8');
				$this->response->setBody('404 Not Found: '. $e->getMessage());
			}
			
			return $this->response;
		}
		
		/**
		 * Respond to the request and return a response.
		 * @param \Protostar\Http\Request $request
		 * @return \Protostar\Http\Response
		 */
		public function respond(): Response {
			return $this->response ??= $this->generateResponse();
		}
		
		/**
		 * Send the response to the client.
		 * @param \Protostar\Http\Response $response
		 * @return void
		 */
		public function sendResponse(Response $response): void {
			// Send the response to the client
			$response->send();
		}
		
		
		
		protected static $cfgs = [];
		
		/**
		 * Get a configuration value by config name and key
		 * @param string $key Dotted key for the configuration value (e.g., 'app.base_url')
		 * @param mixed $default Default value to return if the configuration key does not exist
		 * @param bool $fresh If true, will return the fresh value from the config file, otherwise will return the cached value
		 * @return mixed
		 * @throws RuntimeException If the configuration file for the key does not return an array
		 */
		public function config(string $key, mixed $default=null, bool $fresh=false): mixed {
			$parts = [];
			
			if(false !== strpos($key, '.')) {
				// If the key contains a dot, treat it as a nested configuration
				$parts = explode('.', $key);
				$key = array_shift($parts);
			}
			
			if('' === ($key = strtolower(trim($key)))) {
				return $default;
			}
			
			if($fresh || !isset(self::$cfgs[$key])) {
				// If the configuration is not cached or fresh is true, load it from the config file
				$config = require('/app/config/'. $key .'.php');
				
				if(!is_array($config)) {
					throw new \RuntimeException("Configuration file for key '$key' must return an array.");
				}
				
				self::$cfgs[ $key ] = $config;
			}
			
			// Get the configuration value from the cached configuration
			$value = self::$cfgs[ $key ];
			
			if(!empty($parts)) {
				// If there are nested parts, traverse the configuration array
				foreach($parts as $part) {
					if(is_array($value) && array_key_exists($part, $value)) {
						$value = $value[$part];
					} else {
						// If the part does not exist, return the default value
						return $default;
					}
				}
				
				return $value;
			}
			
			// If no nested parts, return the value directly
			return $value ?? $default;
		}
		
	}