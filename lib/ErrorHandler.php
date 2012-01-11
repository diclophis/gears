<?php

/*
	This is the error/exception handling code, it intercepts any errors and formats a nicer displaying message.
	When GEARS_ENV=production, it displays an even friendler message to the user, that instructs them to try again, or contact support
	And then logs the exception to the error_log
*/

class ErrorHandler {
	/*
		Installs the callbacks into phps error handling system
	*/

	public function __construct ()
	{
		$this->throw_interpreter_exceptions = Config::settings()->gears['throw_interpreter_exceptions'];
	}

	public static function install ()
	{
		$error_handler = new ErrorHandler;
		set_error_handler(array($error_handler, 'trap'));
		set_exception_handler(array($error_handler, 'trap'));
	}

	public static function gather_templates ($backtrace)
	{
		$return = array();
		if (is_array($backtrace)) {
			foreach($backtrace as $key => $value) {
				if (is_array($value)) {
					$return = array_merge($return, self::gather_templates($value));
				} elseif (is_object($value)) {
				} elseif ((strpos($value, "tpl") !== false) && (strpos($value, "templates_c") === false) && (strpos(trim($value), trim($key)) === false)) {
					$return[] = $value;
				}
			}
		}

		return $return;
	}

	/*
		All errors end up in here, various bits of info are collected, and then echoed/logged
	*/
	public function trap ($error, $message = null, $file = null, $line = null)
	{
		if ($error instanceof Exception) {
			while (ob_get_level()) {
				$buffer = ob_get_clean();
			}
			if ($error instanceof PDOException) {
				//print_r($error);
				//exit("\na database error has occured, see above\n");
				$wang =  new GearsException($error->getMessage());
				$wang->inner_trace = $error->getTraceAsString();
				exit($wang);
			} else {
				exit($error);
			}
		} else {
			if (error_reporting() == 0) {
				return;
			} else {
				if (strpos($file, "tpl") !== false) {
					$backtrace = (debug_backtrace());
					$message .= " in one of these templates: ";
					$templates = array_unique(self::gather_templates($backtrace));
					$message .= implode($templates, " or ");
					throw new SmartyException($message, $error);
				} else {
					$new_message = sprintf("%d:%s in %s on line %d", $error, $message, $file, $line);
					if ($this->throw_interpreter_exceptions) {
						throw new InterpreterException($new_message, $error);
					} else {
						exit($new_message);
					}
				}
			}
		}
	}
}

class GearsException extends Exception {
	public $log_as_fatal = true;
	//TODO: maybe clear out lockfile?
	public $web_format = ""; 
	public $cli_format = ""; 
	public $inner_trace = null;
	/*
		Specifies a error message format, based on if the application is CLI or not
	*/
	public function __construct($message, $code = 0)
	{
		parent::__construct($message, $code);
		$this->message = $message;
		$this->code = $code;
		$this->cli_format = "A %s has occurred: %s in %s line: %s\n\n%s\n\n%s\n\n%s\n\n";
		switch ($_SERVER['GEARS_ENV']) {
			case 'xjbardin':
			case 'production':
			case 'stage':
				$this->web_format = str_pad("<html><body><h1>Error</h1></body></html>", 513);
			break;

			default:
				$this->web_format = "<html><body><h1>A %s has occured:</h1><b>%s</b> in <b>%s</b> line: <b>%s</b><hr/><h1>this is the backtrace:</h1><pre>%s</pre><hr/><h1>for this request:</h1><pre>%s</pre><hr/><h1>on this server:</h1><pre>%s</pre></body></html>";
			break;
		}
	}

	public function getTheTrace ()
	{
		if ($this->inner_trace) {
			return $this->inner_trace;
		} else {
			return $this->getTraceAsString();
		}
	}

	public function __toString() 
	{
		$message = $this->getMessage();
		$file = $this->getFile();
		$line = $this->getLine();
		$backtrace = $this->getTheTrace();
		$request = print_r($_REQUEST, true);
		$server = print_r($_SERVER, true);
		$exception_type = get_class($this);
		$header_message = strip_tags(str_replace(array("\n", "\r", "\t"), "", $message));
		$web_message = sprintf($this->web_format, $exception_type, $message, $file, $line, $backtrace, $request, $server);
		$cli_message = sprintf($this->cli_format, $exception_type, $message, $file, $line, $backtrace, $request, $server);
		if ($this->log_as_fatal) {
			Log::fatal($cli_message);
		} else {
			Log::notice($cli_message);
		}
		if (ApplicationEntryPoint::is_web()) {
			$this->header($header_message);
			return $web_message;
		} elseif (ApplicationEntryPoint::is_cli()) {
			return $cli_message;
		} else {
      return "unknown entry point: " . $cli_message;
    }
	}

	public function header ($header_message)
	{
		header(sprintf("HTTP/1.0 500 %s", $header_message));
	}
}

class InterpreterException extends GearsException {
}

class SmartyException extends GearsException {
}

class FileCSVException extends GearsException {
}

class FileNotFoundException extends GearsException {
	public $log_as_fatal = false;
	public function __construct($params)
	{
		parent::__construct("File Not Found", 0);
		$this->html_view = new HtmlView();
		$this->web_format = $this->html_view->display("shared/404", null, array("params" => $params));
		//$this->web_format = str_pad("<html><body><h1>File Not Found</h1></body></html>", 513);
	}

	public function header ($header_message)
	{
		header(sprintf("HTTP/1.0 404 %s", $header_message));
	}
}

class SchemaInfoMissingException extends GearsException {
	public function __construct($message = null, $code = 0)
	{
		parent::__construct($message, $code);
	}

	public function __toString ()
	{
		return "schema_info table missing\n";
	}
}

?>
