<?php
class service_function{

	public function control_service($remote_machine_name, $service_name, $command) {
		for($iattempt = 0 ; $iattempt < 5 ; $iattempt++) {
			if(remote_function::remote_service_control($remote_machine_name, $service_name, $command)) 
				return 1;
			else
				sleep(1);
		}	
		log_function::debug_log("Unable to $command $service_name service");
		return 0;
	}
	
}
?>