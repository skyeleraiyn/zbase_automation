<?php

class tap_commands{
	public function deregister_tap_name($server_name, $tapname){
		$command_to_be_executed = "python26 ".TAP_REGISTRATION_SCRIPT." -h localhost:".MEMBASE_PORT_NO." -d $tapname";
		return remote_function::remote_execution($server_name, $command_to_be_executed);
	}

	public function register_tap_name($server_name, $tapname, $tap_registration_option = NULL){
		$command_to_be_executed = "python26 ".TAP_REGISTRATION_SCRIPT." -h localhost:".MEMBASE_PORT_NO." -r $tapname";
		if($tap_registration_option){
			$command_to_be_executed = $command_to_be_executed.$tap_registration_option;
		}
		return remote_function::remote_execution($server_name, $command_to_be_executed);
	}

	public function register_replication_tap_name($server_name, $tap_registration_option = NULL){
		return self::register_tap_name($server_name, "replication", $tap_registration_option);
	}
	
	public function register_multiple_taps($server_name, $no_of_taps, $tap_registration_option = NULL) {
		for($i=1;$i<=$no_of_taps;$i++) {
			self::register_tap_name($server_name, "replication_$i", $tap_registration_option);
		}
		return 1;
	}
	public function register_backup_tap_name($server_name, $tap_registration_option = NULL){
		return self::register_tap_name($server_name, "backup", $tap_registration_option);
	}
	
	public function deregister_replication_tap_name($server_name){
		return self::deregister_tap_name($server_name, "replication");
	}
	
	public function deregister_backup_tap_name($server_name){
		return self::deregister_tap_name($server_name, "backup");
	}	
}	
?>