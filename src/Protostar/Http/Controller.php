<?php
	namespace Protostar\Http;
	
	use Protostar\Http\Request;
	use Protostar\Http\Response;
	
	class Controller {
		/**
		 * The request object for the controller.
		 * @var \Protostar\Http\Request
		 */
		protected Request $request;
		
		/**
		 * The response object for the controller.
		 * @var \Protostar\Http\Response|null
		 */
		protected ?Response $response = null;
		
		/**
		 * Constructor for the controller.
		 * @param \Protostar\Http\Request|null $request The request object to initialize. If null, the application's request object will be used.
		 */
		public function __construct(Request|null $request = null) {
			$this->request = $request ?: \app('request');
		}
		
		/**
		 * Set the request object for the controller.
		 * @param \Protostar\Http\Request $request The request object to set.
		 */
		protected function setRequest(Request $request): void {
			$this->request = $request;
		}
		
		/**
		 * Get the request object for the controller.
		 * @return \Protostar\Http\Request
		 */
		public function getRequest(): Request {
			return $this->request;
		}
	}