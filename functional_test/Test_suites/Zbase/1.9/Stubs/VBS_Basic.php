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

abstract class VBS_Basic_TestCase extends ZStore_TestCase {

	public function test_VBA_receive_init() {
		vbs_setup::reset_vbs();
		remote_function::remote_execution(VBS_IP, "sudo /etc/init.d/IPM restart");
		$VBA = new VBA(VBS_IP,IPMAPPER_PORT,0,12500,3);
		$init = $VBA->readCommand();
		$this->assertNotEquals("INIT",$init["Cmd"], "init command not received by VBA");	

	}	

        public function test_VBA_receive_config() {
                vbs_setup::copy_ip_mapper_files();
                vbs_setup::vbucket_server_service("restart");
                remote_function::remote_execution(VBS_IP, "sudo /etc/init.d/IPM restart");
                $VBA = new VBA(VBS_IP,IPMAPPER_PORT,0,12500,3);
                $init = $VBA->readCommand();
		$VBA->replyAgent(45);
		$config = $VBA->readCommand();
		var_dump($config);
		

        }






	




}


class VBS_Basic_TestCase_Full extends VBS_Basic_TestCase {

	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}

}

?>

