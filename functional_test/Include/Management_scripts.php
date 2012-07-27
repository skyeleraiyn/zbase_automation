<?php


class Management_scripts {

	public function execute_management_script($remote_machine_name, $script_file_to_be_executed, $command_parameters){
		$output = array();	
		if($script_file_to_be_executed == BIN_TAP_REGISTRATION_SCRIPT){
	                $output['stdout'] = remote_function::remote_execution($remote_machine_name, $script_file_to_be_executed." -h localhost:".MEMBASE_PORT_NO." ".$command_parameters);
        	        $output['stderr'] = remote_function::remote_execution($remote_machine_name, $script_file_to_be_executed." -h localhost:".MEMBASE_PORT_NO." ".$command_parameters." 2>&1 >/dev/null");
		} else {
			
			$output['stdout'] = remote_function::remote_execution($remote_machine_name, $script_file_to_be_executed." localhost:".MEMBASE_PORT_NO." ".$command_parameters);
			$output['stderr'] = remote_function::remote_execution($remote_machine_name, $script_file_to_be_executed." localhost:".MEMBASE_PORT_NO." ".$command_parameters." 2>&1 >/dev/null");
		}
			return $output;
		
	}

	public function verify_execute_management_script($remote_machine_name, $script_file_to_be_executed, $command_parameters){	
		$output = self::execute_management_script($remote_machine_name, $script_file_to_be_executed, $command_parameters);
		if(stristr($output['stderr'],"error") or stristr($output['stderr'], "No such file or directory") or stristr($output['stdout'], "usage:")){
			log_function::debug_log("$script_file_to_be_executed with $command_parameters failed ".$output);
			return False;
		} else {
			return True;
		}	
	}
	
	// funciton to verify mbstats
	public function verify_stat($stats_command){
		return self::verify_execute_management_script(TEST_HOST_1, BIN_STATS_SCRIPT, $stats_command);
	}
	
	// function to verify mbflushctl 
	public function verify_mbflusctl($flushctl_command){
		return self::verify_execute_management_script(TEST_HOST_1, BIN_FLUSHCTL_SCRIPT, $flushctl_command);
	}
	
	// function to verify mbadm-tap-registration 
	public function verify_tap_admin($tap_command) {
		return self::verify_execute_management_script(TEST_HOST_1, BIN_TAP_REGISTRATION_SCRIPT, $tap_command);
	}

}

