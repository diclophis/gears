<?php

interface ErrorAccess {
	public function add_error ($field, $message = '');
	public function is_error ($field);
	public function clear_errors ();
	public function errors ();
	public function has_errors ();
	public function is_valid ();
}

?>
