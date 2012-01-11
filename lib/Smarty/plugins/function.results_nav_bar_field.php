<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {results_nav_bar_field} function plugin
 *
 * Type:     function<br>
 * Name:     results_nav_bar_field<br>
 * Input:<br>
 *           - NA      - Not Applicable
 * Purpose:  Returns a table row enclosed in <tr></tr> tags.
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_results_nav_bar_field($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');

	$row 			= $smarty->get_template_vars('row');
	$results	= $smarty->get_template_vars('results');
	$nav 			= &$results['nav'];
	$fields 	= $smarty->get_template_vars('fields');
	
	$html = '';
	
	$sort_color = ($nav['sort_direction'] == 'ASC') ? 'background-color:#800080;' : 'background-color:#008080;';
	$sort_image = $fields['GEARS'][ 'nav_sort_' . strtolower($nav['sort_direction']) ];
	
	$html .= '<tr>';
	foreach($row as $field => $value)
	{
		if($field == $nav['sort_field'])
		{
			$html .= '	<td>';
			$html .= '		<table>';
			$html .= '	   <tr>';
			$html .= '      <td><input id="sort_' . $field . '" name="submit[sort][' . $field . '][' . $nav['sort_direction'] . ']" style="' . $sort_color . '" type="submit" value="' . $fields[ $field ]['label'] . '" /></td>';
			$html .= '      <td>' . $sort_image . '</td>';
			$html .= '	   </tr>';
			$html .= '		</table>';
			$html .= '	</td>';
		}
		else
		{
			$html .= '	<td>';
			$html .= '		<input id="sort_' . $field . '" name="submit[sort][' . $field . '][ASC]" class="submit_sort" type="submit" value="' . $fields[ $field ]['label'] . '" />';
			$html .= '	</td>';
		}
	}
	$html .= '	<td>';
	$html .= '&nbsp;';
	$html .= '	</td>';
	$html .= '</tr>';
	
	return $html;
	
	return smarty_function_escape_special_chars($query_string);
}		
?>
