<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user

$client_machines_list = array("dual-test-mb-001");
define('MASTER_SERVER', "mkv-client-04");
define('SLAVE_SERVER_1', "mkv-client-05");
define('STORAGE_SERVER_1', "netops-demo-mb-337.va2.zynga.com");

define('ZBASE_VERSION', 1.7);
define('SKIP_BUILD_INSTALLATION', True);
define('SKIP_BASEFILES_SETUP', True);
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('RESULT_FOLDER', "/tmp/churn_test_results");
define('MULTI_KV_STORE', 0); // Options 0 = old zbase, 1 = multikv store with single disk, 3 = multikv store with three disk

define('BLOB_SIZE', serialize(array(1024, 10240)));
define('INSTALL_BASE_SIZE', 30);	// only in GB's
define('INSTALL_BASE', install_base_number());	// For DGM 1:4 1k = 100M, 10k = 20M, 25k = 8M, 50k = 4M
define('MAU', INSTALL_BASE / 2);
define('DAU', MAU / 4);
define('DAU_CHANGE', 0.4);
define('ONE_DAY_DURATION', 20160);
define('SESSION_TIME', 600);
define('TEST_EXECUTION_THREADS', 40);
define('SET_GET_ratio', 3);
define('EVICTION_HEADROOM', 6442450944);
define('EVICTION_POLICY', "lru");

// Build information
$php_pecl_build = array();
$zbase_build = array();





function install_base_number(){
	$total_data_size = 0;
	$blob_size = unserialize(BLOB_SIZE);
	foreach($blob_size as $memory_size){
		if(($memory_size) > 1024){
			$memory_size = $memory_size + 150;
		} else {
			$memory_size = $memory_size + 153;
		}
		$total_data_size = $total_data_size + $memory_size;
	}
	$install_base = count($blob_size) * (ceil((INSTALL_BASE_SIZE * 1073741824 ) / $total_data_size));
	if($install_base > 100000000) $install_base = 100000000;
	return $install_base;
}





?>

