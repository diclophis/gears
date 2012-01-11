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
 * Name:     sql_implode_where<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Implodes an array, to create a where statement
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_sql_implode_where($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('function', 'implode_quote');
	//require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	//$order = $params['order'];

	$string = '';

	if( count($params['where']) == 0) {
		return $string;
	}
	
	$string = 'WHERE ';
	
	$error = false;
	
	$temp = array();
	
	$delimiter = ' AND ';
	
	foreach($params['where'] as $key => $where) {
		if( $where['col_name'] == '' ) {
			$error = true;
		}

		switch( $where['type']) {
			case 'IN':
				$temp['data'] = $where['data'];
				$string .= "`{$where['col_name']}` IN(" . smarty_function_implode_quote($temp, $smarty) . ")" . $delimiter;
				$temp = array();
				break;
			case 'EMPTY':
				$string .= "`{$where['col_name']}` = ''" . $delimiter;
				break;
			case 'NOTEMPTY':
				$string .= "`{$where['col_name']}` != ''" . $delimiter;
				break;
			case 'EQ':
				$string .= "`{$where['col_name']}` = '{$where['data']}'" . $delimiter;
				break;
			case 'NEQ':
			$string .= "`{$where['col_name']}` != '{$where['data']}'" . $delimiter;
				break;
			case 'GT':
				$string .= "`{$where['col_name']}` > '{$where['data']}'" . $delimiter;
				break;
			case 'GT_0':
				$string .= "`{$where['col_name']}` > '0'" . $delimiter;
				break;
			case 'GTE':
				$string .= "`{$where['col_name']}` >= '{$where['data']}'" . $delimiter;
				break;
			case 'LTE':
				$string .= "`{$where['col_name']}` <= '{$where['data']}'" . $delimiter;
				break;
			case 'LT':
				$string .= "`{$where['col_name']}` < '{$where['data']}'" . $delimiter;
				break;
			case 'LT_0':
				$string .= "`{$where['col_name']}` < '0'" . $delimiter;
				break;
			case 'BETWEEN':
				if($where['data']['min'] != '' && $where['data']['max'] != '') {
					$string .= "(`{$where['col_name']}` BETWEEN '{$where['data']['min']}' AND '{$where['data']['max']}')" . $delimiter;
				}
				elseif($where['data']['min'] == '' && $where['data']['max'] != '') {
					$string .= "`{$where['col_name']}` <= '{$where['data']['max']}'" . $delimiter;
				}
				elseif($where['data']['min'] != '' && $where['data']['max'] == '') {
					$string .= "`{$where['col_name']}` >= '{$where['data']['min']}'" . $delimiter;
				}
				else {
					// Set nothing
				}
				break;
			case 'ISNULL':
			$string .= "`{$where['col_name']}` IS NULL '{$where['data']}'" . $delimiter;
				break;
			case 'ISNOTNULL':
			$string .= "`{$where['col_name']}` IS NOT NULL '{$where['data']}'" . $delimiter;
				break;
			case 'LIKE':
			$string .= "`{$where['col_name']}` LIKE '{$where['data']}'" . $delimiter;
				break;
			case 'LIKEMOD':
			$string .= "`{$where['col_name']}` LIKE '{$where['data']}%'" . $delimiter;
				break;
			case 'MODLIKE':
			$string .= "`{$where['col_name']}` LIKE '%{$where['data']}'" . $delimiter;
				break;
			case 'MODLIKEMOD':
			$string .= "`{$where['col_name']}` LIKE '%{$where['data']}%'" . $delimiter;
				break;
		}
	}
	if(strlen($string) > 0) {
		$string = substr($string, 0, -strlen($delimiter));
	}
	else {
		$string = 'Ruh-Roh';
	}
	
	if( $error === true ) {
		$string = '';
	}

	return $string;
}		

//function smarty_function_sql_implode_where_in()
//{
//	require_once $smarty->_get_plugin_filepath('function', 'implode_quote');
//	//$html .= "\n" . smarty_function_implode_quote($info, $smarty);
//}
?>
