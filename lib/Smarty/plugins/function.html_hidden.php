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
 * Name:     html_hidden<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Creates hidden form elements for controlling navigation and pagination.
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_html_hidden($params, &$smarty)
{
	$string = '';
	
	foreach($params as $key => $value)
	{
		$string .= ' ' . $key . '="'. $value . '"';
	}
	
	$html = '<input type="hidden"' . $string . ' />';
	
	return $html;
}		
?>
