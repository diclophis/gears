<?php

class Cookies implements ArrayAccess {

	function __construct ()
	{
	}

	function offsetExists ($key)
	{
		return array_key_exists($key, $_COOKIE);
	}

	function offsetGet ($key)
	{
		if ($this->offsetExists($key, $_COOKIE)) {
			return $_COOKIE[$key];
		} else {
			return null;
		}
	}

	function offsetSet ($key, $value)
	{
		$expire = null;

		if (is_array($value)) {
		} else {
		}

		
	}

	function offsetUnSet ($key)
	{
		$_COOKIE[$key] = null;
		unset($_COOKIE[$key]);
	}
}

?>
