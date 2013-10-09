<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user

$client_machines_list = array("netops-demo-mb-312.va2.zynga.com");
define('MASTER_SERVER', "netops-demo-mb-raid1-5");
define('SLAVE_SERVER_1', "netops-demo-mb-raid1-6");
define('STORAGE_SERVER_1', "");
define('TEST_HOST_2', SLAVE_SERVER_1);

define('ZBASE_VERSION', 1.7);
define('SKIP_BUILD_INSTALLATION', False);
define('SKIP_BASEFILES_SETUP', False);
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('RESULT_FOLDER', "/tmp/chrun_test_results");
define('MULTI_KV_STORE', 3); // Options 0 = old zbase, 1 = multikv store with single disk, 3 = multikv store with three disk

define('BLOB_SIZE', serialize(array(
					"2" => array(5, 20, 100), 
					"48" => array(128, 256, 512), 
					"5" => array(5120), 
					"45" => array(8192, 16384, 32768))));

define('ENABLE_CHECKSUM', False);
define('INSTALL_BASE_SIZE', 150);	// only in GB's
define('INSTALL_BASE', install_base_number());	// For DGM 1:4 1k = 100M, 10k = 20M, 25k = 8M, 50k = 4M
define('MAU', INSTALL_BASE / 2);
define('DAU', MAU / 4);
define('DAU_CHANGE', 0.4);
define('FETCH_FROM_MEMORY', 96);
define('ONE_DAY_DURATION', 1200);
define('SESSION_TIME', 120);
define('NUMBER_OF_DAYS', 30);
define('TEST_EXECUTION_THREADS', 500);
define('SET_GET_ratio', 3);
define('EVICTION_HEADROOM', 6442450944);
define('EVICTION_POLICY', "lru");

// Build information
$php_pecl_build = array();
$zbase_build = array("zbase-1.8.0_1_zynga-1.x86_64.rpm");
$backup_tools_build = "";

function install_base_number(){
	global $blob_distribution_bracket;
	
	$blob_size_list = unserialize(BLOB_SIZE);
	$distribuation_percentage = 0;
	$average_blob_size = 0;
	foreach($blob_size_list as $percentage => $blob_size_array){
		$average_blob_size += (array_sum($blob_size_array) + count($blob_size_array)* 150 ) * $percentage / 100 /  count($blob_size_array);
		$distribuation_percentage += $percentage;
		$blob_distribution_bracket[] = $distribuation_percentage;
	}	
	if(end($blob_distribution_bracket) <> 100) {
		echo "Blob distribution is incorrect \n";
		exit;
	}	
	
	$install_base = (ceil((INSTALL_BASE_SIZE * 1073741824 ) / $average_blob_size));
	if($install_base > 250000000) $install_base = 250000000;
	return $install_base;
}





?>


