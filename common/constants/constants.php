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
 
// zruntime settings
define('ZRUNTIME_USERNAME', "zbase");
define('ZRUNTIME_PASSWORD', "m3mb@s3@p1t00l");
define('EVN', "auto");

// temp config file path
define('TEMP_CONFIG', RESULT_FOLDER."/temp_config");


		// *** Test constants *** ///
		
	// game_id used for backup tools
define('GAME_ID', "zbase");

// dummy files used for disk mapper
define('DUMMY_FILE_1', "/tmp/dummy_file_1"); // 1k size
define('DUMMY_FILE_2', "/tmp/dummy_file_2"); // 1k size
define('DUMMY_FILE_1GB' ,"/tmp/dummy_file_1gb");

	// used by storage server testsuites
define('TEMP_BACKUP_FOLDER', "/tmp/output_mbb/");
define('TEMP_OUTPUT_FILE_PATTERN', TEMP_BACKUP_FOLDER."test-%.mbb");	
define('TEMP_OUTPUT_FILE_0', TEMP_BACKUP_FOLDER."test-00000.mbb");
define('TEMP_OUTPUT_FILE_1', TEMP_BACKUP_FOLDER."test-00001.mbb");
define('STORAGE_SERVER_DRIVE', "/data_1");


?>
