<?php


// log file path
define('VBUCKETMIGRATOR_SYSCONFIG_PATH', "/etc/sysconfig/vbucketmigrator"); 	
define('MEMBASE_LOG_FILE', "/var/log/membase.log");
define('MEMBASE_BACKUP_LOG_FILE', "/var/log/membasebackup.log");
define('VBUCKETMIGRATOR_LOG_FILE', "/var/log/vbucketmigrator.log");
define('DISK_MAPPER_LOG_FILE', "/var/log/disk_mapper.log");

// membase scripts path
define('MEMCACHED_INIT', "/etc/init.d/memcached");
define('MEMCACHED_SYSCONFIG', "/etc/sysconfig/memcached");
define('MEMCACHED_MULTIKV_CONFIG', "/etc/sysconfig/memcached_multikvstore_config");
define('VBUCKETMIGRATOR_INIT', "/etc/init.d/vbucketmigrator");
define('MEMBASE_INIT_SQL', "/opt/membase/membase-init.sql");
define('MEMBASE_BACKUP_INIT', "/etc/init.d/membase-backupd");
define('DISK_MAPPER_CONFIG', "/opt/disk_mapper/config.py");
define('DISK_MAPPER_HOST_MAPPING', "/var/tmp/disk_mapper/host.mapping");

if(MEMBASE_VERSION == 1.6){
	define('MANAGEMENT_FOLDER_PATH', "/opt/membase/bin/ep_engine/management/");
	define('STATS_SCRIPT', MANAGEMENT_FOLDER_PATH."stats");
	define('FLUSHCTL_SCRIPT', MANAGEMENT_FOLDER_PATH."flushctl");
	define('VBUCKETMIGRATOR_SH', "/opt/membase/bin/vbucketmigrator/vbucketmigrator.sh");
} else {
	define('VBUCKETMIGRATOR_SH', "/opt/membase/bin/vbucketmigrator.sh");
	define('MANAGEMENT_FOLDER_PATH', "/opt/membase/lib/python/");
	define('STATS_SCRIPT', MANAGEMENT_FOLDER_PATH."mbstats");
	define('FLUSHCTL_SCRIPT', MANAGEMENT_FOLDER_PATH."mbflushctl");
	define('MEMBASE_BACKUP_SCRIPT', "/opt/membase/membase-backup/membase-backupd");
	define('MEMBASE_RESTORE_SCRIPT', "/opt/membase/membase-backup/membase-restore");
	define('TAP_REGISTRATION_SCRIPT', MANAGEMENT_FOLDER_PATH."mbadm-tap-registration");
	define('DEFAULT_INI_FILE', "/etc/membase-backup/default.ini");
	define('MEMBASE_DB_BACKUP_FOLDER', "/db_backup/");	
}

// storage server script file paths
define('MERGE_INCREMENTAL_INPUT_FILE', "/tmp/input.txt");
define('TEST_SPLITLIB_FILE_PATH', "/opt/membase/membase-backup/t/test_splitlib.py");
define('ZSTORE_CMD_FILE_PATH', "/opt/membase/membase-backup/zstore_cmd");
define('HANDLER_PHP_FILE_PATH', "/var/www/html/membase_backup/handler.php");
define('SAMPLE_CONFIG_PATH', "/var/www/html/membase_backup/config/sample_config.ini");
define('GAMEID_CONFIG_PATH', "/var/www/html/membase_backup/config/".GAME_ID.".ini");
define('DAILY_MERGE_FILE_PATH', "/opt/membase/membase-backup/daily-merge");
define('MASTER_MERGE_FILE_PATH', "/opt/membase/membase-backup/master-merge");
define('MERGE_INCREMENTAL_FILE_PATH', "/opt/membase/membase-backup/merge-incremental");
define('LAST_CLOSED_CHECKPOINT_FILE_PATH', "/var/tmp/last_closed_checkpoint"); //define('LAST_CLOSED_CHECKPOINT_FILE_PATH', "/db/last_closed_checkpoint");
define('MEMBASE_BACKUP_CONSTANTS_FILE', "/opt/membase/membase-backup/consts.py");	
	
?>