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

// Pecl Logging constants
define('MC_SUCCESS', 0x0);
define('MC_STORED', 0x1);
define('MC_NOT_STORED', 0x2);
define('MC_NOT_FOUND', 0x3);
define('MC_EXISTS', 0x4);
define('MC_TMP_FAIL', 0x5);
define('MC_SERVER_MEM_ERROR', 0x6);
define('MC_LOCK_ERROR', 0x7);
define('MC_ERROR', 0x8);
define('MC_CLNT_ERROR', 0x9);
define('MC_SERVER_ERROR', 0x10);
define('MC_MALFORMD', 0x12);
define('MC_ONLY_END', 0x13);
define('MC_NON_NUMERIC_VALUE', 0x14);

define('INTERNAL_ERROR', 100);
define('PROXY_CONNECT_FAILED', 101);
define('VERSION_FAILED', 102);
define('POOL_NT_FOUND', 103);
define('SVR_NT_FOUND', 104);
define('READLINE_FAILED', 105);
define('SVR_OPN_FAILED', 106);
define('COMPRSS_FAILED', 107);
define('PARSE_ERROR', 108);
define('PREPARE_KEY_FAILED', 109);
define('INVALID_CB', 110);
define('INVALID_PARAM', 111);
define('INVALID_CAS', 112);
define('UNLOCKED_FAILED', 113);
define('FLUSH_FAILED', 114);
define('CLOSE_FAILED', 115);
define('CMD_FAILED', 116);
define('SERVER_NO_RESP', 117);
define('MC_UNLOCKED', 200);
define('MC_DELETED', 201);

if(defined('TEST_HOST_1')){
	if(PROXY_RUNNING){
		define('SERVER_NO_RESP_HOSTNAME', "proxy");
		define('SERVER_NO_RESP_DUMMY_HOSTNAME', "proxy");
		define('SERVER_NO_RESP_RES_TIME', 0);
	} else {
		define('SERVER_NO_RESP_HOSTNAME', TEST_HOST_1);
		define('SERVER_NO_RESP_DUMMY_HOSTNAME', "dummy");
		define('SERVER_NO_RESP_RES_TIME', 10);
	}
}
?>
