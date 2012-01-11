<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {html_label} function plugin
 *
 * Type:     function<br>
 * Name:     html_label<br>
 * Input:<br>
 *           - for      - the id of the form element.
 *           - label    - the label text of the form.
 * Purpose:  Creates a label for a form element.
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
*/
function smarty_function_html_label($params, &$smarty)
{
	
	$html = '<label for="'. $params['for'] . '" >' . $params['label'] . '</label>';
	
	return $html;
}		
?>
