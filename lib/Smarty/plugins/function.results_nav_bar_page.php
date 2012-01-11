<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {results_nav_bar_page} function plugin
 *
 * Type:     function<br>
 * Name:     results_nav_bar_page<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Returns the navigation page buttons enclosed in a table.
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_results_nav_bar_page($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	$row 			= $smarty->get_template_vars('row');
	$results	= $smarty->get_template_vars('results');
	$nav 			= &$results['nav'];
	$fields 	= $smarty->get_template_vars('fields');
	$URL_SUBACTION = $smarty->get_template_vars('URL_SUBACTION');

	$button = array();
	$button['first'] 		= array('label'=>$fields['GEARS']['nav_page_first'], 		'value'=>1);
	$button['previous'] = array('label'=>$fields['GEARS']['nav_page_previous'], 'value'=>$nav['previous']);

	for($i=1; $i<=$nav['pages']; $i++)
	{
		$button[ $i ] = array('label'=>$i, 'value'=>$i);
	}
	$button['next'] 		= array('label'=>$fields['GEARS']['nav_page_next'], 		'value'=>$nav['next']);
	$button['last'] 		= array('label'=>$fields['GEARS']['nav_page_last'], 		'value'=>($i - 1));

	//return '<pre>' . var_export($button, true) . '</pre>';

	$nav_page_range = nav_page_range($nav['page'], $nav['pages'], $fields['GEARS']['nav_page_wing']);
	//return '<pre>' . var_export($nav_page_range, true) . '</pre>';

	$html = '<table>';
	$html .= NL . '	<tr>';
	
	foreach($button as $key => $info)
	{
		/* Check to see if $key is a string or integer.
		 *
		 * Possible values: first, previous, {(integer): 1, 2, 3, ... n}, next, last
		*/
		if( is_int($key) )
		{
			$keyIsInteger = true;
			
			if($key >= $nav_page_range['start'] && $key <= $nav_page_range['stop'])
			{
				$displayPageButton = true;
			}
			else
			{
				$displayPageButton = false;
			}
		}
		else
		{
			$keyIsInteger = false;
		}

		if( ($keyIsInteger === false) || ($keyIsInteger === true && $displayPageButton === true) )
		{
			$sort_class = ' class="' . (($info['label'] == $nav['page']) ? $fields['GEARS']['nav_class_page_button'][0] : $fields['GEARS']['nav_class_page_button'][1]) . '" ';
			$sort_style = ' style="' . (($info['label'] == $nav['page']) ? $fields['GEARS']['nav_style_page_button'][0] : $fields['GEARS']['nav_style_page_button'][1]) . '" ';
			$html .= NL . '		<td><input id="nav_bar_page_' . $key . '" name="submit[page][' . $info['value'] . ']" ' . $sort_class . $sort_style . ' type="submit" value="' . $info['label'] . '" /></td>';
		}		
		
	}
	$html .= NL . '	</tr>';
	$html .= NL . '</table>';
	
	
	return $html;
}		

/**
 * This is a helper function to the primary Smarty function on this page.
 *
*/
function nav_page_range($page, $pages, $wing)
{
	$return = array();
	$return['page'] 	= $page;
	$return['pages'] 	= $pages;
	$return['wing'] 	= $wing;
	$return['start'] 	= $return['page'] - $return['wing'];

	if($return['start'] < 1)
	{
		$return['start'] = 1;
	}

	$return['stop'] 	= $return['start'] + 2 * $return['wing'];

	
	if($return['stop'] > $return['pages'])
	{
		$difference	= $return['stop'] - $return['pages'];

		$return['stop'] = $return['pages'];

		$return['start'] = $return['start'] - $difference;
	}

	if($return['start'] < 1)
	{
		$return['start'] = 1;
	}

	return $return;
}

?>
