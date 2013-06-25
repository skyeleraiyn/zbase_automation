<?php
// Other
define('NO_OF_VBA',8);

// Port
define('MEMBASE_PORT_NO', 11211);
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
