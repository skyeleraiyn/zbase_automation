<?php


// Port
define('ZBASE_PORT_NO', 11211);

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
