<?

/* Routes:
 *
 * this class manages the loading and parsing of the routes.ini file.
 * used by Dispatcher::dispatch() and Dispatcher::get_url() to 
 * match incoming and outgoing routes.  
 *
 * INGRESS ROUTING:
 * ===============
 * the PATH_INFO of incoming requests is compared against the 
 * "match" pattern of each route definition defined in 
 * routes.ini. processing is done from the top of file to the
 * bottom and stops when a match is found. then values are
 * extracted using regex extractions in the "match" pattern and assigned
 * to the variable names contained in the routes "map" specification.
 * variable assignents are made based on associated position.
 *
 * example 1:
 *
 *   match="/(is)/(anyone)/(there)/"
 *   map="/(parm1)/(parm2)/(parm2)/"
 *
 *   this will match the incoming URL "/is/anyone/there/" 
 *   and assign $parm1="is", $parm2="anyone", and $parm3="there" 
 *   these varialbes are passed allong in the request to the controller
 *   as $this->params.  If parameters were passed in the QUERY_STRING
 *   with the same variable name, then the QUERY_SRTING will override 
 *   the parameter assignments from the route.
 * 
 *   So the url /is/anyone/there/?parm2=someone will result in 
 *   parm2 being set to "someone" 
 * 
 *   in addition to the map and match parameters, the following 
 *   parameters can be specified that will effect ingress routing.
 *   
 *   host=somehome.com --- requires that the request be for the 
 *                         host somehost.com.
 *   secure=true       --- requires that the request be over https
 *   secure=false      --- requires that the request be over http
 *  
 *
 * EGRESS ROUTING
 * ==============
 * when the program makes a call to Dispatch:get_url via the {url_for}
 * smarty helper, the specified parameter names are compared against the 
 * "map" of each route definition. processing of the routes.ini is from top
 * to bottom and stops when a match is found. the values are then substitued
 * into the "map" string and returnd as the URL. 
 *
 *  example 2: 
 *  {url_for parm1=is parm2=anyone parm3=there}
 *  would match the route 
 *
 *  match="/(is)/(anyone)/(there)/"
 *  map="/(parm1)/(parm2)/(parm2)/"
 *
 *  and result in the url "/is/anyone/there/"
 *
 *  Values must match:
 *  in addition to the variable names being present in the 'map',
 *  in order for the route to match, the values when inserted into
 *  the "map" must match the "match" pattern of the route.
 *  
 *  example 3: 
 *  {url_for param1=is param2=SOMEONE parm3=there}
 *  would NOT match the route from example 2 because the resulting
 *  url "/is/SOMEONE/there" does not match the regular expression 
 *  '/(is)/(anyone)/(there)/'
 *  
 *  Strict Routes:
 *  by default, ALL the parameter names in {url_for} must be present in the 
 *  "map" for a match to occurr. this can be altered by specifying strict=false. 
 *  in the route definition. if strict is set to false, then additional
 *  parameters are passed in the query string.
 *
 *  Absolute URLS:
 *  if a 'host' or 'secure' is specified in the route definition. the URL will
 *  be generated as an absolute url if the incoming request does not match these
 *  settings. otherwise a relative url will be created.
 * 
 * DEBUGGING A ROUTE
 * =================
 * set debug=true in the route definition to print a diagnostic to the log
 * every time the route tested against a possible match.
 *
 * Warning on case sensitive routes
 * ===================================
 * the routing mechanism is case sensitive. But as it turns out some 
 * versions of IE have a bug that forces the PATH_INFO to all lower 
 * case. so keep this in mind when using routes to pass upper case 
 * values. 
 *
 */

class Routes {
  
        static protected $routes = array();
        static protected $routing_enabled = false;
       	static protected $routes_loaded = false; 
       	        
        /* @return bool true of routing enabled false if disabled */ 
        static function routing_enabled() 
        {
            return self::$routing_enabled;
        }
  
        /* call to turn on routing */
        static function enable_routing()
        {
            self::$routing_enabled = true;
        }	
  
        /* call to turn off routing */
        static function disable_routing()
        {
            self::$routing_enabled = true;
        }	

  
        /**
         * load route table from routes.ini
         **/  
	 static public function load()
	 {
	    if(self::$routes_loaded) return;	
	    $path = $_SERVER['COMP_ROOT'].'/config/routes.ini';
	    if( file_exists($path) ) {
  	  	  if( ! $inidata = parse_ini_file( $path , true) )
			 throw new GearsException( 'unable to parse routes file ' . $path );	
		  foreach( $inidata as $route_name => $route_info ) {
				self::$routes[] = new RouteInfo($route_name,$route_info);		 
		  }		 
            }
	    self::$routes_loaded=true;
            if( count(self::$routes) ) {
              self::enable_routing();
            }
	 }

	/**
	 * add a route to the current routing table
	 * @param string $match regex to match
	 * @param array route_info associative array describing route
	 */
	 static public function add_route( $route_name, $route_info ) 
	 {
	     self::enable_routing();
             self::$routes[] = new RouteInfo( $route_name , $route_info );
	 }


	 /* send route debuging info to the debug log */
	 static private function debug_INGRESS_ROUTE( $msg, $http_host, $port, $is_secure, $url_fragment, $route_info, $debug )
	 {
	 	if( $debug || $route_info->debug === true ) {
		 	Log::debug( sprintf( '%s http_host=%s port=%s secure=%s url_fragment="%s" TO ROUTE %s', $msg, $http_host, $port, ($is_secure ? 'true': 'false'), $url_fragment, $route_info->debug_info() ));
		}
	 }

	/**
	 * input_route takes a url_framgment using the route table and 
	 * returns actuals based on a matching route
	 *
	 * @param string $url_fragement to parse
	 * @return array associative array mapping parameter name 
	 *              to value extracted from $url_fragment based on position
	 */
	static public function match_ingress_route( $url_fragment , $http_host, $port, $is_secure , $debug = false )
        {
        	if( ! self::$routes_loaded ) self::load();
		if( ! self::routing_enabled() ) return false;
		if( !strlen($url_fragment) ) return false;
				
		$http_host = strtolower($http_host);
		$routes = self::$routes;

		if( $debug == true ) {
			Log::debug( 'trying to match route for request="'. $url_fragment . '"' );
		}
		
		foreach( $routes as $route_number => $route_info ) {
			if( $actuals = $route_info->match_to_request( $url_fragment, $http_host, $is_secure ) ) {
				self::debug_INGRESS_ROUTE( 'FOUND MATCH FOR', $http_host, $port, $is_secure, $url_fragment , $route_info , $debug );
				return $actuals;
			} else {
				self::debug_INGRESS_ROUTE( 'NO MATCH FOR', $http_host, $port, $is_secure, $url_fragment , $route_info , $debug );
			}
		}
		Log::debug('no matching route for request '. $url_fragment );
		return false;
	}	

	/* print actuals to a string for debugging */
	static public function debug_actuals( $actuals )
	{
		$ans="";
		foreach($actuals as $key=>$value) 
			$ans.=$key.'='.$value . ',';
	 	return '{'.trim($ans,',').'}';
	}


	 /* send route debuging info to the debug log */
	 static private function debug_EGRESS_ROUTE( $msg, $actuals, $route_info, $debug )
	 {
	 	if( $debug || $route_info->debug === true ) {
		 	Log::debug( sprintf( '%s actuals=%s TO ROUTE %s', $msg, self::debug_actuals($actuals), 
		 			$route_info->debug_info() ));
		}
	 }


	/**
	 * attempts to matche $actuals to a route in the routing table
	 * returns RouteInfo if a match is found. otherwise returs false
	 * if no match was found 
	 */
	static public function match_egress_route( $actuals , $debug = false )
	{
            if( ! self::$routes_loaded ) self::load();
	    if( ! self::routing_enabled() ) return false;
	    
	    $routes = self::$routes;
	    foreach( $routes as $route_number => $route_info ) {
		if(  $route_info->map_actuals( $actuals ) ) { 
			self::debug_EGRESS_ROUTE( 'MATCHED', $actuals, $route_info, $debug );
			return $route_info;
		} else {
			self::debug_EGRESS_ROUTE( 'NO MATCH', $actuals, $route_info, $debug );
		}
	     }	         
	     
	     return false;
	}
}



?>
