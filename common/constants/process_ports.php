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
// Other
define('NO_OF_VBA',8);

// Port
define('ZBASE_PORT_NO', 11211);
define('MOXI_PORT_NO', 11114);
define('IPMAPPER_PORT', 12000);
define('VBA_START_PORT',12500);
define('MOXI_START_PORT', 13500);
define('VBA_START_IP', 4);
define('MOXI_START_IP', 50);
define('VBS_PORT', 14000);


// Process names
define('MEMCACHED_PROCESS', "memcached");
define('MCMUX_PROCESS', "mcmux");
define('MOXI_PROCESS', "moxi");
define('VBUCKETMIGRATOR_PROCESS', "vbucketmigrator");

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

?>
