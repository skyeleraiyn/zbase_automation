<?php
 
	// Define test username
if(isset($test_username) && $test_username <> ""){
	define('TEST_USERNAME',  $test_username);
} else {
	define('TEST_USERNAME',  trim(shell_exec("whoami")));
}

// zruntime settings
define('ZRUNTIME_USERNAME', "membase");
define('ZRUNTIME_PASSWORD', "m3mb@s3@p1t00l");
define('EVN', "auto");

// temp config file path
define('TEMP_CONFIG', RESULT_FOLDER."/temp_config");


		// *** Test constants *** ///
		
	// game_id used for backup tools
define('GAME_ID', "membase");

// dummy files used for disk mapper
define('DUMMY_FILE_1', "/tmp/dummy_file_1"); // 1k size
define('DUMMY_FILE_2', "/tmp/dummy_file_2"); // 1k size
define('DUMMY_FILE_1GB' ,"/tmp/dummy_file_1gb");

	// used by storage server testsuites
define('TEMP_BACKUP_FOLDER', "/tmp/output_mbb/");
define('TEMP_OUTPUT_FILE_PATTERN', TEMP_BACKUP_FOLDER."test-%.mbb");	
define('TEMP_OUTPUT_FILE_0', TEMP_BACKUP_FOLDER."test-00000.mbb");
define('TEMP_OUTPUT_FILE_1', TEMP_BACKUP_FOLDER."test-00001.mbb");





?>