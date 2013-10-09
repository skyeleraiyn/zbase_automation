<?php
class service_function{

	public function control_service($remote_machine_name, $service_name, $command) {
		for($iattempt = 0 ; $iattempt < 5 ; $iattempt++) {
			$output = remote_function::remote_service_control($remote_machine_name, $service_name, $command);
			if($command <> "restart" && stristr($output, "failed")) {
				log_function::debug_log($output);
				break;
			} else {
				sleep(2);
				$output = remote_function::remote_execution($remote_machine_name, "sudo /etc/init.d/$service_name status");
				if(stristr($command, "start")){
					if(stristr($output, "is running")){ 
						return True;
					}	
				}	
				if($command == "stop"){
					if(stristr($output, "is stopped") || stristr($output, "is not running") || stristr($output, "command not found")){ 
						return True;
					}
				}	
			}		
			sleep(1);
		}	
		log_function::debug_log("Unable to $command $service_name service");
		return False;
	}
	
}
?>
