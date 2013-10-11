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
