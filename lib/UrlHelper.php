<?php

/*
	These helpers generate urls/links for use in a template

	{url_for controller="Something" action="wang" id="123"}

*/

final class UrlHelper extends Helper {

	public static function url_for ($params, &$smarty)
	{
		if (isset($params['http_host'])) {
			$http_host = $params['http_host'];
			unset($params['http_host']);
		} elseif ($smarty->rendering_for_email) {
			$http_host = true;
		} else {
			$http_host = false;
		}

		return Dispatcher::get_url($params, false, $http_host);
	}


	// for now, this does a very simple, stupid operation. 
	public static function base_url_for_js ($params, &$smarty)
	{
		return "/";
	}

	public static function static_url_for( $params, &$smarty )
	{
		//return Dispatcher::get_status('base_url') . $params['href'];
		return Dispatcher::get_url($params,false,false,true);
	}

}

?>
