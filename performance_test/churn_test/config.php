<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "";	// Specify the username if auth has to happen from a different user, else it will take the current logged in user
ini_set("memcache.retry_interval", 3);
define('MEMBASE_VERSION', 1.7);
define('MEMBASE_CLOUD', "zc2");

define('MASTER_SERVER', "master-server");
define('SLAVE_SERVER_1', "slave-server");
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('SKIP_BUILD_INSTALLATION', False);

// Build information
$php_pecl_build = array("php-pecl-memcache-zynga-2.4.1.16-5.2.10.x86_64.rpm");
$membase_build = array("membase-1.7.3_lru_eviction-1.x86_64.rpm");

define('RESULT_FOLDER', "/tmp/results");


include_once "../../common/common.php";
include_once "Include/Include.php";


?>

