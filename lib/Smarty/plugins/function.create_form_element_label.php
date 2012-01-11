<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {create_form_element_label} function plugin
 *
 * Type:     function<br>
 * Name:     create_form_element_label<br>
 * Input:<br>
 *           - field      - the field name of the element.
 * Purpose:  Creates form labels from a Smarty Variable $form..
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
*/
function smarty_function_create_form_element_label($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('function', 'html_label');

	$error['FIELD_DOES_NOT_EXIST'] = '<-- :: create_form_element(' . $params['field'] . ') :: Field does not exist.' . '-->';
	
	$form	= $smarty->get_template_vars('form');

	if( isset($form['fields'][ $params['field'] ]) )
	{
		$my_field = $form['fields'][ $params['field'] ];
	}
	else
	{
		return $error['FIELD_DOES_NOT_EXIST'];
	}

	if($my_field['type'] == '')
	{
		return '';
	}
	
	return smarty_function_html_label(array('for'=>$my_field['id'],'label'=>$my_field['label']), $smarty);
	
}
?>
