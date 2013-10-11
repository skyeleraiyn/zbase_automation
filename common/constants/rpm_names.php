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
 


// rpm name
define('PHP_PECL_PACKAGE_NAME', "php-pecl-memcache-zynga");
define('MCMUX_PACKAGE_NAME', "mcmux");
define('MOXI_PACKAGE_NAME', "moxi");
define('ZBASE_PACKAGE_NAME', "zbase");
define('BACKUP_TOOLS_PACKAGE_NAME', "zbase-backup-tools");
define('STORAGE_SERVER_PACKAGE_NAME', "zstore");
define('STORAGE_SERVER_PACKAGE_NAME_2', "storage-server");
define('DISK_MAPPER_PACKAGE_NAME', "disk-mapper");

// Dependency rpm name
define('JEMALLOC', "jemalloc");

// Path for Latest released RPM list
define('LATEST_RELEASED_RPM_LIST_PATH', "https://zynga-zbase.s3.amazonaws.com/released_rpms/latest_released_rpms");
define('LATEST_RELEASED_RPM_LIST_LOCAL_PATH', BUILD_FOLDER_PATH."latest_released_rpms");



?>
