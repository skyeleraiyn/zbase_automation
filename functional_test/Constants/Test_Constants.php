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
	define('METADATA_BIG', str_repeat(METADATA_SMALL, 60)); //1024 bytes
	define('METADATA_XL', str_repeat(METADATA_BIG, 2)); //2048 bytes
	define('METADATA_DUMMY', "SHOULD NEVER SEE THIS");

?>