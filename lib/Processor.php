<?php

/*
	This is similar to Dispatch in that it takes an argument thats a classname, and attempts to process it.
	It also sets up a lockfile based on the executing classname
*/

final class Processor extends ApplicationEntryPoint {

	public static function process ($locking=true)
	{
		self::load();

		$process_class = "ApplicationProcess";
		$process_method = null;
		$now = null;

		if ($_SERVER['argc'] == 1) 
                {
			throw new GearsException("You must specify a process class name and or action and or now_date");
		} 
                elseif ($_SERVER['argc'] == 2) 
                {
			$process_method = $_SERVER['argv'][1];
		} 
                elseif ($_SERVER['argc'] == 3) 
                {
			$process_class = $_SERVER['argv'][1];
			$process_method = $_SERVER['argv'][2];
		} 
                else  // if ($_SERVER['argc'] == 4) 
                {
			$process_class = $_SERVER['argv'][1];
			$process_method = $_SERVER['argv'][2];
			$now = strtotime($_SERVER['argv'][3]);
		} 

		if ($now == null) {
			$now = time();
		}

		if (strpos($process_class, "Process") === false) {
			$process_class = sprintf("%sProcess", $process_class);
		}

		if (!class_exists($process_class)) {
			throw new GearsException(sprintf("%s is not a valid process class", $process_class));
		}

		if (!is_subclass_of($process_class, 'Process')) {
			throw new GearsException(sprintf("%s is not a subclass of Process", $process_class));
		}


		$process_class_methods = get_class_methods($process_class);

		if (!in_array($process_method, $process_class_methods)) {
			throw new GearsException(sprintf("%s is not a method of %s", $process_method, $process_class));
		}

		try {
			$previous_run = Model::find('ProcessRun', array(
				'where' => array(
					"process_class = ? AND process_method = ?", $process_class, $process_method
				),
				'order' => "end_date DESC"
			));
		} catch (Exception $e) {
			Log::debug($e);
			self::install_process_runs_table();
			$previous_run = null;
		}

		$tries = 1;
		while($tries++) {
			try {
				if ($locking == false || self::lock($process_class)) {
					$current_run = new ProcessRun(array(
						'pid' => getmypid(),
						'process_class' => $process_class,
						'process_method' => $process_method,
						'now_date' => date("Y-m-d H:i:s", $now),
						'begin_date' => date("Y-m-d H:i:s")
					));
					if ($current_run->trysave()) {
						$process = new $process_class($current_run, $previous_run, $now );
						$success = $process->$process_method();
						$current_run->end_date = date("Y-m-d H:i:s");
						$current_run->success = $success;
						if ($current_run->trysave()) {
							if( $locking == true ) self::unlock($process_class);
						}
					}
					break;
				} else {
					throw new LockException(sprintf("%s is locked", $process_class));
				}
			} catch (LockException $e) {
				Log::debug($e);
				if ($tries > Config::settings()->processor['max_tries']) {
					throw new LockException(sprintf("%s reached is max tries", $process_class));
				} else {
					sleep(1);
				}
			} catch (Exception $e) {
				Log::debug($e);
				if( $locking == true ) self::unlock($process_class);
				throw $e;
			}
		}
	}

	public static function install_process_runs_table ()
	{
		Model::connect(true);
		Model::execute_system_query("
			CREATE TABLE IF NOT EXISTS process_runs (
				`id` int(10) unsigned NOT NULL auto_increment,
				`pid` int(10) unsigned NOT NULL,
				`process_class` varchar(255) NOT NULL,
				`process_method` varchar(255) NOT NULL,
				`input` varchar(255) NULL,
				`output` varchar(255) NULL,
				`now_date` datetime NOT NULL,
				`begin_date` datetime NOT NULL,
				`end_date` datetime NULL,
  				`success` enum('0', '1')  NOT NULL default '0',
				PRIMARY KEY  (id)
	 			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
	}

	public static function is_running( $pid )
	{
		if( ! eregi( '[0-9]+', $pid ) )  {
		 	throw new Exception('pid must be an integer');		
		}
		exec( str_replace( '{pid}', $pid, Config::settings()->processor['pscommand']), 
			$output, $rval);
		return($rval === 0);				
	}


	private static function lockfile ($process_class)
	{
		return sprintf("%s/%s.lock", Config::settings()->processor['lock_root'], $process_class);
	}
		
	private static function locked ($process_class)
	{
		$file_exists = file_exists(self::lockfile($process_class));
		if ($file_exists) {
			
			
			$timed_out = filemtime(self::lockfile($process_class)) < (time() - intval(Config::settings()->processor['timeout']));
			if( ! $timed_out ) return false;

			Log::notice( 'lockfile timeouot ' . self::lockfile($process_class) );
			
			// check the pid contained in the lockfile 
			// and see if it's running
			$pid = file_get_contents(self::lockfile($process_class));
			if( ! eregi('[0-9]+',$pid) ) {
				Log::error( 'lockfile does not contain a valid pid ' . self::lockfile($process_class) );
				return false;
			}
			
			if( self::is_running($pid) ) {
				Log::notice( 'lockfile timeouot but process still running ' . self::lockfile($process_class) );
				return true;
			}
						
			return false;
			
		 } else {
			return false;
		}
	}

	private static function lock ($process_class)
	{
		if (!self::locked($process_class)) {
			return(file_put_contents( self::lockfile($process_class), getmypid() ) !== false); 

		}

		return false;
	}

	private static function unlock ($process_class)
	{
		if( file_exists(self::lockfile($process_class)) ) {		
				return unlink(self::lockfile($process_class));
		}
		return true;		
	}
	
}

class LockException extends Exception {
}

?>
