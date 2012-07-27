<?php

	define('GETL_TIMEOUT', 2);		// required to test getl function	
	
	define('SHARDKEY1', 2);
	define('SHARDKEY2', 3);
	define('SHARDKEY3', 4);
	define('TIMEOUT', 0);
	define('CASVALUE', 0);
	define('MICRO_TO_SEC', 1000000);

	define('CHK_MAX_ITEMS_MIN', 100);
	define('CHK_PERIOD_MIN', 60);

	define('METADATA_SMALL', "1234567890ABCDEF"); //16 bytes
	define('METADATA_BIG', str_repeat(METADATA_SMALL, 64)); //1024 bytes
	define('METADATA_XL', str_repeat(METADATA_BIG, 2)); //2048 bytes
	define('METADATA_DUMMY', "SHOULD NEVER SEE THIS");

	
	// MANAGEMENT SCRIPTS FROM BINARY PATH
	define('MANAGEMENT_SCRIPT_BINARY_PATH',"/opt/membase/bin/");
	define('BIN_STATS_SCRIPT', MANAGEMENT_SCRIPT_BINARY_PATH."mbstats");
	define('BIN_FLUSHCTL_SCRIPT', MANAGEMENT_SCRIPT_BINARY_PATH."mbflushctl");
	define('BIN_TAP_REGISTRATION_SCRIPT',MANAGEMENT_SCRIPT_BINARY_PATH."mbadm-tap-registration");
?>