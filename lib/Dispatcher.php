<?php

/*
	This class connects the inital dispatch.php file to the Controller system.
	It initalizes a bunch of stuff related to handling the web-request, and then hands
	control over to the specified Controlling class.
*/

final class Dispatcher extends ApplicationEntryPoint {

	static protected $status = array(
		'request_url' => '',
		'current_route_name' => '',
		'current_request' => '',
		'is_secure' => false,
		'current_http_host' => '',
		'current_http_port' => '80',
		'current_http_path_info' => '',
		'current_arguments' => array(),
		'current_controller' => '',
		'current_action' => '',
		'current_parameters' => array(),
		'flash_values' => array(),
		'dispatcher_dir' => '',
		'base_url' => '',
	);

	static protected $error_handler = array('ApplicationController','error');
	static protected $layout_handler = array('ApplicationController','layout');
	static protected $current_controller = false;
	static protected $default_view_dir = false;

	/*
		Takes a class_name and action_name which should line up to an implemented Controller and method.
		The parameters are setup in the Controller and available throughout the request.
		If it cant find the controlling class or method, it will throw an exception
	*/
	static public function call($class_name, $action_name, $parameters)
	{
		// EL - controller parameter can no longer 
		// end with 'Controller'
	
		$class_name .= "Controller";
		if (!class_exists($class_name)) {
			throw new GearsException($class_name.' class was not found.');
		}

		if (!is_subclass_of($class_name, 'Controller')) {
			throw new GearsException($class_name.' is not a subclass of ApplicationController');
		}

		self::$current_controller = new $class_name($class_name, $action_name, $parameters);

		if (self::$current_controller->before_call($action_name) === false) {
			return false;
		}

		$callback = array(self::$current_controller, $action_name);
		if (!is_callable($callback)) {
			throw new GearsException(get_class($callback[0]).'->'.$callback[1].'() is not callable.');
		}

		return call_user_func($callback);
	}
	
	/*
		Returns the last PicoraController instance created by Dispatcher::call()
	*/
	static public function get_current_controller ()
	{
		return self::$current_controller;
	}
	
	/*
		return string "get","post" or "ajax", depending on a request type. An empty POST request will resolve as a GET request.
	*/
	static public function get_request_method ()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
			? 'ajax'
			: strtolower($_SERVER['REQUEST_METHOD']); 
	}
	
	/*
		Generates a url from the given set of parameters. if routing
		is enabled will match the parameter list to an entry in the
	        routing table and generate the url passing data in the 
	        URL path vs the query string.
	*/
	static public function get_url ($parameters, $escape_ampersand = false, $host = false, $static = false )
	{
		$current_is_secure   = Dispatcher::get_status('is_secure');
		$current_http_host   = Dispatcher::get_status('current_http_host');
		$current_http_port   = Dispatcher::get_status('current_http_port');
		$base_url            = Config::settings()->gears['base_url'];

		$is_secure   = $current_is_secure;
		$http_host   = $current_http_host;
		$http_port   = $current_http_port;

		// CLI mode always needs to generate an absolute URL
		if( $host === true || ( $host === false && ApplicationEntryPoint::is_cli() === true ) ) {
			$http_host = Config::settings()->gears['web_host'];				
		} elseif ( is_string($host) ) {
			// input parameter can override the host
			$http_host = $host;
		}

                // check for secure routing...
                $secure_routing_enabled = Config::settings()->gears['secure_routing_enabled'];

		// if we are generating a URL for a 'static' file
		// do auto-append the controller and action
		if( $static === false )
		{
			// fill in controller and index if not supplied	
			if (!array_key_exists('controller', $parameters)) {
				$parameters['controller'] = Dispatcher::get_status('current_controller');
			}

			if (!array_key_exists('action', $parameters) || strlen($parameters['action']) === 0) {
				$parameters['action'] = 'index';
			}	
		}

		// if routing is enabled build the route onto the base URL otherwise 
		// build a standard query string
		if ( Routes::routing_enabled() )
		{
			if( $route_info = Routes::match_egress_route( $parameters ) )
			{
				$base_url = $base_url . $route_info->map_encoded;
				
				if( isset($route_info->port) ) 
					$http_port   = $route_info->port;
				
				if( isset($route_info->secure)) 	
					$is_secure   = $route_info->secure;
				
				if( isset($route_info->host) )
					$http_host   = $route_info->host;
				
				if( count( $route_info->actuals_remaining ) ) {
					$query_string = http_build_query($route_info->actuals_remaining, "", ($escape_ampersand? '&amp' : '&' ));
				}	

			} else {
				// OOPS --- CANT FIND ROUTE
				$msg = 'NO MATCHING ROUTE for parameters ' . Routes::debug_actuals($parameters);
				throw new GearsException($msg);
			}		 
		}
		else
		{
			$query_parameters = $parameters;
			foreach ($query_parameters as $key => $value) 
            {
				if ($value === null) 
                {
					unset($query_parameters[$key]);
				}
			}
			$query_string = http_build_query($query_parameters, "", ($escape_ampersand? '&amp;' : '&' ));
		}

		if ($host === true ||  $http_host !== $current_http_host || $http_port !== $current_http_port || $is_secure !== $current_is_secure )
		{
			$prefix = (($is_secure && $secure_routing_enabled) ? 'https' : 'http') . "://" . $http_host . 
			(($http_port != '80' && $http_port != '443')?(':' . $http_port) : '' ).
			$base_url;
		} 
        else
        {
			$prefix = $base_url;		
		}

		$url = $prefix . (isset($query_string)&&strlen($query_string)?'?'.$query_string:'');
		return $url;
	}


	/*
		Returns the response from a Controller that responded to the requested url.
	*/
	static public function dispatch ()
	{
		$dispatch_id = uniqid();

		self::load();

		//Log::debug(sprintf("%s: dispatch starting...", $dispatch_id));

		if( isset($_SERVER['HTTP_HOST']) ){
      //TODO fix depc ereg
      //DEPC
		   if( @ereg( '(.*):([0-9]+)', $_SERVER['HTTP_HOST'], $regs ) ) {
				self::$status['current_http_host'] = strtolower($regs[1]);
				self::$status['current_http_port'] = $regs[2];
		   }
		   else {
   		          	self::$status['current_http_host'] = $_SERVER['HTTP_HOST'];
		   }
		}
		
		if (isset($_SERVER['HTTPS'])) {
			self::$status['is_secure'] = true;
		}

		// preserve $_REQUEST before merging with route parameters
		self::$status['current_request'] = $parameters = $_REQUEST;
        
		// if routing is enabled - extract the parameters from PATH_INFO
		if( isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO']) && Routes::routing_enabled() )
        {
			if( $route_parameters = Routes::match_ingress_route( $_SERVER['PATH_INFO'], 
                                                                 self::$status['current_http_host'], 
                                                                 self::$status['current_http_port'], 
                                                                 self::$status['is_secure'], false ) )
            {
                //Log::debug("ROUTE FOUND:");
                //Log::debug($route_parameters);
				// merge route parameters into $_REQUEST
				$parameters = array_merge( $route_parameters, $parameters );
			} else {
				throw new FileNotFoundException($parameters);
			}
		}

		$has_controller = array_key_exists('controller', $parameters);
		$has_action = (array_key_exists('action', $parameters) && strlen($parameters['action']) !== 0);

		if ($has_controller) {
			$controller = $parameters['controller'];
		} else {
			$controller = 'Application';
		}
		
		if ($has_action) {
			$action = $parameters['action'];
		} else {
			$action = 'index';
		}

		self::$status['current_controller'] = $controller;
		self::$status['current_action'] = $action;

		ob_start();
		$response = self::call($controller, $action, $parameters);
		$crap = ob_get_clean();
		if (strlen(trim($crap)) > 0) {
			Log::notice('here is the content that was outputed:');
			Log::notice($crap);
		}	
		//Log::debug(sprintf("%s: dispatch completed", $dispatch_id));
		return $response;
	}

	/*
		Key name can be any of the following:
		'dispatcher_dir' string Path to the directory where the file that handled the request is located. For example: "/Library/WebServer/Documents/my\_app/"
		'base_url' string The base URL that the application is located at. For example: "http://localhost/my\_app/"
		'request_url' string The URL that was requested relative to the base URL. For example: "/blog/5"
		'current_route' string The route string that matched the requested url. For example: "/blog/:post\_id"
		'current_controller' string The name of the Controller that responded to the requested URL. For example: "Blog". same as current_class sans the word "Controller"
		'current_method' string The name of the method that responded to the requested URL. For example: "post"
		'current_parameters' array Merged POST and GET arrays.
		'current_arguments' mixed Array arguments passed to the called method, or bool false.
	*/	
	static public function get_status ($key = false)
	{
		return (!$key)
			? self::$status
			: (isset(self::$status[$key]) ? self::$status[$key] : false);
	}
}

?>
