<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {results_nav_bar} function plugin
 *
 * Type:     function<br>
 * Name:     results_nav_bar<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Returns an HTML dropdown menu of the number of rows to display for searching.
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_results_nav_rows_dropdown($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');
	require_once $smarty->_get_plugin_filepath('function', 'html_options');

	$fields	= $smarty->get_template_vars('fields');
	$results	= $smarty->get_template_vars('results');
	$nav 			= $results['nav'];

	$nav_rows = $fields['GEARS']['nav_rows'];
	//return '<pre>' . var_export($nav_rows, true) . '</pre>';
	
	foreach($nav_rows as $key => $string)
	{
			$test = explode(':',$string);
			$label = strtok($nav_rows, ":");
			$value = strtok(":");
			$options[ $test[0] ] = $test[1];
	}
	
	$params = array();
	
	$params['name'] = 'gears[choose_rows]';
	$params['options'] = $options;
	$params['selected'] = $nav['rows'];
	
	return smarty_function_html_options($params, $smarty);
	
}		
?>
