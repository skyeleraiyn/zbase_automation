/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
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
define('ZBASE_VERSION', 1.7);
define('RESHARD_SCRIPT_FOLDER', "reshard");

/* 	Build information
	Can specify either zbase or zbase + php-pecl combination. Should specify Source => Destination 
	Zbase1 => Zbase2 or 
	Zbase1:php-pecl1 => Zbase2:Php-pecl2 
*/

$build_combination_list = array(
	"zbase-1.7.3r_25_56_gad973d4-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm => zbase-1.7.3r_25_56_gad973d4-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm", 
	"zbase-1.7.3r_25_56_gad973d4-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm => zbase-1.7.3r_23-1.x86_64.rpm:::php-pecl-memcache-zynga-2.5.0.0-5.2.10.x86_64.rpm");

include_once "../common/common.php";
common::include_all_php_files("Include/");


?>

