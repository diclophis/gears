<?php

class ProcessRun extends Model
{
	const TABLE_NAME = 'process_runs';
	const PRIMARY_KEYS = 'id autoincrement';

	//unique to each ProcessRun
	public $id;
	
	//Used to identify which actual system process is this ProcessRun
	public $pid;

	//What Process class is running
	public $process_class;

	//What method of that class is executing
	public $process_method;

	//input from something
	public $input;

	//output to somewhere
	public $output;

	//used to "time-travel" the processrun for testing purposes
	public $now_date;

	//local system time of when the process was begun, uneffected by $now_date
	public $begin_date;

	//local system time for when the process ended
	public $end_date;

	//whether or not this process was a success
	public $success = false;


	public static function find_last_successful()
	{
		return Model::find('ProcessRun', array('where' => array('success = 1'), 
																'order' => 'end_date desc')
															   );	
	}

	/* return true if the process has run today */
	public function was_today()
	{	
	 	return  (substr($this->begin_date,0,10) == date('Y-m-d') );	
	}
	
	/* return true if process ran successfully */
	public function was_successful()
	{
		return ($this->success == "1");
	}
	
	/* return true if process failed */
	public function was_failed()
	{
		return !$this->was_successfull();
	}

}

?>
