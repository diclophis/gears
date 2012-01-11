<?php

/**
	This system manages and reads the application config files.
	@package Gears
*/
class Config implements ArrayAccess {
	private $__settings__ = array();
	static protected $__singelton__ = null;
	private $settings_file = null;
	private $env_settings_file = null;

	function __construct ($settings = null)
	{
		if ($settings === null) {
			$this->settings_file = $_SERVER['COMP_ROOT'].'/config/environment.ini';
			$this->env_settings_file = $_SERVER['COMP_ROOT'].'/config/environments/'.$_SERVER['GEARS_ENV'].'.ini';
			$settings = parse_ini_file($this->settings_file, true);
			$env_settings = parse_ini_file($this->env_settings_file, true);
			if (count(array_diff(array_keys($env_settings), array_keys($settings))) > 0) {
				error_log(sprintf("there are settings in %s not found in %s", $this->env_settings_file, $this->settings_file));
			}
			foreach ($env_settings as $section => $override_settings) {
				$settings[$section] = array_merge($settings[$section], $override_settings);
			}
			foreach ($settings as $section => $settings) {
				if (empty($settings)) {
					//throw new GearsException(sprintf("Invalid INI file section: (%s)", $section));
					error_log(sprintf("no entries for %s", $section));
				} else {
					$this->$section = new Config($settings);
					//Special cases for the [php] context, attempt to set the ini directive as if the setting were in php.ini
					if ($section == 'php') {
						foreach ($settings as $key => $value) {
							$ini_set = ini_set($key, $value);
							//if (empty($ini_set)) {
							//	throw new Exception(sprintf("unable to ini_set %s = %s", $key, $value));
							//}
						}
					}
				} 
			}
		} else {
			$this->__settings__ = $settings;
		}
	}

	/*
		This is a singelton access method, and allows for retreival of config settings with this syntax
		Config::settings()->some_section['some_value'];
	*/
	public static function settings ()
	{
		if (self::$__singelton__ == null) {
			self::$__singelton__ = new Config();
		}
		
		return self::$__singelton__; 
		
	}

	/*
		here on out are the methods used to implement ArrayAccess,
		there are special cases for when the context isnt found (throws an exception)
		and or if a setting isnt set (returns null, rather than throwing an error)
	*/
	function __get ($key)
	{
		throw new GearsException(sprintf("Gears::config()->%s is not defined in either %s, or %s", $key, $this->settings_file, $this->env_settings_file));
	}

	final public function toArray() {
		return $this->__settings__;
	}
	
	final public function offsetExists($key){
		return (array_key_exists($key, $this->__settings__));
	}
	
	final public function offsetGet ($key) {
		if ($this->offsetExists($key)) {
			return $this->__settings__[$key];
		} else {
			return null;
		}
	}
	
	final public function offsetSet($key,$value)
	{
		throw new GearsException("Config settings should not be modified directly");
	}
	
	final public function offsetUnset($key)
	{
		throw new GearsException("Config settings should not be modified directly");
	}
}

?>
