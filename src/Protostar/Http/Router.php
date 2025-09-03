<?php
	namespace Protostar\Http;
	
	use Exception;
	use BadMethodCallException;
	use RuntimeException;
	
	use Protostar\App;
	use Protostar\Http\Request;
	use Protostar\Http\Response;
	use Protostar\Http\Exception\RouteNotFoundException;
	
	class Router {
		/**
		 * The routes array
		 * @var array
		 */
		protected static array $routes = [];
		
		/**
		 * The application instance
		 * @var \Protostar\App|null
		 */
		protected static App|null $app = null;
		
		/**
		 * Constructor
		 */
		public function __construct() {
			
		}
		
		/**
		 * Load routes from a specified file
		 * @param string $routes_path The path to the routes file
		 * @return Router
		 * @throws Exception If the routes file does not exist
		 */
		public function loadRoutes(string $routes_path): Router {
			if(!file_exists($routes_path)) {
				throw new Exception("Routes file not found: " . $routes_path);
			}
			
			require_once($routes_path);
			
			return $this;
		}
		
		/**
		 * Add a route
		 * @param string $method The HTTP method (GET, POST, etc.).
		 * @param string $path The path
		 * @param mixed $handler The handler function
		 */
		public function addRoute(string $method, string $path, mixed $handler): void {
			self::$routes[] = [
				'method' => strtoupper($method),
				'path' => $path,
				'handler' => $handler
			];
		}
		
		/**
		 * Add a route that matches any HTTP method (GET or POST for now)
		 * @param string $path The path
		 * @param mixed $handler The handler function
		 * @return void
		 */
		public static function any(string $path, mixed $handler): void {
			self::get($path, $handler);
			self::post($path, $handler);
		}
		
		/**
		 * Add a route that matches all HTTP methods. Uses Router::any()
		 * @param string $path The path
		 * @param mixed $handler The handler function
		 * @return void
		 */
		public static function all(string $path, mixed $handler): void {
			self::any($path, $handler);
		}
		
		/**
		 * Add a GET route
		 * @param string $path The path
		 * @param mixed $handler The handler function
		 */
		public static function get(string $path, mixed $handler): void {
			self::$routes[] = [
				'method' => 'GET',
				'path' => $path,
				'handler' => $handler
			];
		}
		
		/**
		 * Add a POST route
		 * @param string $path The path
		 * @param mixed $handler The handler function
		 */
		public static function post(string $path, mixed $handler): void {
			self::$routes[] = [
				'method' => 'POST',
				'path' => $path,
				'handler' => $handler
			];
		}
		
		/**
		 * Match the request to a route and execute the handler
		 * @param \Protostar\Http\Request $request The incoming request
		 * @return \Protostar\Http\Response The response from the handler
		 * @throws \Protostar\Http\Exception\RouteNotFoundException If no route matches the request
		 */
		public function match(Request $request): Response {
			foreach(self::$routes as $route) {
				if($route['method'] !== $request->getMethod()) {
					continue;
				}
				
				// Check if the path matches the request URI
				if($route['path'] === $request->getUri()) {
					return $this->callHandler($route['handler'], $request);
				}
				
				// If the path has { and }, we need to handle dynamic segments
				$route_path = $route['path'];
				
				$route_regex = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) {
					return '([a-zA-Z0-9_]+)';
				}, $route_path);
				
				$route_regex = '#^' . $route_regex . '$#';
				
				if(preg_match($route_regex, $request->getUri(), $matches)) {
					// If the route matches, we can call the handler
					// Pass the dynamic segments as parameters to the handler
					$this->digestRoutePathParams($route_path, $request);
					
					// Call the handler with the request
					return $this->callHandler($route['handler'], $request);
				}
			}
			
			throw new RouteNotFoundException("No route found for " . $request->getMethod() . " " . $request->getUri());
		}
		
		/**
		 * Digest the route path parameters and set them in the request object
		 * @param string $path The route path
		 * @param \Protostar\Http\Request $request The request object
		 * @return void
		 */
		protected function digestRoutePathParams(string $path, Request $request): void {
			$route_params = $this->extractRouteParams(
				$path,
				$request->getUri()
			);
			
			if(!empty($route_params)) {
				// Set the parameters in the request object
				$request->setParams($route_params);
			}
		}
		
		/**
		 * Extract route parameters from the route and request URI
		 * @param string $route The route pattern. Eg: '/stock/{symbol}/'
		 * @param string $uri The request URI. Eg: '/stock/AAPL/'
		 * @return array
		 */
		protected function extractRouteParams(string $route, string $uri): array {
			$pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route);
			$pattern = '#^' . rtrim($pattern, '/') . '/?$#i';
			
			if(preg_match($pattern, $uri, $matches)) {
				return array_filter(
					$matches,
					fn($k) => !is_int($k),
					ARRAY_FILTER_USE_KEY
				);
			}
			
			return [];
		}
		
		/**
		 * 
		 * @param mixed $handler
		 * @param \Protostar\Http\Request $request
		 * @return \Protostar\Http\Response
		 */
		protected function callHandler(mixed $handler, Request $request): Response {
			if(is_callable($handler)) {
				return call_user_func($handler, $request);
			}
			
			list($controllerClass, $method) = $this->getHandlerCallable($handler);
			
			$controllerInstance = new $controllerClass($request);
			
			$method_args = [
				$request
			];
			
			if('__invoke' === $method) {
				// If the method is __invoke, we can call the controller instance directly
				return $controllerInstance(...$method_args);
			} elseif(method_exists($controllerInstance, $method)) {
				return call_user_func([$controllerInstance, $method], ...$method_args);
			} else {
				throw new RouteNotFoundException("Handler not found for ". $handler);
			}
		}
		
		/**
		 * Get the handler callable from a string
		 * @param string $handler The handler string in the format 'Controller@method'
		 * @return array
		 */
		protected function getHandlerCallable(string $handler): array {
			// If the handler is a string, it should be in the format 'Controller@method'
			if(false === strpos($handler, '@')) {
				// assume format is Controller@method
				list($controllerClass, $method) = explode('@', $handler, 2);
			} else {
				$controllerClass = $handler;
				$method = '';
			}
			
			if(!class_exists($controllerClass)) {
				// if the specified controller class does not exist, assume it's in a commonly used namespace
				if(class_exists("\\App\\Http\\Controllers\\". $controllerClass)) {
					$controllerClass = "\\App\\Http\\Controllers\\". $controllerClass;
				} elseif(class_exists("\\Protostar\\Http\\Controllers\\". $controllerClass)) {
					$controllerClass = "\\Protostar\\Http\\Controllers\\". $controllerClass;
				} else {
					throw new RuntimeException("Controller class not found: ". $controllerClass);
				}
			}
			
			return [$controllerClass, $method];
		}
		
		
		/**
		 * Get the application instance
		 * @return \Protostar\App|null
		 */
		public static function getApp(): ?App {
			return self::$app;
		}
		
		public function url(string $path, array $params = []): string {
			// Build the URL based on the path and parameters
			$url = rtrim(config('app.base_url', '/'), '/') . '/' . ltrim($path, '/');
			
			if(!empty($params)) {
				$url .= '?' . http_build_query($params);
			}
			
			return $url;
		}
	}