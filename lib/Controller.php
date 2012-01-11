<?php

class Controller {

	public $helper = null;

	public function __construct ($class_name, $action_name, $parameters)
	{
		$this->current_controller = Dispatcher::get_status('current_controller');
		$this->current_action = $action_name;
		$this->session = new Session();
		$this->params = $parameters;
		$this->files = $_FILES;

		$this->html_view = new HtmlView();

		$class = get_class($this);
		$helper_classes[] = sprintf("%sHelper", str_replace("Controller", "", $class));
		$helper_classes[] = "ApplicationHelper";

		foreach ($helper_classes as $helper_class) {
			if (class_exists($helper_class)) {
				$this->helper = $this->html_view->register_helper_class($helper_class);
				break;
			}
		}

	}

	/*
		This is a callback function that will be called each time any method of the controller is called.
		This method may be called multiple times while the dispatcher is searching for a method that returns a response.
		It is designed to be overriden by a subclass and does nothing by default. 
	*/
	public function before_call ($method_name) {}
	
	/*
		After a method sucessfully responds to a requested url, this method is called.
		It is designed to be overriden by a subclass and does nothing by default.
	*/
	public function after_call ($response, $method_name) {}

	/*
		Renders the given templates and retrns the result.
		Templates are typically divided into 3 main groups
			"main" templates are used to control layout, and include the most common elements of a set of pages
			"content" templates are typically action specific, they are also optional if the "main" template suffices
			"partial" templates are smaller more specific bits of html that are repeated often, and or returned as the result of an ajax request

		Templates are contained in the app/views directory which is typically broken down like this
		app/
			views/
				admin/ stuff for AdminController
				listings/ stuff for ListingsController
				shared/ shared stuff used for any controller


		It is typicaly called at the end of an action and is the return value for that action

		...in WangController
		public function chung ()
		{
			return $this->render('shared/vanilla', 'wang/chung');
		}

		*note the .tpl for the template names is appended automagically
	*/
	final public function render ($main_template, $content_template = null)
	{
		$variables = get_object_vars($this);
		$variables["is_ajax"] = $this->is_ajax();
		$variables["is_mobile"] = $this->is_mobile();
		
		return $this->html_view->display($main_template, $content_template, $variables);
	}

	/*
		These three function is_post, is_get, is_ajax are called to determine if a given request is of a particular type.
		This is useful where you want to do something different within an action, depending on if its a POST or GET request
	*/
	public function is_post ()
	{
		return Dispatcher::get_request_method() == "post";
	}

	public function is_get ()
	{
		return Dispatcher::get_request_method() == "get";
	}

	public function is_ajax ()
	{
		return Dispatcher::get_request_method() == "ajax";
	}
	
    /**
     * @return string - the type of phone we have (iPhone, BlackBerry, etc), or false if not a known cellphone
     * Examines the user_agent from the system to see if we can pick out a phone type.
     */
    public function is_mobile ()
    {
        $UA = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' ;
        $known_mobiles = array(
                                'iPhone'     => 'iphone',
                                'iPod'       => 'ipod',
                                'BlackBerry' => 'blackberry',
                                'Mobile'     => 'generic'
                              );
            
        foreach ($known_mobiles as $test_key => $value)
        {
            if (stripos($UA, $test_key) !== false) 
            {
                return $value;
            }
        }
        
        // this block will force a return of blackberry in the case where it's not really a phone....
        if (Config::settings()-> gears['test_mobile'])
            return 'blackberry';
        
        // if we couldn't identify the accessing object as a phone, then return false
        return false;
    }
	
	
	/*
		Renders $data_to_encode as JSON with the appropriate headers, outputs this to the browser and terminates the current request.
	*/
	final protected function render_to_json ($response_data, $include_response_data_in_header = false) {
		if (function_exists('json_encode')) {
			$output = json_encode($response_data);
		} else {
			throw new GearsException('No function or class found to render JSON data.');
		}

		if ($include_response_data_in_header) {
			header('X-JSON: ('.$output.')');
		}

		if ($this->is_ajax()) {
			header('Content-type: application/json');
		}

		return $output;
	}
	
	/*
		Redirects to another method, and terminates the current request.
		Putting the word "Controller" at the end of each controller name is optional.
		It takes the same parameters as Dispatcher::get_url

		...in WangController
		public function chung ()
		{
			return $this->redirect(array("controller" => "Wang", "action" => "other_chung"));
		}
	*/
	final public function redirect ($parameters_or_url)
	{
		if (is_array($parameters_or_url)) {
			$url = Dispatcher::get_url($parameters_or_url);
		} else {
			$url = $parameters_or_url;
		}

        // Session/cookie handling. PHP's transparent session handling breaks down on redirects,
        //   so we need to put the session id onto the URL here. Let's be smart, though - don't
        //   put it there if the cookie mechanism is working. Get the name of the session cookie,
        //   and see if it is there. If not, THEN append the SID to the session.
        $session_name = Config::settings()->php['session.name'];
        if (!isset($_COOKIE[$session_name]))
        {
            if (strpos($url, "?") !== false)
                $url .= "&" . SID;
            else
                $url .= "?" . SID;

        } // if there is no session cookie...

		return header("Location: ".$url);
	}

        /**
         * @param string $file Local file name
         * @param array $headers headers to send to browser.
         *      ex array('Content-Type'=>'image/gif',  )
         * @return true on success or false on error
         */
         final public function sendfile ( $file, $headers )
         {
         	if(!file_exists($file))
         		return false;
		
		foreach( $headers as $var => $val ) {
			header( "$var: $val" );
		}
         
		// add Content-Length header if not present
               if( ! array_key_exists( 'Content-Length', $headers ) ) {
               		$size = filesize($file);
               		header('Content-Length: '.$size);
	       }
                                                                                                                                                                                                                                                                     
               return file_get_contents( $file );
         }
         
	/*
		port() and host() are used in the event that this is an RpcController
	*/
	final protected function port ()
	{
		return intval($_SERVER['SERVER_PORT']);
	}

	final protected function host ()
	{
		return $_SERVER['HTTP_HOST'];
	}

	final protected function referer ()
	{
		return $_SERVER['HTTP_REFERER'];
	}


	/*
		This is a shortcut method for getting a param from a request, or if it doesnt exist in the request parameters, using a default value
	*/
	final public function param ($param_key, $default = "")
	{
		if (isset($this->params[$param_key])) {
			return $this->params[$param_key];
		} else {
			return $default;
		}
	}

	/*
		get_messages, set_messages, get_message_for. set_message_for are used as a "flash" mechinism for transfering chunks of text between pages, possibly through redirects
		
		...in WangController
			public function agent ()
			{
				if ($this->is_post()) {
					if ($this->agent->save()) {
						//set the message and then redirect
						$this->set_message_for("confirmation", "Your information has been succesfully updated");
						return $this->redirect(array("controller" => "Admin", "action" => "agent", "acnt" => $this->agent->acnt));
					}
				}
				//grab the message, or blank
				$this->confirmation_message = $this->get_message_for("confirmation");
				return $this->render("admin/master");
			}
	*/
	final public function get_messages ()
	{
		if (isset($this->session['messages'])) {
			$messages = $this->session['messages'];
		} else {
			$messages = array();
		}
		return $messages;
	}

	final public function set_messages ($messages)
	{
		$this->session['messages'] = $messages;
		return true;
	}

	final public function get_message_for ($message_for)
	{
		$return = null;
		$messages = $this->get_messages();
		if (isset($messages[$message_for])) {
			$return = $messages[$message_for];
			unset($messages[$message_for]);
		}
		$this->set_messages($messages);
		return $return;
	}

	final public function set_message_for ($message_for, $message)
	{
		$messages = $this->get_messages();
		$messages[$message_for] = $message;
		$this->set_messages($messages);
		return true;
	}

	/*
		set_return_point and return_to are part of the "tunnel" system for handling authentication requests
		in the restricted actions before_call you set_return_point to the current action (or another action)
		then you can bounce the user somewhere and possible through a form/login and then return_to() the previously set point
	*/
	final public function set_return_point ($key, $params)
	{
		unset($this->params["PHPSESSID"]);
		$key = sprintf("goto_url_for_%s", $key);
		$this->session[$key] = Dispatcher::get_url($params);
	}

	final public function return_to ($key, $fallback_params)
	{
		$key = sprintf("goto_url_for_%s", $key);
		if (isset($this->session[$key])) {
			$return = $this->redirect($this->session[$key]);
			unset($this->session[$key]);
			return $return;
		} else {
			return $this->redirect($fallback_params);
		}
	}

	final public function has_upload_for ($filename)
	{
		if (isset($this->files[$filename])) {
			return ((intval($this->files[$filename]['size']) > 0));
		}

		return false;

	}

	final public function attach_file_to ($model, $filename, $allowed_mime_types = array(), $custom_move_method = false)
	{
		$src = $this->files[$filename]['tmp_name'];
		$mime_type_cmd = (sprintf("%s %s", Config::settings()->gears['file_cmd'], escapeshellarg($src)));
		Log::debug($mime_type_cmd);
		$mime_type = trim(shell_exec($mime_type_cmd));
		foreach ($allowed_mime_types as $allowed_mime_type) {
			if (strpos($mime_type, $allowed_mime_type) === 0) {
				$attachment_details = $this->files[$filename];
				$attachment_details['scanned_mime_type'] = $mime_type;
				$dst = $model->get_attachment_destination_for($filename, $attachment_details);
				Log::debug($dst);
				if ($custom_move_method) {
					$moved = (call_user_func(array($model, $custom_move_method), $src, $dst));
					if ($moved) {
						Log::debug($moved);
						return $dst;
					}
				} else {
					if (move_uploaded_file($src, $dst)) {
						return $dst;
					}
				}
			}
		}
		return false;
	}

}

?>
