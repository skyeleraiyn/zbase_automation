<?php


// log file path

define('ZBASE_LOG_FILE', "/var/log/zbase.log");
define('ZBASE_BACKUP_LOG_FILE', "/var/log/zbasebackup.log");
define('VBUCKETMIGRATOR_LOG_FILE', "/var/log/vbucketmigrator.log");
define('DISK_MAPPER_LOG_FILE', "/var/log/disk_mapper.log");
define('STORAGE_SERVER_LOG_FILE', "/var/log/storage_server.log");
define('VBS_LOG', "/var/log/vbs.log");
define('CLUSTER_BACKUP_LOG_FILE', "/var/log/vbucketbackupd.log");


// zbase scripts path
define('MEMCACHED_INIT', "/etc/init.d/memcached");
define('VBS_SYSCONFIG_PATH', "/etc/sysconfig/vbucketserver");
define('MOXI_CONFIG', "/etc/sysconfig/moxi");
define('VBUCKETMIGRATOR_SYSCONFIG_PATH', "/etc/sysconfig/vbucketmigrator");
define('MEMCACHED_SYSCONFIG', "/etc/sysconfig/memcached");
define('MEMCACHED_MULTIKV_CONFIG', "/etc/sysconfig/memcached_multikvstore_config");
define('VBUCKETMIGRATOR_INIT', "/etc/init.d/vbucketmigrator");
define('ZBASE_INIT_SQL', "/opt/zbase/zbase-init.sql");
define('ZBASE_BACKUP_INIT', "/etc/init.d/zbase-backupd");
define('DISK_MAPPER_CONFIG', "/opt/disk_mapper/config.py");
define('DISK_MAPPER_HOST_MAPPING', "/var/tmp/disk_mapper/host.mapping");


if(ZBASE_VERSION == 1.6){
	define('MANAGEMENT_FOLDER_PATH', "/opt/zbase/bin/ep_engine/management/");
	define('STATS_SCRIPT', MANAGEMENT_FOLDER_PATH."stats");
	define('FLUSHCTL_SCRIPT', MANAGEMENT_FOLDER_PATH."flushctl");
	define('VBUCKETMIGRATOR_SH', "/opt/zbase/bin/vbucketmigrator/vbucketmigrator.sh");
} else {
	define('VBUCKETMIGRATOR_SH', "/opt/zbase/bin/vbucketmigrator.sh");
	define('MANAGEMENT_FOLDER_PATH', "/opt/zbase/lib/python/");
	define('STATS_SCRIPT', MANAGEMENT_FOLDER_PATH."mbstats");
	define('FLUSHCTL_SCRIPT', MANAGEMENT_FOLDER_PATH."mbflushctl");
	define('ZBASE_BACKUP_SCRIPT', "/opt/zbase/zbase-backup/zbase-backupd");
	define('ZBASE_RESTORE_SCRIPT', "/opt/zbase/zbase-backup/zbase-restore");
	define('TAP_REGISTRATION_SCRIPT', MANAGEMENT_FOLDER_PATH."mbadm-tap-registration");
	define('DEFAULT_INI_FILE', "/etc/zbase-backup/default.ini");
	define('ZBASE_DB_BACKUP_FOLDER', "/db_backup/");
	define('ZBASE_DB_LOCAL_BACKUP_FOLDER', "/db_localbackup/");
	define('LOCAL_DISK_MAPPER_HOST_CONFIG_FILE', "/var/tmp/diskmapper_hostconfig");
}

// storage server script file paths
define('MERGE_INCREMENTAL_INPUT_FILE', "/tmp/input.txt");
define('TEST_SPLITLIB_FILE_PATH', "/opt/zbase/zbase-backup/t/test_splitlib.py");
define('ZSTORE_CMD_FILE_PATH', "/opt/zbase/zbase-backup/zstore_cmd");
define('HANDLER_PHP_FILE_PATH', "/var/www/html/zbase_backup/handler.php");
define('SAMPLE_CONFIG_PATH', "/var/www/html/zbase_backup/config/sample_config.ini");
define('GAMEID_CONFIG_PATH', "/var/www/html/zbase_backup/config/".GAME_ID.".ini");
define('DAILY_MERGE_FILE_PATH', "/opt/zbase/zbase-backup/daily-merge");
define('MASTER_MERGE_FILE_PATH', "/opt/zbase/zbase-backup/master-merge");
define('MERGE_INCREMENTAL_FILE_PATH', "/opt/zbase/zbase-backup/merge-incremental");
define('LAST_CLOSED_CHECKPOINT_FILE_PATH', "/var/tmp/last_closed_checkpoint"); //define('LAST_CLOSED_CHECKPOINT_FILE_PATH', "/db/last_closed_checkpoint");
define('ZBASE_BACKUP_CONSTANTS_FILE', "/opt/zbase/zbase-backup/consts.py");

//ip mapper scripts file path
define("IPMAPPER_PATH", "/var/tmp/");
define("IPMAPPER_LOG", "/var/log/IPM.log");


?>
