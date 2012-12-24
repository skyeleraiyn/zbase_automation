<?php
 
common::include_all_php_files("constants/");

// Base file path
 // Ensure to replace correct value for generating HOME_DIRECTORY constant -- current path is functional_test/Constants
define('HOME_DIRECTORY', str_replace("common", "", dirname(__FILE__)));
define('BASE_FILES_PATH', HOME_DIRECTORY."common/misc_files/".MEMBASE_VERSION."_files/");


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
?>