<?php
	namespace Protostar\Http;
	
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
		 * Initializes the request object.
		 * @param \Protostar\Http\Request|null $request The request object to initialize. If null, a new Request object will be created.
		 */
		public function __construct(Request $request = null) {
			if(null === $request) {
				$request = new Request();
			}
			
			$this->setRequest($request);
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