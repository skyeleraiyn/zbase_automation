<?php

// Service 
define('MEMBASE_SERVER_SERVICE', "membase-server");
define('MEMCACHED_SERVICE', "memcached");
define('VBUCKETMIGRATOR_SERVICE', "vbucketmigrator");
define('SYSLOG_NG_SERVICE', "syslog-ng");
define('MCMUX_SERVICE', "mcmux");

// rpm name
define('PHP_PECL_PACKAGE_NAME', "php-pecl-memcache-zynga");
define('MCMUX_PACKAGE_NAME', "mcmux");
define('MEMBASE_PACKAGE_NAME', "membase");
define('BACKUP_TOOLS_PACKAGE_NAME', "membase_backup");

// path
define('VBUCKETMIGRATOR_SYSCONFIG_PATH', "/etc/sysconfig/vbucketmigrator");
define('MEMBASE_DATABASE_PATH', "/db/membase");
define('MEMBASE_LOG_FILE', "/var/log/membase.log");
define('VBUCKETMIGRATOR_LOG_FILE', "/var/log/vbucketmigrator.log");
define('MEMCACHED_INIT', "/etc/init.d/memcached");
define('MEMCACHED_SYSCONFIG', "/etc/sysconfig/memcached");
define('VBUCKETMIGRATOR_INIT', "/etc/init.d/vbucketmigrator");
define('MEMBASE_INIT_SQL', "/opt/membase/membase-init.sql");
if(MEMBASE_VERSION == 1.6)
	define('VBUCKETMIGRATOR_SH', "/opt/membase/bin/vbucketmigrator/vbucketmigrator.sh");
else
	define('VBUCKETMIGRATOR_SH', "/opt/membase/bin/vbucketmigrator.sh");

// Port
define('MEMBASE_PORT_NO', 11211);


// Process names
define('MEMCACHED_PROCESS', "memcached");
define('MCMUX_PROCESS', "mcmux");
define('VBUCKETMIGRATOR_PROCESS', "vbucketmigrator");

/* Define the cloud where membase will be running
 This is required to pull the graphs. Options available ec2, zc1, zc2
*/
$avilable_clouds = array("ec2" => "9236", "zc1" => "22328", "zc2" => "30287");
if(defined('MEMBASE_CLOUD') and (MEMBASE_CLOUD <> "")){
	define('MEMBASE_CLOUD_ID', $avilable_clouds[MEMBASE_CLOUD]);
}

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
	define('DEFAULT_INI_FILE', "/etc/membase-backup/default.in");
}

// if request has to be passed through mcmux
$GLOBALS['mcmux_process'] = trim(shell_exec('/sbin/pidof mcmux'), "\n");
if(is_numeric($GLOBALS['mcmux_process'])){
	define('MCMUX_RUNNING', TRUE);
	ini_set('memcache.proxy_enabled', 1);
	ini_set('memcache.proxy_host', 'unix:///var/run/mcmux/mcmux.sock');
} else {
	define('MCMUX_RUNNING', FALSE);
}

// Build folder path
 // Ensure to replace correct value for generating HOME_DIRECTORY constant -- current path is functional_test/Constants
define('HOME_DIRECTORY', str_replace("common", "", dirname(__FILE__)));
define('BASE_FILES_PATH', HOME_DIRECTORY."common/misc_files/".MEMBASE_VERSION."_files/");
define('BUILD_FOLDER_PATH', HOME_DIRECTORY."common/misc_files/builds/");


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