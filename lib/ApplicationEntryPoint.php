<?php

/*
	This interface determines what happens when a part of an application is started, be it from a http request, or cli batch process
*/

abstract class ApplicationEntryPoint {

	/*
		Used to determine if the current running env. is CLI or not (switches stuff in the error reporting and logging)
	*/
	static public function is_cli ()
	{
		$sapi_name = php_sapi_name();
		return $sapi_name == 'cli';
	}

	static public function is_web ()
	{
		$sapi_name = php_sapi_name();
		return ($sapi_name == 'apache2handler' || $sapi_name == 'apache');
	}

	/*
		Start the gears engine, by installing the various handlers... Logging, Errors, Session if its a web request, and connect the Model database
	*/
	static protected function load ($for_migrations = false) {
		Log::install();
		ErrorHandler::install();
		if (!self::is_cli()) {
			Session::install();
		}
		Routes::load();
		Model::connect($for_migrations);
	}
}

?>
