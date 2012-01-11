<?

/* 
 * simple class to facilitate downloading messages from a POP3/IMAP server.
 * requires PHP imap support. 
 *
 * On freebsd make install under /usr/ports/mail/php5-imap
 *
 */


class IMAPIterator implements Iterator
{
        const PROTOCOL_IMAP = 'imap';
        const PROTOCOL_POP3 = 'pop3';

        private $imap_stream = NULL;
        private $host = NULL;
        private $port = NULL;
        private $protocol = NULL;
        private $user = NULL;
        private $pass = NULL;
        private $mailbox = NULL;        
        private $errors = NULL;
        private $count = NULL;    
        private $msgnum = NULL;	
        private $current_msg= NULL;
        private $flags='';
        

        /**
         * constructor
         * @param host imap/pop3 hostname
         * @param port imap/pop3 port
         * @param protocol either IMAPFetch::PROTOCOL_IMAP or IMAPFetch::PROTOCOL_POP3
         */
        
        public function __construct( $host, $port, $protocol = self::PROTOCOL_IMAP ) 
        {
                  $this->host = $host;
                  $this->port = $port;
                  $this->protocol = $protocol;
        }


        /**
         * return true if there was an error on the last operation
         */
        public function has_errors()
        {
            return ($this->errors != NULL && count($this->errors));
        }
        

        /**
         * @return srting current error message
         */
        public function errors()
        {
             if( is_array($this->errors) ) 
                return implode(' ',$this->errors);
            return "NO IMAP ERRORS";
        }
    
    
        /**
         * Open a mailbox in preperation for reading messages
         * @param string user  username
         * @param string pass  password
         * @param string mailbox  
         * @return bool true on success false on failure - call errors() to get specific error message
         */
        public function open( $user, $pass, $mailbox = 'INBOX', $flags )
        {
                  $this->user = $user;
                  $this->pass = $pass;
                  $this->mailbox = $mailbox;
                  $this->flags=$flags;
    
                  if( ! strlen($this->flags) ) $this->flags = '/novalidate-cert';
                  $connect_string=sprintf('{%s:%d/%s%s}%s',$this->host,$this->port,$this->protocol,$this->flags,$this->mailbox);
//Log::debug($connect_string);
                  $this->imap_stream = @imap_open( $connect_string, $user, $pass );
                  $this->errors = imap_errors();              
  
                  if( ! $this->imap_stream )
                     return false;  
                      
                  if( is_array( $this->errors ) && $this->errors[0] != 'Mailbox is empty' && 
                                $this->errors[0] != 'SECURITY PROBLEM: insecure server advertised AUTH=PLAIN' )  {
                     return false;
                  }
                  
                     
                 
                  $this->errors = NULL;    
                  $this->count = imap_num_msg($this->imap_stream);  
                  $this->msgnum = 0;
                  $this->next_message();
//                  $this->current_msg = new IMAPMessage( -1, '', '');
                  
                  return true;
        }
    

        /**
         * @return array list of imap mailboxes
         ***/
         
        public function list_mailboxes()
        {
              $ref=sprintf('{%s:%d/%s%s}%s',$this->host,$this->port,$this->protocol,$this->flags,'');
              return imap_list( $this->imap_stream , $ref, '*');
        }

        /**
         * @retrun bool true if connection is currently open
         */
        public function is_open()  
        {
              return( $this->imap_stream != NULL && $this->imap_stream !== false );
        } 
        
        /**
         * @return integer number of message in the current mailbox
         */
        public function count_messages()
        {
             return $this->count;
        }

  
        /**
         * @return IMAPMessage current message
         */
        public function current_message()
        {
              if( ! $this->valid() ) return false;
              return $this->current_msg;
        }

        
        /**
         * get the next message from the mailbox
         * @return mixed false if there are no more messages or IMAPMessage 
         */
        public function next_message() 
        {
                  if( ! $this->is_open() || $this->msgnum === NULL ||
                        $this->msgnum > $this->count_messages() ) return false;
                        
                  $this->msgnum++;
                  if( $this->msgnum > $this->count_messages() ) return false;       


                  if( ! $rawheaders = @imap_fetchheader($this->imap_stream,$this->msgnum) ){
                        $this->errors = imap_errors();
                        return false;
                  }      
                  
                  if( ! $rawbody = imap_body($this->imap_stream,$this->msgnum) ) {
                        $this->errors = imap_errors();
                        return false;
                  }            

                  imap_errors();	                                  
                  $this->current_msg = new IMAPMessage( $this->msgnum, $rawheaders, $rawbody );
                  return $this->current_msg;
        }        
        
        /**
         * flag current message for deletion - deletetion happens when close() is called
         */
        public function delete( $msgnum )
        {
             if( $msgnum == NULL || 
                 $msgnum > $this->count_messages() ) return false;

              if( ! $this->is_open() ) 
                    return;

             imap_delete($this->imap_stream,$msgnum);  
             imap_errors(); // clear errors
             return true;
        }
        
        /**
         * close connection and delete messages that were marked for deletion
         */
        public function close()
        {
              if( $this->imap_stream === NULL || $this->imap_stream === false ) 
                    return;
              imap_expunge($this->imap_stream);
              imap_errors();
              imap_close($this->imap_stream);
              
              $this->imap_stream = NULL;
              $this->msgnum = NULL;
        }


        /**
         * close connection
         */
        public function disconnect()
        {
              if( $this->imap_stream === NULL || $this->imap_stream === false ) 
                    return;

              imap_errors();
              imap_close($this->imap_stream);
              
              $this->imap_stream = NULL;
              $this->msgnum = NULL;
        }
        
        
        /**
         * get current message number
         * @return integer current message number
         */ 
        public function get_msgnum()
        {		
              return $this->msgnum;
        }	

  
        /* ITERATOR functions */
        public function current() 
        {
            return $this->current_message();
        }
        
        public function key()
        {
            return $this->msgnum;
        }
        
        public function next() 
        {
             return $this->next_message();
        }
        
        public function rewind()
        {
             $this->msgnum=0;
             $this->next_message();
        }

        public function valid() 
        {
            $is_valid = ( $this->is_open() && $this->msgnum > 0 && $this->msgnum <= $this->count && $this->current_msg !== NULL);
            return $is_valid;
        }
}



?>
