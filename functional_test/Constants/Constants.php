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

	define('TEST_FOLDER_PATH', "Test_suites/");

	define('CHK_MAX_ITEMS_MIN', 100);
	define('CHK_PERIOD_MIN', 60);

	// required to test getl function	
	define('GETL_TIMEOUT', 2);		

	// pecl logconf path
	define('LOGCONF_PATH', "/etc/pecl-log/logconf");
	define('LOGCONF_LOCAL_FOLDER_PATH', "Include/logconf_file/");
	
		// Bykey
	define('SHARDKEY1', 2);
	define('SHARDKEY2', 3);
	define('SHARDKEY3', 4);
	define('TIMEOUT', 0);
	define('CASVALUE', 0);
	define('MICRO_TO_SEC', 1000000);

	define('METADATA',  'abcde');
	define('METADATA_SMALL', "1234567890ABCDEF"); //16 bytes
	define('METADATA_BIG', str_repeat(METADATA_SMALL, 64)); //1024 bytes
	define('METADATA_XL', str_repeat(METADATA_BIG, 2)); //2048 bytes
	define('METADATA_DUMMY', "SHOULD NEVER SEE THIS");
?>
