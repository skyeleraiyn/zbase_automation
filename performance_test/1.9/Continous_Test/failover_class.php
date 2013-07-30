<?php
class Failover_class{
	protected $vbs_ip;
	protected $machine_array = array();
	protected $vbs_port;
	function __construct(){
		global $test_machine_list;
		$this->machine_array = $test_machine_list;
		
		}
	public function kill_membase($machine){
		global $test_machine_list;
		membase_setup::kill_membase_server($machine);	
		sleep(150);
		membase_setup::clear_restart_membase($machine);
		}

	public function reshard_up($machine){
		global $test_machine_list;
		vbs_functions::add_server_to_cluster($machine);
		
		}
	public function reshard_down($machine){
		global $test_machine_list;
		membase_setup::clear_restart_membase($machine);
		vbs_functions::remove_server_from_cluster($machine);
		sleep(100);
		membase_setup::clear_restart_membase($machine);
		}

}
	

?>
