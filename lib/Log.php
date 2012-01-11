<?php

/*
	This is the "logging" system, it has varius levels of logging, and is typically called from anywhere in the application like this:
	Log::debug("something")

	It can be configured to echo the log, or append to a log file in the ini files
*/

class Log {

	static $settings;
	
	public static function debug ($message)
	{
		$debug = debug_backtrace();
		$prefix = basename($debug[0]['file'], ".php") . $debug[1]['type'] . $debug[1]['function'] . " ";
		self::write(LOG_DEBUG, $message, $prefix);
	}

	public static function notice ($message)
	{
		self::write(LOG_NOTICE, $message);
	}

	public static function error ($message)
	{
		self::write(LOG_ERR, $message);
	}

	public static function fatal ($message)
	{
		self::write(LOG_CRIT, $message);
	}

	public static function write ($level, $message, $prefix = "")
	{
	 	if (is_array($message)) {
			$message = print_r($message, true);
		} elseif (is_object($message)) {
			if( method_exists( $message, '__toString' ) )
				$message = (string) $message;
			else
				$message = get_class($message).'('.print_r( get_object_vars( $message ),true ) . ')';
		}

		$message .= "\n";
		if (self::$settings['log_level'] >= $level) {
			if (self::$settings['log_to_email']) {
				if ($level == LOG_CRIT) {
					//$headers  = 'MIME-Version: 1.0' . "\r\n";
					//$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					error_log($message, 1, self::$settings['log_email']);
					//, $headers);
				}
			}

			$x=explode("\n", str_replace("\n\r","\n", trim($message)) );
			foreach ($x as $line_no => $single_line) 
            {
                if ( (strlen($single_line) > 500) && (strpos($single_line, "|") !== false) )
                {
                    $pipeline = explode("|", $single_line);
                    foreach ($pipeline as $single_pipe)
                    {
                        error_log($prefix."----".$single_pipe);
                    }
                }
                else if ( (strlen($single_line) > 500) && (strpos($single_line, ",") !== false) )
                {
                    $commaline = explode(",", $single_line);
                    foreach ($commaline as $single_comman)
                    {
                        error_log($prefix."----".$single_comman);
                    }
                }
                else
                {
                    error_log($prefix.$single_line);
                }
			}
		}
	}

	public static function install ()
	{
		self::$settings = Config::settings()->logging;
	}
}

?>
