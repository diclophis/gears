<?php

/*
	Base class for all batch processes
	Work is done in the constructor
*/

class Process {
	public $current_run = null;
	public $previous_run = null;
	public $now = null;
	public function __construct ($current_run, $previous_run, $now)
	{
		$this->current_run = $current_run;
		$this->previous_run = $previous_run;
		$this->now = $now;
	}
}

?>
