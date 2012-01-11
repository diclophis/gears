<?php

class Validate 
{
    const phone_regex = "/^[\d]*[ -]*[\(]*([\d]{3})[\)]*[ \.\-]*([\d]{3})[ \.\-]*([\d]{4})[ ]*[(x|ext|ex|\/)]*[\. :]*([0-9]*)$/";
    const email_regex = "/^([0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})$/";
    const short_date_regex = "/^(01|1|02|2|03|3|04|4|05|5|06|6|07|7|08|8|09|9|10|11|12)\/([0-9]{1,2})$/";
    const password_regex = "/^([\$a-z0-9._-])+$/i";
    const min_url_regex = "/(http|https):\/\/([\w-\.]+[\.]*)/";
    const domain_regex = "/^[A-Za-z0-9.-]+$/i";

    const CC_TYPE_VISA       = 'VISA';
    const CC_TYPE_AMEX       = 'AMEX';
    const CC_TYPE_MASTERCARD = 'MC';
    const CC_TYPE_DISCOVER   = 'DISC';
    

	public static function is_blank ($string)
	{
		return (strlen(trim($string)) == 0);
	}
	
	public static function is_valid_email ($string)
	{
		return preg_match(self::email_regex, $string);
	}

	public static function is_valid_phone ($string)
	{
		return preg_match(self::phone_regex, $string);
	}
	

	public static function is_valid_short_date($string)
	{
		return preg_match(self::short_date_regex, $string);
	}
	
	
	public static function is_valid_zip($string)
	{
		if (!self::is_blank($string) && is_numeric($string) && (strlen($string) == 5) )
		{
		   return true;
		}
	
		return false;
	}

	public static function is_valid_password($password)
	{
		if ( (strlen($password) < 3) || (strlen($password) > 20) || !preg_match(self::password_regex, $password) )
			return false;
		return true;
	}


	public static function is_valid_url($url)
	{
		return preg_match(self::min_url_regex, $url);
	}
	
	public static function is_valid_domain( $domain ) 
	{
		return preg_match(self::domain_regex,$domain);
	}

	
	public static function is_valid_state( $state ) 
	{
	        return (USStates::lookup_state_name( $state ) == NULL ? false : true);
	}
	
	
    public static function is_valid_credit_card_number( $ccn , $type )
    {
        
        // check credit card type
        $check = array( 
           self::CC_TYPE_VISA       =>  '^4[0-9]{15}$',
           self::CC_TYPE_MASTERCARD =>  '^5[1-5]{1}[0-9]{11,14}$',
           self::CC_TYPE_AMEX       =>  '^3[47]{1}[0-9]{13}$',
           self::CC_TYPE_DISCOVER   =>  '^6011[0-9]{12}' );
                        
        if( ! ereg( $check[$type], $ccn ) ) {
           Log::debug( 'invalid ccn '. $ccn .' does not match to pattern for '. $type . ' using pattern ' . $check[$type] );
           return false;
        }
        
        $doubledNumber  = "";
        $odd            = false;
        for($i = strlen($ccn)-1; $i >=0; $i--)
        {
             $doubledNumber .= ($odd) ? $ccn[$i]*2 : $ccn[$i];
             $odd            = !$odd;
        }
                                                                
        # Add up each 'single' digit
        $sum = 0;
        for($i = 0; $i < strlen($doubledNumber); $i++)
          $sum += (int)$doubledNumber[$i];
        
        # A valid number doesn't have a remainder after mod10\
        # or equal to 0
        if( ($sum % 10 ==0) && ($sum != 0) ) return true;
        Log::debug( 'invalid ccn '. $ccn .' LUHN check failed '. $type . ' sum='. $sum );      
        return false;
  
    }

}

?>
