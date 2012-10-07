<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "root";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user
$test_machine_list = array("HUD_PARAM_TEST_HOST_ARRAY");
define('STORAGE_SERVER', "HUD_PARAM_STORAGE_SERVER");		
define('MEMBASE_VERSION', 1.7);
define('RESULT_FOLDER', "/tmp/results");
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('SKIP_BUILD_INSTALLATION_AND_SETUP', False);
define('RUN_WITH_VALGRIND', True);
define('RUN_WITH_TCPDUMP', False);

// Build information
// For moxi / mcmux add the rpm under $proxyserver_build
$membase_build = array("HUD_PARAM_MEMBASE_BUILD");
$proxyserver_build = array("HUD_PARAM_PROXYSERVER_BUILD");
$php_pecl_build = array("HUD_PARAM_PECL_BUILD");
$backup_tools_build = array("HUD_PARAM_BACKUP_TOOL_BUILD");

		// Declare test_suite_array  	
		// Available suites - php_pecl_smoke_test, php_pecl_regression_test, membase_smoke_test, membase_regression_test
$test_suite_array = declare_test_suite("HUD_PARAM_TEST_SUITE_ARRAY");


function declare_test_suite($testname){

	switch ($testname){
		case "php_pecl_smoke_test":
			return array(
				"Basic/Basic.php",
				"Logger/Logger_basic.php",					
				"Basic/Append_Prepend.php",
				"Basic/CAS.php",
				"Basic/CAS2.php",
				"Checksum.php",
				"Serialize/IGBinary_Serialize_Bzip.php",						 
				"Getl/Getl_basic.php",
				"Getl/Getl_unlock.php",
				"Mcmux.php",
				"ByKey/ByKey__independent.php",
				"1.7/Data_Integrity/DI_basic.php");
		case "php_pecl_regression_test":
			return array_merge(declare_test_suite("php_pecl_smoke_test"), 
				array(					
				"Basic/Re_entrant.php",	
				"Logger/Logger_invalid_rule.php",
				"Eviction.php",
				"Leak.php",					 
				"Serialize/Negative_IGBSerialize_BzipUncompress.php",	
				"Logger/Logger_non_existant_keys.php",
				"Serialize/Negative_Serialize_Uncompress.php",						
				"Getl/Getl_Metadata.php",
				"Getl/Getl_basic.php",
				"Getl/Getl_append_prepend.php",
				"Getl/Getl_evict.php",
				"Getl/Getl_timeout.php",
				"Getl/Getl_update.php",
				"Getl/Getl_expiry.php",
				"Getl/Getl_unlock.php",
				"Logger/Logger_out_of_memory_server.php",
				"ByKey/ByKeyEviction__independent.php",
				"Basic/TestKeyValueLimit.php"));
		case "membase_smoke_test":
			return array(
				"Basic/Basic.php",
				"Basic/Append_Prepend.php",
				"Basic/CAS.php",
				"Basic/CAS2.php",
				"Eviction.php",
				"Checksum.php",
				"Serialize/IGBinary_Serialize_Bzip.php",						 					
				"Getl/Getl_basic.php",
				"Getl/Getl_append_prepend.php",
				"Replication__independent.php",
				"1.7/Backup_Tests__independent.php",
				"1.7/Core_Merge__independent.php",
				"1.7/Data_Integrity/DI_basic.php",
				"1.7/Data_Integrity/DI_IBR__independent.php");
		case "membase_regression_test":
			return array_merge(declare_test_suite("membase_smoke_test"), 
				array(	 
				"Python_Scripts.php",	
				"Serialize/Negative_IGBSerialize_BzipUncompress.php",	
				"Serialize/Negative_Serialize_Uncompress.php",						
				"Getl/Getl_Metadata.php",
				"Getl/Getl_evict.php",
				"Getl/Getl_timeout.php",
				"Getl/Getl_update.php",
				"Getl/Getl_expiry.php",
				"Getl/Getl_unlock.php",
				"Persistance/Persistance_basic.php",
				"Stats/Stats_basic.php",
				"1.7/Backup_Daemon__independent.php",
				"1.7/Core_Parameters_Test.php",
				"1.7/Daily_Merge__independent.php",
				"1.7/Master_Merge__independent.php",
				"1.7/1.7_Replication__independent.php",
				"1.7/Tap__independent.php",
				"1.7/Data_Integrity/DI_IBR_negative__independent.php"));	
		default:
			echo "Error: undeclared testname \n";
			exit;
	}			
}
				
include_once "../common/common.php";
include_once "Constants/Constants.php";
include_once "Include/Include.php";				
						
// Following test suites have issue or need to be reviewed
//		"Logger/Logger_sig_stop_server.php"
//		"Logger/Logger_non_existant_server.php"
// 		"ByKey/Large_MultiKey__independent.php",
//		"HugeMultiGet.php"						
//		"ByKey_logger.php"
// 		"ApacheLog.php"
