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
function smarty_function_sql_implode_limit($params, &$smarty)
{
	//require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	$limit = $params['limit'];

	$string = '';
	
	$error = false;
	
	if( !is_int($limit['offset']) ) {
		$error = true;
	}
	if( !is_int($limit['row_count']) ) {
		$error = true;
	}

	if( $error === false ) {
		$string = "LIMIT {$limit['offset']}, {$limit['row_count']}";
	}
	
	return $string;
}		
?>
