<?php

class process_functions{

	public function check_process_exists($remote_machine_name, $process_name){
		$check_process_exists = explode("\n", general_function::execute_command("/sbin/pidof $process_name", $remote_machine_name));
		$check_process_exists = explode(" ", end($check_process_exists));
		if (is_numeric($check_process_exists[0])){
			return $check_process_exists[0];
		} else {	
			return False;	
		}	
	}


	public function kill_process($remote_machine_name, $process_name) {
		for($iattempt = 0 ; $iattempt < 5 ; $iattempt++) {
			if (self::check_process_exists($remote_machine_name, $process_name)) {
				remote_function::remote_execution($remote_machine_name, "sudo killall -9 ".$process_name);
				sleep(3);
			} else {
				return 1;
			}	
		}
		log_function::debug_log("Unable to kill $process_name process on: $remote_machine_name");
		return 0;	
	}
}
?>