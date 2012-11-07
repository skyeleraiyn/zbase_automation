<?php
 
	// Define test username
if(isset($test_username) && $test_username <> ""){
	define('TEST_USERNAME',  $test_username);
} else {
	define('TEST_USERNAME',  trim(shell_exec("whoami")));
}

// Service 
define('MEMBASE_SERVER_SERVICE', "membase-server");
define('MEMCACHED_SERVICE', "memcached");
define('VBUCKETMIGRATOR_SERVICE', "vbucketmigrator");
define('SYSLOG_NG_SERVICE', "syslog-ng");
define('MCMUX_SERVICE', "mcmux");
define('MOXI_SERVICE', "moxi");
define('MEMBASE_BACKUP_SERVICE', "membase-backupd");

// rpm name
define('PHP_PECL_PACKAGE_NAME', "php-pecl-memcache-zynga");
define('MCMUX_PACKAGE_NAME', "mcmux");
define('MOXI_PACKAGE_NAME', "moxi");
define('MEMBASE_PACKAGE_NAME', "membase");
define('BACKUP_TOOLS_PACKAGE_NAME', "membase-backup-tools");
define('STORAGE_SERVER_PACKAGE_NAME', "zstore");

// Dependency rpm name
define('JEMALLOC', "jemalloc");

// path
define('VBUCKETMIGRATOR_SYSCONFIG_PATH', "/etc/sysconfig/vbucketmigrator"); 	
define('MEMBASE_LOG_FILE', "/var/log/membase.log");
define('MEMBASE_BACKUP_LOG_FILE', "/var/log/membasebackup.log");
define('VBUCKETMIGRATOR_LOG_FILE', "/var/log/vbucketmigrator.log");
define('MEMCACHED_INIT', "/etc/init.d/memcached");
define('MEMCACHED_SYSCONFIG', "/etc/sysconfig/memcached");
define('MEMCACHED_MULTIKV_CONFIG', "/etc/sysconfig/memcached_multikvstore_config");
define('VBUCKETMIGRATOR_INIT', "/etc/init.d/vbucketmigrator");
define('MEMBASE_INIT_SQL', "/opt/membase/membase-init.sql");
define('MEMBASE_BACKUP_INIT', "/etc/init.d/membase-backupd");
if(MEMBASE_VERSION == 1.6)
	define('VBUCKETMIGRATOR_SH', "/opt/membase/bin/vbucketmigrator/vbucketmigrator.sh");
else
	define('VBUCKETMIGRATOR_SH', "/opt/membase/bin/vbucketmigrator.sh");

// Port
define('MEMBASE_PORT_NO', 11211);


// Process names
define('MEMCACHED_PROCESS', "memcached");
define('MCMUX_PROCESS', "mcmux");
define('MOXI_PROCESS', "moxi");
define('VBUCKETMIGRATOR_PROCESS', "vbucketmigrator");


	// Backup tools rpm
define('GAME_ID', "membase");

		// Script

if(MEMBASE_VERSION == 1.6){
	define('MANAGEMENT_FOLDER_PATH', "/opt/membase/bin/ep_engine/management/");
	define('STATS_SCRIPT', MANAGEMENT_FOLDER_PATH."stats");
	define('FLUSHCTL_SCRIPT', MANAGEMENT_FOLDER_PATH."flushctl");
} else {
	define('MANAGEMENT_FOLDER_PATH', "/opt/membase/lib/python/");
	define('STATS_SCRIPT', MANAGEMENT_FOLDER_PATH."mbstats");
	define('FLUSHCTL_SCRIPT', MANAGEMENT_FOLDER_PATH."mbflushctl");
	define('MEMBASE_BACKUP_SCRIPT', "/opt/membase/membase-backup/membase-backupd");
	define('MEMBASE_RESTORE_SCRIPT', "/opt/membase/membase-backup/membase-restore");
	define('TAP_REGISTRATION_SCRIPT', MANAGEMENT_FOLDER_PATH."mbadm-tap-registration");
	define('DEFAULT_INI_FILE', "/etc/membase-backup/default.ini");
	define('MEMBASE_DB_BACKUP_FOLDER', "/db_backup/");
}

	// storage server constants
	define('MERGE_INCREMENTAL_INPUT_FILE', "/tmp/input.txt");
	define('TEMP_BACKUP_FOLDER', "/tmp/output_mbb/");
	define('TEMP_OUTPUT_FILE_PATTERN', TEMP_BACKUP_FOLDER."test-%.mbb");	
	define('TEMP_OUTPUT_FILE_0', TEMP_BACKUP_FOLDER."test-00000.mbb");
	define('TEMP_OUTPUT_FILE_1', TEMP_BACKUP_FOLDER."test-00001.mbb");
	define('TEST_SPLITLIB_FILE_PATH', "/opt/membase/membase-backup/t/test_splitlib.py");
	define('ZSTORE_CMD_FILE_PATH', "/opt/membase/membase-backup/zstore_cmd");
	define('HANDLER_PHP_FILE_PATH', "/var/www/html/membase_backup/handler.php");
	define('SAMPLE_CONFIG_PATH', "/var/www/html/membase_backup/config/sample_config.ini");
	define('GAMEID_CONFIG_PATH', "/var/www/html/membase_backup/config/".GAME_ID.".ini");
	define('DAILY_MERGE_FILE_PATH', "/opt/membase/membase-backup/daily-merge");
	define('MASTER_MERGE_FILE_PATH', "/opt/membase/membase-backup/master-merge");
	define('MERGE_INCREMENTAL_FILE_PATH', "/opt/membase/membase-backup/merge-incremental");
	define('LAST_CLOSED_CHECKPOINT_FILE_PATH', "/db/last_closed_checkpoint");
	define('MEMBASE_BACKUP_CONSTANTS_FILE', "/opt/membase/membase-backup/consts.py");


// if request has to be passed through mcmux / moxi
$mcmux_process = trim(shell_exec('/sbin/pidof '.MCMUX_PROCESS), "\n");
$moxi_process = trim(shell_exec('/sbin/pidof '.MOXI_PROCESS), "\n");
if(is_numeric($mcmux_process) or is_numeric($moxi_process)){
	// mcmux takes a precedence if both the proxy servers are running
	// Ensure to run only one at time
	if(is_numeric($mcmux_process)){
		define('PROXY_RUNNING', 'unix:///var/run/mcmux/mcmux.sock');
	} else {
		define('PROXY_RUNNING', 'unix:///var/run/moxi/moxi.sock');
	}	
	ini_set('memcache.proxy_enabled', 1);
	ini_set('memcache.proxy_host', PROXY_RUNNING);
} else {
	define('PROXY_RUNNING', FALSE);
}

// Base file path
 // Ensure to replace correct value for generating HOME_DIRECTORY constant -- current path is functional_test/Constants
define('HOME_DIRECTORY', str_replace("common", "", dirname(__FILE__)));
define('BASE_FILES_PATH', HOME_DIRECTORY."common/misc_files/".MEMBASE_VERSION."_files/");


// Path for Latest released RPM list
define('LATEST_RELEASED_RPM_LIST_PATH', "https://zynga-membase.s3.amazonaws.com/released_rpms/latest_released_rpms");
define('LATEST_RELEASED_RPM_LIST_LOCAL_PATH', BUILD_FOLDER_PATH."latest_released_rpms");

// temp config file path
define('TEMP_CONFIG', RESULT_FOLDER."/temp_config");

// Include all php files under this directory and sub-directory
common::include_all_php_files(dirname(__FILE__));

class common{
	public function include_all_php_files($path_to_include){
		$path_to_include = dirname($path_to_include."/*");
		$phpfiles = glob($path_to_include."/*.php");
		foreach($phpfiles as $phpfile){
				include_once $phpfile;
			//	echo "including file $phpfile \n"; //	Debug purpose only
		}
		$directory_list = glob($path_to_include."/*", GLOB_ONLYDIR);
		foreach($directory_list as $directory_path){
			self::include_all_php_files($directory_path);
		}
		return 1;
	}
}
?>