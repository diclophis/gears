<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {html_hidden} function plugin
 *
 * Type:     function<br>
 * Name:     implode_quote<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Implodes an array, separated by a delimiter and quotes it.
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_implode_quote($params, &$smarty)
{
	//require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	$data					= $params['data'];
	$data_raw					= $params['data_raw'];
	$encapsulate	= ( isset($params['encapsulate']) ) ? $params['encapsulate'] : "'";
	$delimiter	= ( isset($params['delimiter']) ) ? $params['delimiter'] : ",";

	$string = '';
	
	$quote = array();
	
	if( is_array($encapsulate) === false ) {
		$quote[0] = $encapsulate;
		$quote[1] = $encapsulate;
	}
	else {
		$quote = $encapsulate;
	}	

	for($i = 0; $i < count($data); $i++) {
		$string .= $quote[0] . $data[ $i ] . $quote[1] . $delimiter;
	}

	for($i = 0; $i < count($data_raw); $i++) {
		$string .= $data_raw[ $i ] . $delimiter;
	}

	$string = substr($string, 0, -strlen($delimiter));
	
	return $string;
}		
?>
