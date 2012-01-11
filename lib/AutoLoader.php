<?php

/*
	The AutoLoader class is an object oriented hook into PHP's \_\_autoload functionality.
	When adding a whole folder each file should contain one class named the same as the file sans ".php" (PageController => PageController.php)
*/
final class AutoLoader {
	static protected $files = array();
	static protected $folders = array();
	
	/*
		Used to add a folder to the search patch for autoloading...
		AutoLoader::add_folder($_SERVER['COMP_ROOT']."/app/controllers");
	*/
	static public function add_folder ($folder)
	{
		if (is_array($folder)) {
			foreach($folder as $f) {
				self::addFolder($f);
			}
		} else {
			self::$folders[] = $folder;
		}
	}

	/*
		This method is called when a unloaded class needs to be loaded, it attempts to match the existing list of files to the class name, and load that.
		And then it beings to search through the added folders
	*/
	static public function load ($class_name)
	{
		foreach(self::$files as $name => $file){
			if($class_name == $name){
				require_once($file);
				return true;
			}
		}
		foreach (self::$folders as $folder) {
			if (substr(0,-1) != DIRECTORY_SEPARATOR) {
				$folder .= DIRECTORY_SEPARATOR;
			}
			if (file_exists($folder.$class_name.'.php')) {
				require_once($folder.$class_name.'.php');
				return true;
			}
		}
		return false;
	}
}

/*
	Register the Autoloading handler into PHP
*/

if(!function_exists('__autoload')) {
	function __autoload($class_name) {
		return AutoLoader::load($class_name);
	}
}

?>
