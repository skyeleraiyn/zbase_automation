<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "root";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user

$test_machine_list = array("HUD_PARAM_TEST_HOST_ARRAY");
$storage_server_pool = array("HUD_PARAM_STORAGE_SERVER");	
define('DISK_MAPPER_SERVER_ACTIVE', "HUD_PARAM_DISK_MAPPER_SERVER_ACTIVE");
define('DISK_MAPPER_SERVER_PASSIVE', "HUD_PARAM_DISK_MAPPER_SERVER_PASSIVE");
define('ACTIVE_DISKMAPPER_KEY', "HUD_PARAM_ACTIVE_DM_KEY");


	// Options 0 = old zbase, 1 = multikv store with single disk, 3 = multikv store with three disk
define('MULTI_KV_STORE', 0);
define('ZBASE_VERSION', 1.7);
define('RESULT_FOLDER', "/tmp/results");
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('SKIP_BUILD_INSTALLATION', False);
define('SKIP_BASEFILES_SETUP', False);
define('RUN_WITH_VALGRIND', True);
define('RUN_WITH_TCPDUMP', False);


// Build information
// For moxi / mcmux add the rpm under $proxyserver_build
$zbase_build = array("HUD_PARAM_ZBASE_BUILD");
$proxyserver_build = array("HUD_PARAM_PROXYSERVER_BUILD");
$php_pecl_build = array("HUD_PARAM_PECL_BUILD");

$backup_tools_build = "HUD_PARAM_BACKUP_TOOL_BUILD";
$disk_mapper_build = "HUD_PARAM_DISK_MAPPER_BUILD";
$storage_server_build = "HUD_PARAM_STORAGE_SERVER_BUILD";

//To support checksum on DI. Checksum will be enabled only if all components support it. 
// Disabling this will stop checksum verification on DI build
define('SUPPORT_CHECKSUM', True);

include_once "Test_suites/Test_suites.php";
		/* Declare test_suite_array Available suites - php_pecl_smoke_test, php_pecl_regression_test, 
			zbase_smoke_test, zbase_regression_test, storage_server_test, disk_mapper_test	
		*/
$test_suite_array = Test_suites::declare_test_suite("HUD_PARAM_TEST_SUITE_ARRAY");			
 
?>
