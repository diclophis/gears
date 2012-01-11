<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {nav_hidden_form_elements} function plugin
 *
 * Type:     function<br>
 * Name:     nav_hidden_form_elements<br>
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
function smarty_function_results_nav_hidden_form_elements($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');
	require_once $smarty->_get_plugin_filepath('function', 'html_hidden');
	
	$results	= $smarty->get_template_vars('results');
	$nav 			= &$results['nav'];
	
	$html = '';
	
	foreach($nav as $name => $value)
	{
		//$html .= "\n" . '<input type="hidden" name="nav[' . $name .']" value="' . $value .'" />';
		$html .= "\n" . smarty_function_html_hidden(array('name'=>'nav[' . $name .']','value'=>$value), $smarty);
	}
	
	return $html;
}		
?>
