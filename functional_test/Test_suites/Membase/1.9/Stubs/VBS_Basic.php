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

