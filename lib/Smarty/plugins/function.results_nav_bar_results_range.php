<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {results_nav_bar_results_range} function plugin
 *
 * Type:     function<br>
 * Name:     results_nav_bar_results_range<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Returns a string like 'Results 1 - 10 of 12'
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_results_nav_bar_results_range($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	$results	= $smarty->get_template_vars('results');
	$nav 			= &$results['nav'];
	$fields 	= $smarty->get_template_vars('fields');
	//return '<pre>' . var_export($fields, true) . '</pre>';
	
	if($nav['page_first'] == $nav['page_last'])
	{
		$html = 'Results ' . $nav['page_first'] . ' of ' . $nav['all'];
	}
	else
	{
		$html = 'Results ' . $nav['page_first'] . ' - ' . $nav['page_last'] . ' of ' . $nav['all'];
	}
	
	return $html;
}
?>
