<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "aasok";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user
#define('TEST_USERNAME', "ppratap");


/*$test_machine_list = array("netops-dgm-ibr-test-32.ca2.zynga.com",
			"netops-dgm-ibr-test-33.ca2.zynga.com",
			"netops-dgm-ibr-test-34.ca2.zynga.com",
			"netops-dgm-ibr-test-35.ca2.zynga.com");
$test_machine_list = array("netops-dgm-ibr-test-2-chef-production-dm.ca2.zynga.com",
                        "netops-dgm-ibr-test-3-chef-production-dm-spare.ca2.zynga.com",
                        "netops-dgm-ibr-test-11.ca2.zynga.com",
                        "netops-dgm-ibr-test-12.ca2.zynga.com");
*/
#$test_machine_list = array("10.36.200.32", " 10.36.194.50", );
#$test_machine_list = array("10.36.193.163", "10.36.194.50", "10.36.200.32", "10.36.166.46");
#$test_machine_list = array("10.80.0.161","10.80.0.176","10.81.73.72","10.80.18.173");
$test_machine_list = array("10.36.193.163", "10.36.194.50", "10.36.200.32", "10.36.199.30");
$secondary_machine_list = array("10.36.193.180","10.36.194.61","10.36.200.34","10.36.199.31");
$spare_machine_list = array("10.36.166.46");
#$storage_server_pool = array("netops-demo-mb-337.va2.zynga.com");	

define('IBR_STYLE', 1.0);
//define('DISK_MAPPER_SERVER_ACTIVE', "netops-demo-mb-336.va2.zynga.com");
//define('DISK_MAPPER_SERVER_PASSIVE', "");
//define('ACTIVE_DISKMAPPER_KEY', "ACTIVE_MCS_DM");

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
$moxi_machines = array("10.36.168.173");
define('VBS_IP',"10.36.168.173");
define('VBS_CONFIG', "/etc/sysconfig/vbucketserver");
define('NO_OF_REPLICAS', 1);
define('NO_OF_VBUCKETS', 32);
//define('MOXI_CONFIG', "/etc/sysconfig/moxi");
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
