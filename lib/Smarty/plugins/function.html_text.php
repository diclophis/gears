<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {html_text} function plugin
 *
 * Type:     function<br>
 * Name:     html_text<br>
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
function smarty_function_html_text($params, &$smarty)
{
	//require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');
	/*
	$name			= ' name="' . $params['name'] . '" ';
	$value 		= ' value="' . $params['value'] . '" ';
	$style 		= ' style="' . $params['style'] . '" ';
	$class 		= ' class="' . $params['class'] . '" ';
	$onsubmit = ' onsubmit="' . $params['onsubmit'] . '" ';
	$onclick 	= ' onclick="' . $params['onclick'] . '" ';
	*/
	
	$string = '';
	
	foreach($params as $key => $value)
	{
		$string .= ' ' . $key . '="'. $value . '"';
	}
	
	$html = '<input type="text"' . $string . ' />';
	
	return $html;
}		
?>
