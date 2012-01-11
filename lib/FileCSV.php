<?

/* class for parsing and iterating over CSV files. 
 * First line of the csv file are the field names and remaining lines
 * are data. each call to fetch() returns the next record of data
 * as an associative array.
 *
 * example - read data from test.csv - tab delimited
 * $csvfile = new FileCSV('test.csv', "\t", '"' );
 * while( $record = $csvfile->fetch() )
 * {
 *	print_r($record);
 * }
 *
 **/



class FileCSV  {

      private $fp             = false;
      private $delimiter      = ",";
      private $enclosure      = "\"";
      private $field_names    = false;
      private $line_number    = 0;
      
      public function __construct( $filename , $delimiter=',', $enclosure="\"" ) 
      {
            if( ! $this->fp = fopen( $filename, 'r' ) )
              throw new Exception( sprintf('unable to open file %s', $filename) );
            $this->delimiter = $delimiter;
            $this->enclosure = $enclosure;
            $this->line_number  = 0;         
               
            // only PHP5.3 accepts $escape
            if( ! $field_names = FileCSV::fgetcsv_custom( $this->fp, 0, $this->delimiter, $this->enclosure ) )  
                  throw new FileCSVException( sprintf('failed to read line from CSV file') );
            $this->field_names = $field_names;
      }
      
      public function __destruct()
      {	
          if( $this->fp ) fclose($this->fp);
      }


      public function line_number() 
      {
          return $this->line_number;
      }

  
      /**
       * fetch next record.  returns associative array or false if
       * no records found.  throws FileCSVException if a field is 
       * missing from the data.
       *
       */
      public function fetch() 
      {
            if( feof( $this->fp ) ) {
                return false;
            }
            
            if( ! $data = FileCSV::fgetcsv_custom( $this->fp, 0, $this->delimiter, $this->enclosure ) ) {
                    return false;
            }  
            $this->line_number++;      
            reset($this->field_names);          
            $record=array();
            foreach( $this->field_names as $field_number => $field_name ) {
                    if( ! array_key_exists( $field_number, $data ) ) {
                        var_dump($data);
                        throw new FileCSVException( sprintf('missing field %s on line %d of csv file', $field_name, $this->line_number ) );
                    }      
                    $record[$field_name] = $data[$field_number];
            }  
            return $record;
      }
      
       


      
    static function fgetcsv_custom(  $resource_handle ,  $length=0 ,  $delimiter=',' ,  $enclosure="\"" )
    {
        if(!$str = fgets($resource_handle))
        {
            return false;   
        }
        $expr="/$delimiter(?=(?:[^$enclosure]*\"[^$enclosure]*\")*(?![^$enclosure]*\"))/";
    
        $results=preg_split($expr,trim($str));
    
        return preg_replace("/^$enclosure(.*)$enclosure$/","$1",$results);
    
    }


}




?>
