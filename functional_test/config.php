<?php

define('TEST_USERNAME', "sharsha");
define('TEST_PASSWORD', "zstore");
$test_machine_list = array("machine-1", "machine-2", "machine-3");		
define('MCMUX_VERSION', "1.0.3.4");
define('MEMBASE_VERSION', 1.7);
define('MEMBASE_CLOUD', "ec2");
define('RUN_WITH_VALGRIND', True);

// Build information
$membase_build = array();
$mcmux_build = array();
$php_pecl_build = array();
$backup_tools_build = array();

		// Declare test_suite_array  	
		// Available suites - php_pecl_smoke_test, php_pecl_regression_test, membase_smoke_test, membase_regression_test
$test_suite_array = declare_test_suite("php_pecl_smoke_test");

define('SKIP_BUILD_INSTALLATION_AND_SETUP', False);
define('EXECUTE_TESTCASES_PARALLELY', True);

$result_folder = "/tmp/results";
$debug_file = $result_folder."/debug_file.log";

include_once "../common/common.php";
include_once "Constants/Constants.php";
include_once "Include/Include.php";

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
				"Persistance.php",
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
				"Getl/Getl_unlock.php"));	
		default:
			echo "Error: undeclared testname \n";
			exit;
	}			
}
				
						
// Following test suites have issue or need to be reviewed
//		"Logger/Logger_sig_stop_server.php"
//		"Logger/Logger_non_existant_server.php"
// 		"ByKey/Large_MultiKey__independent.php",
//		"HugeMultiGet.php"						
//		"Getl_Metadata.php"
//		"ByKey_logger.php"
// 		"ApacheLog.php"
