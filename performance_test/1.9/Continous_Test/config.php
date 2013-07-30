<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "aasok";
define('IBR_STYLE', 2.0);


$test_machine_list = array("10.36.193.163","10.36.194.50","10.36.199.30","10.36.200.32","10.36.166.46");
$moxi_machine=array("10.36.168.173");
define('MOXI_MACHINE',"10.36.168.173");
$spare_machine=array();
/*
$moxi_rpm=;
$membase_rpm=;
$vba_dma_rpm=;
$vbs_rpm=;
*/
define('MOXI_PORT',11114);
$vbs_machine=array("10.36.168.173");
$no_of_keys=10000;
$value_size=1024;
$last_failed_machine=$test_machine_list[0];
$timeperiod_failure=100;
define('NO_OF_REPLICAS', 1);
define('NO_OF_VBUCKETS', 32);
define('MEMBASE_VERSION', 1.9);
define('NO_OF_KEYS',10000);
define('VALUE_SIZE',1024);
?>
