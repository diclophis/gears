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
function smarty_function_sql_implode_order($params, &$smarty)
{
	//require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	//$order = $params['order'];

	$string = 'ORDER BY ';
	
	$error = false;
	
	$delimiter = ', ';
	
	foreach($params['order_priority'] as $key => $order) {
		if( $order['col_name'] == '' ) {
			$error = true;
		}
		$string .= "{$order['col_name']} {$order['expr']} {$order['position']}" . $delimiter;
	}
	
	foreach($params['order'] as $key => $order) {
		if( $order['col_name'] == '' ) {
			$error = true;
		}
		$string .= "{$order['col_name']} {$order['expr']} {$order['position']}" . $delimiter;
	}
	
	$string = substr($string, 0, -strlen($delimiter));

	if( $error === true) {
		$string = '';
	}
	if( count($params['order_priority']) == 0 && count($params['order']) == 0 ) {
		$string = '';
		//$string = 'order_priority: ' . count($params['order_priority']) . ' :: order: ' . count($params['order']);
	}

	return $string;
}		
?>
