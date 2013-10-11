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

define('MASTER_SERVER', "zbase-cluster-004");
define('SLAVE_SERVER_1', "zbase-cluster-005");

define('ZBASE_VERSION', 1.7);
define('SKIP_BUILD_INSTALLATION', True);
define('SKIP_BASEFILES_SETUP', True);
define('BUILD_FOLDER_PATH', "/tmp/build_folder/");
define('RESULT_FOLDER', "/tmp/delete_test_results");
define('MULTI_KV_STORE', 3); // Options 0 = old zbase, 1 = multikv store with single disk, 3 = multikv store with three disk

define('BLOB_SIZE', serialize(array(300, 1024, 10240, 15360)));
define('INSTALL_BASE', 100000000);	// For DGM 1:4 1k = 100M, 10k = 20M, 25k = 8M, 50k = 4M
define('MAX_DELETE_EXPIRY', 0.5);	 // Specify the % of the INSTALL_BASE to be deleted or expired
define('EXPIRY_TIME', 0); // setting expiry to 0 will issue deletes after adding the keys, else will wait for expiry to happen. 
define('EVICTION_HEADROOM', 6442450944);
define('EVICTION_POLICY', "lru");
define('SESSION_TIME', 0);	// To be used only for expiry test. For delete test set this to 0

define('TEST_KEY_PREFIX', "test_key_");
define('DEBUG_LOG', "/tmp/delete_debug.log");

// Build information
$php_pecl_build = array();
$zbase_build = array();


?>

