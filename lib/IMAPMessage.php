<?

/* 
 * simple class to represent a single message returned by an IMAP/POP3 server
 *
 */


class IMAPMessage
{
       public $rawheaders = NULL;
       public $rawbody   = NULL;
       public $msgnum     = NULL; 
       public $parsedheaders = NULL;
      
       public function __construct ( $msgnum, $rawheaders, $rawbody )
       {
             $this->msgnum     = $msgnum;
             $this->rawheaders = $rawheaders;
             $this->rawbody    = $rawbody;
             $this->parsedheaders = imap_rfc822_parse_headers($rawheaders);
       }
       

       public function getParsedHeader( $name )
       {	
           if( isset($this->parsedheaders->$name) ) return $this->parsedheaders->$name;
           return NULL;
       }

       public function getBody()
       {
           return $this->rawbody;
       }

}



?>