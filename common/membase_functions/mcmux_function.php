<?php

class mcmux_function{

	public function start_mcmux_service($remote_machine_name = NULL) {
		return service_function::control_service($remote_machine_name, MCMUX_SERVICE, "restart");
	}

	public function kill_mcmux_process($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MCMUX_PROCESS);
	}	
}
?>