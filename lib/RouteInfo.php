<?

/* class to represent an individual route specification. this 
 * class is use by the class Route.
 */
class RouteInfo {
  
    public $name   = NULL;
    public $map    = NULL;
    public $match  = NULL;
    public $secure = NULL;
    public $host   = NULL;
    public $host_lookup = NULL;
    public $debug  = false;
    public $map_raw = NULL;
    public $map_encoded = NULL;
    public $port = NULL;
    public $actuals_remaining = array();
    public $strict = true;
    
    // construct route object from array
    public function __construct( $route_name, $route_info ) 
    {
          $this->name = $route_name;
	  $valid_keys = array( 'map', 'match', 'host', 'secure' , 'strict', 'debug', 'port', 'constants' , 'host_lookup' );
	  
          foreach( $route_info as $key => $val )
          {
	      	if( false === array_search( $key, $valid_keys ) ){
  	        	 throw new GearsException('error in route information for ['. $route_name .'] invalid key "'. $key .'"' );
                }
            
                // turn 1/0 into a bool
                if( $key == 'secure' || $key == 'strict' || $key == 'debug' ) {
                    $val  = (bool) $val;
                }
                
                $this->$key = $val;
          }

          if( ! isset( $this->match )  ) {
  	        throw new GearsException('error in route information for ['. $route_name .'] required parameter "map" is not set' );
  	  }     	 
  	  
    }
    
    /**
     * print information about this route to a string
     */
    public function debug_info()
    {
         $msg = sprintf( '[%s] match="^%s$" map="%s"' , $this->name, $this->match, $this->map );
         $msg.= sprintf( ' strict=%s', ($this->strict===true ? 'true' : 'false' ) );
 
         if( $this->secure === true )
           $msg .= sprintf( ' secure=%s', ($this->secure===true ? 'true' : 'false' ) );
         
         if( $this->host != NULL )
           $msg .= sprintf( ' host=%s', $this->host );         
           
         if( $this->host_lookup != NULL ) {
             $msg .= sprintf( ' host_lookup=%s', $this->host_lookup );
         }  
         
         if( $this->map_raw != NULL )
           $msg .= sprintf( ' mapped to="%s"', $this->map_raw );
      
         if( count($this->actuals_remaining) )
           $msg .= sprintf( ' with actuals remaining %s', Routes::debug_actuals($this->actuals_remaining) );    
           
         return $msg;  

    }

    /* scan $this->map for {variables} and return a numerically
     * indexed array of variable names.
     * 
     * @param string $map to scan
     * @return array of variable names 
     */
    private function get_map_params()
    {
	 	$ans=array(); $x = explode ('{', $this->map );
	 	for($i=1;$i<count($x);$i++) {
			$y=explode('}',$x[$i]);$ans[]=$y[0];
	 	}
	 	return $ans;
    }

	private function get_constant_params ()
	{
		$return = array();
		if (isset($this->constants)) {
			parse_str($this->constants, $return);
		}

		return $return;
	}

    /**
     * match_to_request
     * if the url fragment in $request matches the regular expression
     * pattern in $this->match then the extracted values returned 
     * by ereg are returned as an associative array using the variable
     * names from $this->map that have the same position relative.
     *
     * returns false if the route does not match
     * 
     * @param string $request the url fragment to test
     * @return mixed array of actuals if a match is found false if no 
     *           match
     */
	public function match_to_request( $request, $http_host=NULL, $http_port=NULL, $is_secure=NULL )
	{
		// host and secure must match
		if( is_null($is_secure) === false && is_null( $this->is_secure ) === false && $this->secure != $is_secure ) return false;

		if( is_null($http_host) === false && is_null( $this->host ) === false && $this->host != $http_host ) return false;

		if( is_null($http_port) === false && is_null( $this->port ) === false && $this->port != $http_port ) return false;

		// handle host_lookup
		$hl_params = array();
		if( is_null($http_host) == false && is_null( $this->host_lookup ) == false ) {
		    if( ($result = Model::find( $this->host_lookup , array('where' => array('hostname = ?', $http_host )))) === false ) {
		        return false;		        
		    }
		    $hl_params = $result->toArray();    
		}

		// check the regular expression in $this->match
		// map extracted values to variables specified in $this->map  
		// that have the same relative position
    //TODO: DEPC ereg
		if(@ereg( '^'.$this->match . '$' , $request, $regs ) ) {
			$map_params = $this->get_map_params();
			$constants = $this->get_constant_params();
			$actuals=array();
			for($i=1;$i<count($regs);$i++) {
				if (!isset($map_params[$i-1])) {
					throw new GearsException('invalid route: route map missing parameter '. ($i-1) . ' for request ');
					//. $request .print_r($map_params, true).print_r($regs, true));
				}
				$actuals[$map_params[$i-1]] = urldecode($regs[$i]);
			}
			
			// assign host_lookup parameters
			foreach ($hl_params as $key => $value) {
			        $actuals[$key] = $value;
			}
			  
			// assign constants  
			foreach ($constants as $key => $value) {
				$actuals[$key] = $value;
			}

			return $actuals;
		}
		return false;
	}

   /**
    * map_actuals
    * given an array of paramaters attempt to map them to this route. A route
    * is considered a match under the following conditions:
    * 
    *   1) $this->strict = false and all of the variables specified in 
    *      $this->map exist in the $actuals array. (additional variables
    *      in $actuals that were not in $this->map are stored in 
    *      $this->remaining_actuals)
    *
    *   OR
    *
    *   2) $this->strict = true and all of the variables specified in 
    *      $this->map exist in the $actuals array and all variables in
    *      $actuals exist in $this->map. 
    *
    *  AND when the values of actuals are substituted into the map
    *  the resulting url fragment must match $this->map.
    *
    *  If the route matches then the following properties are set
    *  and true is returned.  
    *     $this->map_raw to the url_fragment 
    *     $this->map_encoded to the url_fragment but encoded for the browser 
    *     $this->actuals_remaining to an array of parameters that were not
    *         found in $this->map.
    *
    *  false is returned if there is no match
    */
    public function map_actuals( $actuals )
    {
        $map_raw = $this->map;
     
	$actuals_remaining = $actuals; 
	$map_encoded=$map_raw;
	foreach($actuals as $var => $val ) {
		if (is_array($val)) {
			//do something special?
		} else {
			$prevmap=$map_raw;
			$map_raw=str_replace( '{' . $var . '}',  $val , $map_raw ) ;
			if( $prevmap != $map_raw ) { 
				unset($actuals_remaining[$var]);
				$map_encoded = str_replace( '{'.$var.'}' , urlencode($val), $map_encoded );
			}
		}
	}
	
	// test if unmatched parameters exist
	if( ereg( '{[^}]+}', $map_raw ) ) {
	    return false; 
        }
      
	// strict match requires all actuals to be in the map
	if( $this->strict && count($actuals_remaining) ) {
	    return false; 
        }
	
	// verify that the generated route matches when actuals are plugged in  
	if( false === $this->match_to_request( $map_raw ) ) {
	    return false;
	}
	
	$this->map_raw           = $map_raw;
	$this->map_encoded       = $map_encoded;
	$this->actuals_remaining = $actuals_remaining;  

	return true;
    }

}



?>
