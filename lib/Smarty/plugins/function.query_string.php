<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {query_string} function plugin
 *
 * Type:     function<br>
 * Name:     query_string<br>
 * Input:<br>
 *           - var      - string The variable to replace
 *           - val      - string The value of the variable to replace
 * Purpose:  Replaces QUERY_STRING values for web links
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_query_string($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	$var = $params['var'];
	$val = $params['val'];
	
	$query_string = $_SERVER["QUERY_STRING"];
	
	if(strstr($query_string, $var)) 
	{
		$query_string =  preg_replace("/$var=[\\d\\w]*/", "$var=$val", $query_string);
	} 
	elseif($query_string != '') 
	{
		$query_string = $query_string . "&" . $var . "=" . $val;
	} 
	else 
	{
		$query_string = $query_string . $var . "=" . $val;
	}
	
	return smarty_function_escape_special_chars($query_string);
}		
?>
