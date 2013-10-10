<?php

define('GENERATE_SSH_KEYS', False);
$test_username = "aasok";
define('IBR_STYLE', 2.0);


$test_machine_list = array("10.80.82.145","10.92.5.21","10.80.9.51","10.80.0.148","10.80.35.181","10.92.5.58","10.92.5.28","10.80.0.149","10.81.220.145","10.80.220.28","10.80.81.156","10.80.81.25","10.81.73.67","10.80.35.165","10.81.73.199","10.81.73.42","10.81.73.30","10.81.73.50","10.80.82.176","10.80.81.170");
$moxi_machine=array("10.36.168.173");
define('MOXI_MACHINE',"10.80.210.42");
$spare_machine=array();
/*
$moxi_rpm=;
$zbase_rpm=;
$vba_dma_rpm=;
$vbs_rpm=;
*/
define('MOXI_PORT',11114);
$vbs_machine=array("10.36.168.173");
$no_of_keys=1126400;
$value_size=1024;
//$last_failed_machine=$test_machine_list[0];
$timeperiod_failure=10800;
define('NO_OF_REPLICAS', 1);
define('NO_OF_VBUCKETS', 1024);
define('ZBASE_VERSION', 1.9);
define('NO_OF_KEYS',1126400);
define('VALUE_SIZE',1024);
define('VBS_IP',$vbs_machine[0]);
define('RESULT_FOLDER','/tmp/continous_test/');
define('BUILD_FOLDER_PATH','/tmp/');
$result_file=RESULT_FOLDER."/result.log";
?>
