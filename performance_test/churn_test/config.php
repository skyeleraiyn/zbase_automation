<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user
$client_machines_list = array("membase-cluster-003", "membase-cluster-110");
define('MEMBASE_VERSION', 1.7);
define('MASTER_SERVER', "membase-cluster-004");
define('SLAVE_SERVER_1', "membase-cluster-005");
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('SKIP_BUILD_INSTALLATION', True);
define('SKIP_BASEFILES_SETUP', True);

define('BLOB_SIZE', 1024);
define('INSTALL_BASE', 100000000);
define('MAU', INSTALL_BASE / 2);
define('DAU', MAU / 4);
define('DAU_CHANGE', 0.4);
define('ONE_DAY_DURATION', 3600);
define('TEST_EXECUTION_THREADS', 10);
define('SET_GET_ratio', 3);

// Build information
$php_pecl_build = array("php-pecl-memcache-zynga-2.5.0.1-5.2.10.x86_64.rpm");
$membase_build = array("membase-1.7.4_4-1.x86_64.rpm");

define('RESULT_FOLDER', "/tmp/results");



// For DGM 1:4 1k = 100M, 10k = 20M, 25k = 8M, 50k = 4M


?>

