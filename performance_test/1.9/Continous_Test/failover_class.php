<?php
class Failover_class{
	protected $vbs_ip;
	protected $machine_array = array();
	protected $vbs_port;
	function __construct(){
		global $test_machine_list;
		$this->machine_array = $test_machine_list;
		}

	//Function to kill zbase
	public function kill_zbase($machine){
		global $test_machine_list;
		zbase_setup::kill_zbase_server($machine);	
		sleep(150);
		zbase_setup::clear_restart_zbase($machine);
		}

	//Function to reshard up
	public function reshard_up($machine){
		global $test_machine_list;
		vbs_functions::add_server_to_cluster($machine);
		
		}

	//Function to reshard down
	public function reshard_down($machine){
		global $test_machine_list;
		zbase_setup::clear_restart_zbase($machine);
		vbs_functions::remove_server_from_cluster($machine);
		sleep(100);
		zbase_setup::clear_restart_zbase($machine);
		}

}
	

?>
