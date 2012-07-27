<?php

define('TEST_USERNAME', "root");
define('TEST_PASSWORD', "");
$test_machine_list = array("HUD_PARAM_TEST_HOST_ARRAY");		
define('MEMBASE_VERSION', 1.7);
define('MEMBASE_CLOUD', "HUD_PARAM_CLOUD_NAME");
define('RESULT_FOLDER', "/tmp/results");

define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('TEST_IGBINARY_FLAGS', True);
define('SKIP_BUILD_INSTALLATION_AND_SETUP', True);
define('RUN_WITH_VALGRIND', True);

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
				"ByKey/ByKey__independent.php");
		case "php_pecl_regression_test":
			return array_merge(declare_test_suite("php_pecl_smoke_test"), 
				array(					
				"Compressed_key_length.php",
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
				"ByKey/ByKeyEviction__independent.php"));
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
				"Replication__independent.php");
		case "membase_regression_test":
			return array_merge(declare_test_suite("membase_smoke_test"), 
				array(	 
				"Serialize/Negative_IGBSerialize_BzipUncompress.php",	
				"Serialize/Negative_Serialize_Uncompress.php",						
				"Getl/Getl_Metadata.php",
				"Getl/Getl_evict.php",
				"Getl/Getl_timeout.php",
				"Getl/Getl_update.php",
				"Getl/Getl_expiry.php",
				"Getl/Getl_unlock.php",
				"Persistance/Persistance_basic.php",
				"Stats/Stats_basic.php"));	
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
