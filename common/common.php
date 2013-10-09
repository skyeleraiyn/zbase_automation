<?php
 
	// Define test username
if(isset($test_username) && $test_username <> ""){
	define('TEST_USERNAME',  $test_username);
} else {
	define('TEST_USERNAME',  trim(shell_exec("whoami")));
}
 
common::include_all_php_files("constants/");

// Base file path
 // Ensure to replace correct value for generating HOME_DIRECTORY constant -- current path is functional_test/Constants
define('HOME_DIRECTORY', str_replace("common", "", dirname(__FILE__)));
define('BASE_FILES_PATH', HOME_DIRECTORY."common/misc_files/".ZBASE_VERSION."_files/");


// Include all php files under this directory and sub-directory
common::include_all_php_files(dirname(__FILE__));

class common{
	public function include_all_php_files($path_to_include){
		$path_to_include = dirname($path_to_include."/*");
		$phpfiles = glob($path_to_include."/*.php");
		foreach($phpfiles as $phpfile){
				include_once $phpfile;
			//	echo "including file $phpfile \n"; //	Debug purpose only
		}
		$directory_list = glob($path_to_include."/*", GLOB_ONLYDIR);
		foreach($directory_list as $directory_path){
			self::include_all_php_files($directory_path);
		}
		return 1;
	}
}

/* Used for debugging / pausing the script. 
	Included in this file as this gets called first 
	Usage:
	Call this function { breakpoint(); } and watch the debug log file
*/
	
function breakpoint(){
	$backtrace = debug_backtrace();
	log_function::debug_log("Breakpoint at line:".$backtrace[0]['line']." in function: ".$backtrace[1]['function']);
	log_function::debug_log("File: ".$backtrace[0]['file']);
	log_function::debug_log("Waiting for user input...");
	return readline("");
}
?>
