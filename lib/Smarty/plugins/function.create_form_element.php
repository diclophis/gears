<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
*/

/**
 * Smarty {create_form_element} function plugin
 *
 * Type:     function<br>
 * Name:     create_form_element<br>
 * Input:<br>
 *           - field      - the field name of the element.
 * Purpose:  Creates form elements from a Smarty Variable $form..
 *
 * @author Jeremy Postlethwaite <jeremyp@cisdata.net>
 * @param array
 * @param Smarty
 * @return string
 * @uses smarty_function_escape_special_chars()
*/
function smarty_function_create_form_element($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('shared','escape_special_chars');
	require_once $smarty->_get_plugin_filepath('function', 'html_label');
	require_once $smarty->_get_plugin_filepath('function', 'html_hidden');
	require_once $smarty->_get_plugin_filepath('function', 'html_text');
	require_once $smarty->_get_plugin_filepath('function', 'html_options');
	require_once $smarty->_get_plugin_filepath('function', 'html_radios');
	require_once $smarty->_get_plugin_filepath('function', 'html_checkboxes');
	
	$field = $params['field'];
	unset($params['field']);
	
	$error['FIELD_DOES_NOT_EXIST'] = '<-- :: create_form_element(' . $field . ') :: Field does not exist.' . ' -->';
	
	$form	= $smarty->get_template_vars('form');

	if( isset($form['fields'][ $field ]) )
	{
		$my_field = $form['fields'][ $field ];
	}
	else
	{
		return $error['FIELD_DOES_NOT_EXIST'];
	}
	$params['id'] 		= $my_field['id'];
	$params['name'] 	= $my_field['name'];
	$params['value'] 	= $my_field['value'];
	
	$html = '';

	if($my_field['type'] == '')
	{
		$html = '';
	}
	if($my_field['type'] == 'text')
	{
		$html = smarty_function_html_text($params, $smarty);
	}
	elseif($my_field['type'] == 'hidden')
	{
		$html = smarty_function_html_hidden($params, $smarty);
	}
	else
	{
		$html = smarty_function_html_hidden($params, $smarty);
	}
	
	return $html;
}		
?>
