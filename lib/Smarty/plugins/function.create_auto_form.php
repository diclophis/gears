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
function smarty_function_create_auto_form($params, &$smarty)
{
	require_once $smarty->_get_plugin_filepath('function', 'create_form_element');
	require_once $smarty->_get_plugin_filepath('function', 'create_form_element_label');
	require_once $smarty->_get_plugin_filepath('function', 'html_hidden');

	$error['FIELD_DOES_NOT_EXIST'] = '<-- :: create_form_element(' . $params['field'] . ') :: Field does not exist.' . '-->';
	
	$form	= $smarty->get_template_vars('form');
	
	$html  = '<form id="' . $form['id'] . '" action="' . $form['action'] . '" method="' . $form['method'] . '">';

	foreach($form['_hidden_'] as $key => $value)
	{
		$info = array(
			'id'		=> $form['id'] . PS . '_hidden_' . PS . $key,
			'name'	=> $form['id'] . '[' . '_hidden_' . ']' . '[' . $key . ']',
			'value'	=> $value,
		);
		$html .= "\n" . smarty_function_html_hidden($info, $smarty);
	}

	foreach($form['fields'] as $field => $field_info)
	{
		$html .= "\n" . smarty_function_create_form_element_label( array('field'=>$field), $smarty);
		$html .= "\n" . smarty_function_create_form_element( array('field'=>$field), $smarty);
		if($field_info['type'] != '' && $field_info['type'] != 'hidden') $html .= "\n" . '<hr />';
	}
	
	$html .= "\n" . '<input type="submit" name="' . $form['id'] . '[' . '_submit_' . ']' . '[' . $key . ']' . '" value="Submit">';

	$html .= "\n" . '</form>';
	
	//return 'haha';
	return $html;
	
}
?>
