<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user

$test_machine_list = array("10.36.160.57", "10.36.192.167", "10.36.161.171" );
$spare_machine_list = array("10.36.172.60");
$storage_server_pool = array("netops-dgm-ibr-test-30.ca2.zynga.com", "netops-dgm-ibr-test-31.ca2.zynga.com",  "netops-dgm-ibr-test-32.ca2.zynga.com");
$moxi_machines = array("netops-demo-mb-325.va2.zynga.com");
#$storage_server_pool = array("netops-demo-mb-337.va2.zynga.com");	

define('IBR_STYLE', 1.9);
define('DISK_MAPPER_SERVER_ACTIVE', "netops-demo-mb-325.va2.zynga.com");
define('DISK_MAPPER_SERVER_PASSIVE', "");
define('ACTIVE_DISKMAPPER_KEY', "ACTIVE_MCS_1.9");

	// Options 0 = old membase, 1 = multikv store with single disk, 3 = multikv store with three disk
define('MULTI_KV_STORE', 3);
define('MEMBASE_VERSION', 1.9);
define('RESULT_FOLDER', "/tmp/results");
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('SKIP_BUILD_INSTALLATION', True);
define('SKIP_BASEFILES_SETUP', False);
define('RUN_WITH_VALGRIND', True);
define('RUN_WITH_TCPDUMP', False);



//Cluster Related Info
define('VBS_IP',"netops-demo-mb-325.va2.zynga.com");
define('VBS_CONFIG', "/etc/sysconfig/vbucketserver");
define('NO_OF_REPLICAS', 1);
define('NO_OF_VBUCKETS', 64);
define('NO_OF_STORAGE_DISKS', 6);
define('CLUSTER_CAPACITY', 300);
// Build information
// For moxi / mcmux add the rpm under $proxyserver_build
$membase_build = array("");
$proxyserver_build = array("");
$php_pecl_build = array("");

$backup_tools_build = "";
$disk_mapper_build = "";
$storage_server_build = "";

//To support checksum on DI. Checksum will be enabled only if all components support it. 
// Disabling this will stop checksum verification on DI build
define('SUPPORT_CHECKSUM', False);

include_once "Test_suites/Test_suites.php";
		/* Declare test_suite_array Available suites - php_pecl_smoke_test, php_pecl_regression_test, 
			membase_smoke_test, membase_regression_test, storage_server_test, disk_mapper_test, coalescer_test	
		*/
$test_suite_array = Test_suites::declare_test_suite("membase_regression_test");		
