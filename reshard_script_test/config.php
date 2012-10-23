<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user

// Need 5 machines to execute this test
$test_machine_list = 
	array(	"dgm-template-test-001", 
			"dgm-template-test-002", 
			"vandana-dgm-003", 
			"vandana-dgm-004", 
			"vandana-dgm-005");

define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('RESULT_FOLDER', "/tmp/results");
define('SKIP_BUILD_INSTALLATION', False);
define('MEMBASE_VERSION', 1.7);

/* 	Build information
	Can specify either membase or membase + php-pecl combination. Should specify Source => Destination 
	Membase1 => Membase2 or 
	Membase1:php-pecl1 => Membase2:Php-pecl2 
*/

$build_combination_list = array(
	"membase-1.7.3r_25_56_gad973d4-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm => membase-1.7.3r_25_56_gad973d4-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm", 
	"membase-1.7.3r_25_56_gad973d4-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm => membase-1.7.3r_23-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm");

include_once "../common/common.php";
common::include_all_php_files("Include/");


?>

