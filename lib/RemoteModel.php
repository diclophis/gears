<?php

class RemoteModel implements ArrayAccess, ErrorAccess {

	protected $error_list = array();

   public function __construct ($data = null, $new = true)
	{
		if ($data !== null && is_array($data)) {
			foreach ($data as $key => $value) {
				if (!empty($key)) {
					$this->$key = $value;
				}
			}
		}
	}

	public function add_error ($field, $message = '')
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

	public function is_valid ()
	{
	}

	public function trysave ()
	{
	}

	public function toArray()
	{
		$a = get_object_vars($this);
		return $a;
	}
	
	public function offsetExists($key)
	{
		return isset($this->{$key});
	}
	
	public function offsetGet($key)
	{
		return $this->{$key};
	}
	
	public function offsetSet($key,$value)
	{
		$this->{$key} = $value;
	}
	
	public function offsetUnset($key)
	{
		unset($this->{$key});
	}
}

?>
