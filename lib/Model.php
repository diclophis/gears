<?php

/*
	Base Model implementation, designed as much as possible to follow the "ActiveRecord" pattern.
	There are base "finder" methods, that are used to retreive records and build instances of a given Model class
	And there are save() and create() methods for creating and updated records
*/

class Model implements ArrayAccess, ErrorAccess {
	static protected $connection = null;
	static protected $table_names = array();
	static protected $primary_keys = array();
	static protected $relationships = array();
	static protected $protected_field_names = array('class_name', 'error_list', 'new');
	public $extra_protected_field_names = array();
	static protected $behaviors = array();
	static protected $autoincrement_key = null;
	static protected $current_statement = null;
	protected $error_list = array();
	protected $class_name = false;
	protected $new = true;
	
	final static public function connect ($for_migrations = false)
	{
		$host = Config::settings()->model_db['host'];
		$dbname = Config::settings()->model_db['dbname'];
		if ($for_migrations) {
			$username = Config::settings()->model_db['username_for_migrations'];
			$password = Config::settings()->model_db['password_for_migrations'];
		} else {
			$username = Config::settings()->model_db['username'];
			$password = Config::settings()->model_db['password'];
		}

		$dsn = sprintf("mysql:host=%s;dbname=%s", $host, $dbname);

		$driver_options = array(
			PDO::ATTR_FETCH_TABLE_NAMES => 1,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
		);

		self::$connection = new PDO($dsn, $username, $password, $driver_options);
	}

	final static public function connected ()
	{
		return (self::$connection != null);
	}
	
	/**
	 * Returns a PDO object, SQLiteDatabase object or a MySQL connection resource
	 * @return mixed 
	 */
	final static public function connection ()
	{
		if (!self::connected()) {
			self::connect();
		}

		return self::$connection;
	}

	/**
	 * @param string $class_name
	 * @return string
	 */
	final static public function tableNameFromClassName($class_name){
		if(!isset(self::$table_names[$class_name])){
			if(!class_exists($class_name))
				$table_name = $class_name;
			else{
				$table_name = (defined($class_name.'::TABLE_NAME') ? constant($class_name.'::TABLE_NAME') : null);
				if ($table_name === null) {
					throw new GearsException("you must specify a table name in the class");
				}
					//$table_name = StringHelper::pluralize(strtolower(preg_replace('/([a-z])([A-Z])/e',"'\\1_'.strtolower('\\2')",str_replace('ActiveRecord','',$class_name))));
			}
			self::$table_names[$class_name] = $table_name;
		}
		return self::$table_names[$class_name];
	}
	
	/**
	 * @param string $class_name
	 * @return string
	 */
	final static public function primary_keys_from_class_name ($class_name, $set_autoincrement_key = true)
	{
		if (!class_exists($class_name)) {
			throw new GearsException(sprintf("%s is an invalid or missing class name, instance not constructed properly", $class_name));
		}

		$primary_keys_string = (defined($class_name.'::PRIMARY_KEYS') ? constant($class_name.'::PRIMARY_KEYS') : ('id'));
		$primary_keys = explode(",", $primary_keys_string);
		foreach ($primary_keys as $i => $value) {
			if (stripos($value, "autoincrement") !== false) {
				self::$autoincrement_key = $primary_keys[$i] = str_ireplace(array(" ", "autoincrement"), "", $value);
			}
		}
		return $primary_keys;
	}
	
	final static protected function last_insert_id ()
	{
		return Model::connection()->lastInsertId();
	}
	
	/**
	 * @param string $sql
	 * @return mixed
	 */
	final static public function execute_query ($sql_or_statement, $arguments = null)
	{
		if ($sql_or_statement instanceof PDOStatement) {
			$executed = $sql_or_statement->execute($arguments);
			if ($executed) {
				$response = $sql_or_statement;
			} else {
				$response = false;
			}	
		} elseif ((func_num_args() > 1) && $arguments !== null) {
			$arguments = func_get_args();
			$sql = array_shift($arguments);
			$sql_or_statement = Model::connection()->prepare($sql);
			$response = Model::execute_query($sql_or_statement, $arguments)->fetchAll();
		} elseif (is_string($sql_or_statement)) {
			$sql_or_statement = Model::connection()->query($sql_or_statement);
			if ($sql_or_statement) {
				$response = $sql_or_statement->fetchAll();
			} else {
				$response = false;
			}
		} else {
			throw new GearsException("execute query requires a PDOStatement, a string, or an array of string+parameters");
		}

		//if ($response === false) {
		//	throw new GearsException($sql_or_statement);
		//} else {
		//	return $response;
		//}
		return $response;
	}

	final static public function execute_system_query ($sql_or_statement, $arguments = null)
	{
		if ($sql_or_statement instanceof PDOStatement) {
			$executed = $sql_or_statement->execute($arguments);
			if ($executed) {
				$response = true;
			} else {
				$response = false;
			}	
		} elseif ((func_num_args() > 1) && $arguments !== null) {
			$arguments = func_get_args();
			$sql = array_shift($arguments);
			$sql_or_statement = Model::connection()->prepare($sql);
			$response = Model::execute_system_query($sql_or_statement, $arguments)->fetchAll();
		} elseif (is_string($sql_or_statement)) {
			$sql_or_statement = Model::connection()->query($sql_or_statement);
			if ($sql_or_statement) {
				$response = true;
			} else {
				$response = false;
			}
		} else {
			throw new GearsException("execute_system_query requires a PDOStatement, a string, or an array of string+parameters");
		}

		return $response;
	}

	final static public function count ()
	{
		$args = func_get_args();
		$response = call_user_func_array(array('Model', 'execute_query'), $args);
		return $response[0][0];
	}

	final static function build_statement ($class_name, $params)
	{
		$where_parameters = array();
		$where_template = '';
		if (isset($params['where']) && is_array($params['where'])) {
			$where_template = array_shift($params['where']);
			$where_parameters = $params['where'];
		}

		if (isset($params['tables'])) {
			$params['tables'] = (is_string($params['tables']) ? array($params['tables']) : $params['tables']);
		} else {
			$params['tables'] = array();
		}

		if (count($params['tables']) == 0) {
			$params['tables'] = array(self::tableNameFromClassName($class_name));
		}

		$sql = 'SELECT ';
		$sql .= (isset($params['select']) ? implode(',',$params['select']) : '*');
		$sql .= ' FROM ';
		$sql .= implode(',', $params['tables']);

		$through_class_name = null;

		if (isset($params['through'])) {
			$link_class_name = null;
			foreach ($params['through'] as $through_class_name => $fkey) {
				$through_table_name = self::tableNameFromClassName($through_class_name);
				$sql .= " JOIN ";
				$sql .= $through_table_name;
				$sql .= " ON ";
				$sql .= $through_table_name;
				$sql .= ".";
				$sql .= $fkey;
				$sql .= " = ";
				//$sql .= self::tableNameFromClassName($class_name);
				if ($link_class_name != null) {
					$sql .= self::tableNameFromClassName($link_class_name);
				} else {
					$sql .= self::tableNameFromClassName($class_name);
				}
				$sql .= ".";
				$sql .= $fkey;
				$link_class_name = $through_class_name;
			}
		}

		if (isset($params['include'])) {
			$link_class_name = null;
			foreach ($params['include'] as $related_class_name => $fkey) {
				$sql .= " LEFT JOIN "; 
				$sql .= self::tableNameFromClassName($related_class_name);
				$sql .= " ON ";
				$sql .= self::tableNameFromClassName($related_class_name);
				$sql .= ".";
				$sql .= $fkey;
				$sql .= " = ";
				if ($link_class_name != null) {
					$sql .= self::tableNameFromClassName($link_class_name);
				} else {
					$sql .= self::tableNameFromClassName($class_name);
				}
				$sql .= ".";
				$sql .= $fkey;
				$link_class_name = $related_class_name;
			}
		}

		if (strlen($where_template)) {
			$sql .= ' WHERE ';
			$sql .= $where_template; 
		}

		$sql .= (isset($params['group']) ? ' GROUP BY '.$params['group'] : '');
		$sql .= (isset($params['order']) ? ' ORDER BY '.$params['order'] : '');
		$sql .= (isset($params['offset'], $params['limit']) ? ' LIMIT '.$params['offset'].','.$params['limit'] : '');
		$sql .= (!isset($params['offset']) && isset($params['limit']) ? ' LIMIT '.$params['limit'] : '');

		$statement = Model::connection()->prepare($sql);
		foreach ($where_parameters as $key => $value) {
			$statement->bindValue((int)($key + 1), $value);
		}

		return $statement;
	}

	/*
		Used by the delete() instance method
	*/
	static public function destroy ($class_name, $params)
	{
		$where_parameters = array();
		$where_template = '';

		if (isset($params['where']) && is_array($params['where'])) {
			$where_template = array_shift($params['where']);
			$where_parameters = $params['where'];
		}

		if (isset($params['tables'])) {
			$params['tables'] = (is_string($params['tables']) ? array($params['tables']) : $params['tables']);
		} else {
			$params['tables'] = array(self::tableNameFromClassName($class_name));
		}

		$sql = 'DELETE FROM ';
		$sql .= implode(',', $params['tables']);

		if (strlen($where_template)) {
			$sql .= ' WHERE ';
			$sql .= $where_template; 
		}

		$statement = Model::connection()->prepare($sql);

		if ($statement) {
			foreach ($where_parameters as $key => $value) {
				$statement->bindValue((int)($key + 1), $value);
			}
		}

		if ($statement->execute()) {
			return  $statement->rowCount();
		} else {
			return false;
		}
	}

	/*
		Builds a new subclass instance and saves it.
	*/
	static public function create ($class_name, $data = false)
	{
		//TODO: fix this hack, or clean it up, too global variable like
		self::$autoincrement_key = null;
		$record = new $class_name($data);
		$record->save();
		return $record;
	}
	
	/*
		Similar to findAll() but returns only a single instance no matter how many records are found. Returns false if no record is found.
	*/
	static public function find ($class_name, $id_or_params)
	{
		$params = $id_or_params;
		$params['limit'] = 1;
		$results = self::find_all($class_name, $params);
		return (isset($results[0])) ? $results[0] : false;
	}
	
	/*
		Find all instances of a given $class_name. You can pass a complete SQL statement, or an array of $params which may contain any of the following:
			- where - string WHERE SQL fragment or array of (string field_name => mixed value) pairs
			- order - string ORDER BY SQL fragment
			- offset - int LIMIT SQL fragment
			- limit - int LIMIT SQL fragment
			- tables - array of tables to include (defaults to the table belonging to the current $class_name)
			- columns - array of columns to include (defaults to *)
			- include - array of (string join_class => string foreign_key) pairs
			- through - array of (string join_class => string foreign_key) pairs
	*/
	static public function find_all ($class_name, $params_or_sql = array())
	{
		$results = array();

		if (!is_array($params_or_sql)) {
			$sql = $params_or_sql;
			$response = self::execute_query($sql);
		} else {
			$sql = self::build_statement($class_name, $params_or_sql);
			$sql->execute();
			$response = $sql;
		}

		self::$current_statement = $response;
		$target_class_name = (class_exists($class_name) ? $class_name : 'Model');
		$empty_target = new $target_class_name();
		$empty_target->params_or_sql = $params_or_sql;
		$results = $response->fetchAll(PDO::FETCH_FUNC, array($empty_target, 'fetch'));
		return $results;
	}

	public function fetch ()
	{
		$data = array();
		$arguments = func_get_args();
		$included_data = array();
		$included_tables = array();

		if (is_array($this->params_or_sql) && isset($this->params_or_sql['include'])) {
			foreach ($this->params_or_sql['include'] as $related_class_name => $fkey) {
				$included_data[$related_class_name] = array();
				$included_tables[self::tableNameFromClassName($related_class_name)] = $related_class_name;
			}
		}

		foreach ($arguments as $i => $argument) {
			$meta = (self::$current_statement->getColumnMeta($i));
			if (array_key_exists('table', $meta) && array_key_exists($meta['table'], $included_tables)) {
				$related_class_name = $included_tables[$meta['table']];
				$included_data[$related_class_name][$meta['name']] = $argument;
			} else {
				$data[$meta['name']] = $argument;
			}
		}

		$model = new $this->class_name($data, false);

		foreach ($included_data as $related_class_name => $data) {
			$related_class_name = strtolower($related_class_name);
			$model->$related_class_name = new $related_class_name($data);
		}

		return $model;
	}
	
	/*
		sets up internal attributes from the passed in data
	*/
	public function __construct ($data = null, $new = true)
	{
		if (!$this->class_name) {
			$this->class_name = get_class($this);
		}
		if ($data !== null && is_array($data)) {
			foreach ($data as $key => $value) {
				if (!empty($key)) {
					$this->$key = $value;
				}
			}
		}
		// EL - this assignment was missing	
		$this->new = $new;
	}
	
	/*
		Adds an error to the specified attribute field
	*/
	final public function add_error ($field, $message = '')
	{
		if (isset($this->error_list[$field])) {
			foreach(range(0, 10) as $i) {
				$field .= sprintf("_%s", $i);
				if (!isset($this->error_list[$field])) {
					break;
				}
			}
		}
		$this->error_list[$field] = $message;
	}

	/*
		returns boolean if the specified field has an error associated with it
	*/
	final public function is_error ($field)
	{
		return isset($this->error_list[$field]);
	}
	
	final public function clear_errors ()
	{
		$this->error_list = array();
	}
	
	final public function errors ()
	{
		return $this->error_list;
	}

	final public function has_errors ()
	{
		return (count($this->error_list) > 0);
	}
	
	/*
		Does nothing by default, you can override this in a subclass.
	*/
	public function is_valid ()
	{
	}

	final public function insert ($replace = false)
	{
		$keys = array();
		$values = array();
		$qs = array();
		
		foreach($this->toArray() as $key => $value) {
			$keys[] = $key;
			$values[] = $value;
			$qs[] = "?";
		}

		if ($replace) {
			$sql = "REPLACE INTO ";
		} else {
			$sql = "INSERT INTO ";
		}
		$sql .= self::tableNameFromClassName($this->class_name);
		$sql .= "(";
		$sql .= implode(',', $keys);
		$sql .= ") VALUES (";
		$sql .= implode(',', $qs);
		$sql .= ")";

		$statement = Model::connection()->prepare($sql);
		if ($statement === false) {
			throw new GearsException(implode("\n", Model::connection()->errorInfo()));
		}

		foreach ($values as $key => $value) {
			$statement->bindValue(($key + 1), $value);
		}

		$response = $statement->execute();
		if ($response && self::$autoincrement_key) {
			$this->{self::$autoincrement_key} = self::last_insert_id();
		}

		return $response;
	}

	final public function update ()
	{
		// EL this was missing
		$primary_keys = self::primary_keys_from_class_name($this->class_name);
		
		$sql = "UPDATE ";
		$sql .= self::tableNameFromClassName($this->class_name);
		$sql .= " SET ";
		$values = array();			
		$keys = array();

		foreach ($this->toArray() as $key => $value) {
			if (!in_array($key, $primary_keys)) {
				$keys[] = $key." = ?";
				$values[] = $value;
			}
		}

		$sql .= implode(",", $keys); 
		$sql .= " WHERE ";

		foreach ($primary_keys as $i => $primary_key) {
			if ($i > 0) {
				$sql .= " AND ";
			}
			$sql .= $primary_key." = ?";
			$values[] = $this->{$primary_key};
		}

		$statement = Model::connection()->prepare($sql);

		foreach ($values as $key => $value) {
			$statement->bindValue(($key + 1), $value);
		}

		$response = self::execute_query($statement);

		return $response;
	}
	
	/*
		Saves a given record, if it passes all validation and before filter tests
		return bool

		use case:
			$record = new ExampleModel();
			if ($record->save()) {
				//everything is aok
			} else {
				//else something blew up
			}
	*/
	public function save ($throw_exception = false)
	{
		$response = null;
		self::$autoincrement_key = null;
		$primary_keys = self::primary_keys_from_class_name($this->class_name);
		try
        {
			if ($this->is_valid() === false || $this->has_errors())
            {
				throw new RecordNotSaved("Model::is_valid failed::" . print_r($this, true));
			}

			if ($this->notifyObservers('before_save') === false)
            {
				throw new RecordNotSaved("Model::before_save failed");
			}

			if ($this->new)
            {
				if ($this->notifyObservers('before_create') === false)
                {
					throw new RecordNotSaved("Model::before_create failed");
				}
				$response = $this->insert();
				$this->notifyObservers('after_create');
			} 
            else
            {
				if ($this->notifyObservers('before_update') === false) 
                {
					throw new RecordNotSaved("Model::before_update failed");
				}
				$response = $this->update();
				$this->notifyObservers('after_update');
			}

			$this->notifyObservers('after_save');
			$this->new = false;
			return $response;
		}
        catch (Exception $exception)
        {
			if ($throw_exception)
            {
				throw $exception;
			}
            else
            {
				$this->add_error('','DATABASE ERROR SAVING RECORD ' . $exception->getMessage() );
				Log::fatal($exception);
				return false;
			}
		}
	}

	public function replace ($throw_exception = false)
	{
		$response = null;
		self::$autoincrement_key = null;
		$primary_keys = self::primary_keys_from_class_name($this->class_name);
		try {
			if ($this->is_valid() === false || $this->has_errors()) {
				throw new RecordNotSaved("Model::is_valid failed::" . print_r($this, true));
			}

			if ($this->notifyObservers('before_save') === false) {
				throw new RecordNotSaved("Model::before_save failed");
			}

			if ($this->notifyObservers('before_replace') === false) {
				throw new RecordNotSaved("Model::before_replace failed");
			}
			$response = $this->insert(true);
			$this->notifyObservers('after_replace');
		} catch (Exception $exception) {
			if ($throw_exception) {
				throw $exception;
			} else {
				Log::fatal($exception);
				return false;
			}
		}
	}

	/*
		Saves a given record, if it passes all validation and before filter tests
		return true or throws a RecordNotSaved exception (compare to save())

		use case:
			$record_a = new ExampleModel();
			$record_b = new SecondExampleModel();
			try {
				$record_a->trysave();
				$record_b->trysave();
				//if you get here everything is aok
			} catch (RecordNotSaved $error) {
				//else you end up here if something blew up
			}
	*/
	final public function trysave ()
	{
		return $this->save(true);
	}

	final public function tryreplace ()
	{
		return $this->replace(true);
	}

	/*
		Used to handle transactions
	*/
	public static function begin_transaction ()
	{
		return Model::connection()->beginTransaction();
	}

	public static function commit_transaction ()
	{
		return Model::connection()->commit();
	}

	public static function rollback_transaction ()
	{
		return Model::connection()->rollback();
	}

	/*
		These are currently un-implemented
	*/
	final protected function notifyObservers ($method_name) {
		return $this->$method_name();
	}
	
	public function before_create(){}
	public function before_update(){}
	public function after_update(){}
	public function after_create(){}
	public function before_save(){}
	public function after_save(){}
	public function before_delete(){}
	public function after_delete(){}
	public function after_find(){}
	public function before_replace(){}
	public function after_replace(){}
	
	/*
		The following methods are used for ArrayAccess
	*/
	final public function toArray()
	{
		$a = get_object_vars($this);
		unset($a['extra_protected_field_names']);
		foreach (self::$protected_field_names as $field) {
			unset($a[$field]);
		}
		foreach ($this->extra_protected_field_names as $field) {
			unset($a[$field]);
		}
		return $a;
	}
	
	final public function offsetExists($key)
	{
		return (!in_array($key,self::$protected_field_names)) ? isset($this->{$key}) : false;
	}
	
	final public function offsetGet($key)
	{
		if (!in_array($key,self::$protected_field_names)) {
			return $this->{$key};
		} else {
			throw new GearsException(sprintf("%s is a protected property of Model", $key));
		}
	}
	
	final public function offsetSet($key,$value)
	{
		if (!in_array($key,self::$protected_field_names)) {
			$this->{$key} = $value;
		} else {
			throw new GearsException(sprintf("%s is a protected property of Model", $key));
		}
	}
	
	final public function offsetUnset($key)
	{
		if(!in_array($key,self::$protected_field_names)) {
			unset($this->{$key});
		} else {
			throw new GearsException(sprintf("%s is a protected property of Model", $key));
		}
	}

	public function blank ($key)
	{
		return ( isset($this->$key)===false || (strlen(trim($this->$key)) === 0));
	}

	public function invalid_url ($key, $explode_on = null)
	{
		if (isset($this->$key)) {
			if ($explode_on) {
				$urls = explode($explode_on, $this->$key);
			} else {
				$urls = array($this->$key);
			}

			foreach ($urls as $url) {
				 //$domain = "([[:alpha:]][-[:alnum:]]*[[:alnum:]])(\.[[:alpha:]][-[:alnum:]]*[[:alpha:]])+";
				 $domain = "(http(s?):\/\/|ftp:\/\/)*([[:alpha:]][-[:alnum:]]*[[:alnum:]])(\.[[:alpha:]][-[:alnum:]]*[[:alpha:]])+";
				 $dir = "(/[[:alpha:]][-[:alnum:]]*[[:alnum:]])*";
				 $page = "(/[[:alpha:]][-[:alnum:]]*\.[[:alpha:]]{3,5})?";
				 $getstring = "(\?([[:alnum:]][-_%[:alnum:]]*=[-_%[:alnum:]]+)(&([[:alnum:]][-_%[:alnum:]]*=[-_%[:alnum:]]+))*)?";
				 $pattern = "^".$domain.$dir.$page.$getstring."$";
				 if (!eregi($pattern, $url)) {
					 return true;
				 }
			}
		}

		return false;
	}

	public function invalid_email ($key, $explode_on = null)
	{
		if (isset($this->$key)) {
			if ($explode_on) {
				$emails = explode($explode_on, $this->$key);
			} else {
				$emails = array($this->$key);
			}

			foreach ($emails as $email) {
				$email = trim($email);
				if (!preg_match("/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/i", $email)) {
					return true;
				}
			}
		}

		return false;
	}
	
	
	public function invalid_phone ($key)
	{
		$phone_regex = "/^[\(]*([\d]{3})[\)]*[ \.\-]*([\d]{3})[ \.\-]*([\d]{4})[ ]*[(x|ext|ex)]*[\. ]*([0-9]*)$/";
		return !preg_match($phone_regex, $this->$key);
	}
	
	
	
	/**
	 * check if a field is numeric
	 * @param string $key  the key to check
	 * @param string $min  minimum value
	 * @param string $max  maximun value
	 */	
	public function invalid_number( $key, $min = NULL, $max = NULL )
	{
		if(!is_numeric($this->$key)) {
			return true;	
		}
	        
		if( $min != NULL && $this->$key < $min ) {
			return true;
		}
		
		if( $max != NULL && $this->$key > $max ) {
			return true;
		}
		
		return false;	
	}

	/**
	 * check if a field matches a regular expression
	 * @param string $key  the key to check
	 * @param string $regex regular expression 
	 */	
	public function invalid_regex( $key, $regex )
	{
		if(!ereg( $regex, $this->$key)) {
			return true;	
		} else {
			return false;	
		}
	}

	private function invalid_password($password)
	{
		$regex_password = "/^([a-z0-9._-])+$/i";
		if ( (strlen($password) < 5) || (strlen($password) > 20) || !preg_match($regex_password, $password) ) {
			return true;
		} else {
			return false;
		}
	}

	/*
		Uses primary keys defined in model
		$record = Model::create('ModelClassName', array(
			...
		));
		print_r($record->delete());
	*/
	public function delete ()
	{
		$class_name = get_class($this);
		$where_statement = "";
		$where = array();
		$primary_keys = self::primary_keys_from_class_name($class_name);
		
		foreach ($primary_keys as $idx => $primary_key) {
			if ($idx != 0) {
				$where_statement .= " AND ";
			}
			$where_statement .= sprintf(" %s = ? ", $primary_key);
			$where[] = $this->$primary_key;
		}

		array_unshift($where, $where_statement);

		if ($this->notifyObservers('before_delete') === false) {
				throw new RecordNotSaved("Model::before_delete failed");
		}
		
		if( self::destroy($class_name, array('where' => $where)) ) {

			if ($this->notifyObservers('after_delete') === false) {
					throw new RecordNotSaved("Model::after_delete failed");
			}
		        return true;
		} else {
			return false;
		}
		
	}

	public function __toString ()
	{
		return print_r($this, true);
	}
}

class RecordNotSaved extends Exception {
	/*
	public $error = null;
	public function __construct($error)
	{
		parent::__construct("RecordNotSaved", 1);
		$this->error = $error;
	}
	*/
}

?>
