<?php

/*
	This is the base class for all Helper classes.
	Helper classes are loaded as plugins into the HtmlView/Smarty rendering engine.
	Thay are saved in
		app/
			helpers/
				ApplicationHelper.php (base helper for a given application)
				WangHelper.php (helper thats loaded into all requests dispatched through WangController)

	There are 3 types of methods

	Block helpers
		function a_block_helper ($params, $content, &$smarty, &$repeat)
		Are used to "wrap" up content

	Simple Helpers
		function a_standard_helper ($params, &$smarty)
		return a string

	Filter Helpers
		function a_filter_helper ($input)
		modified just a value

	more info here: http://www.smarty.net/manual/en/plugins.php 

*/

class Helper {
	function parse_macros ($params, &$smarty)
	{ 
		$input = $params['for'];
		unset($params['for']);
		$strip_tags = false;
		if (isset($params["strip_tags"])) {
			$strip_tags = $params["strip_tags"];
			unset($params["strip_tags"]);
		}

		$length = null;
		if (isset($params["truncate"])) {
			$length = $params["truncate"];
			unset($params["truncate"]);
		}
		
		$return = MacroEngine::parse_macros_in_php($input, $params);
		if ($strip_tags) {
			$return = strip_tags($return);
		}

		if ($length) {
			$return = $this->neo_truncate($return, $length);
		}

		return $return;
	}

	function neo_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
	{
		if ($length == 0)
			return '';

		if (strlen($string) > $length) {
			$length -= min($length, strlen($etc));
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
			}
			if(!$middle) {
				return substr($string, 0, $length) . $etc;
			} else {
				return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
			}
		} else {
			return $string;
		}
	}
}

?>
