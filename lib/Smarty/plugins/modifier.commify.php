<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty commify modifier plugin
 *
 * Type:     modifier<br>
 * Name:     commify_number<br>
 * Purpose:  format numbers with thousands separator<br>Invoke as {commify_number($number)}
 * Input:<br>
 *         - string: input number
 * @param string
 * @return string|void
 * @uses php number_format()
 */
function smarty_modifier_commify($number)
{
    if ($number < 1000)
        return $number;
    else
        return  number_format($number);
}

?>
