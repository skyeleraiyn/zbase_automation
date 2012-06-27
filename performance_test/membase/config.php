<?php

ini_set("memcache.retry_interval", 3);
define('TEST_USERNAME', "sharsha");
define('TEST_PASSWORD', "zstore");
define('MEMBASE_VERSION', 1.7);
define('MEMBASE_CLOUD', "zc2");

define('MASTER_SERVER', "master-server");
define('SLAVE_SERVER_1', "slave-server");

// Build information
$php_pecl_build = array();
$membase_build = array();
$mcmux_build = array();
$backup_tools_build = array();

		// Delcare Data sample size in this format "data_size" => "no_of_keys"
// 70GB machine
$data_sample = array("51200" => "1500000", "25600" => "2500000", "15360" => "4200000", "8192" => "8000000", "1024" => "50000000", "256" => "80000000", "20" => "100000000"); 

define('SKIP_BUILD_INSTALLATION_AND_SETUP', False);
	// quick_run gives an option to run only set during performance run and reduces run time by 60%. 
	// To involve both set and get, set it False. Default option is False.
define('QUICK_RUN', False);

$result_folder = "/tmp/results";
$debug_file = $result_folder."/debug_file.log";


// 62GB machine
//$data_sample = array("51200" => "1300000", "25600" => "2300000", "15360" => "3900000", "8192" => "7200000", "1024" => "40000000", "256" => "80000000", "20" => "100000000"); 
// 55GB machine
//$data_sample = array("51200" => "1100000", "25600" => "1900000", "15360" => "3300000", "8192" => "6200000", "1024" => "39000000", "256" => "80000000", "20" => "100000000");
// 15GB machine 
//$data_sample = array("51200" => "250000", "25600" => "400000", "15360" => "700000", "8192" => "1000000", "1024" => "8000000", "256" => "20000000", "20" => "50000000"); 

include_once "../../common/common.php";
include_once "Include/Include.php";


?>

