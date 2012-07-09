<?php

class proxy_server_function{

	public function start_mcmux_service($remote_machine_name = NULL) {
		return service_function::control_service($remote_machine_name, MCMUX_SERVICE, "restart");
	}

	public function kill_mcmux_process($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MCMUX_PROCESS);
	}

	public function start_moxi_service($remote_machine_name = NULL) {
		return service_function::control_service($remote_machine_name, MOXI_SERVICE, "restart");
	}

	public function kill_moxi_process($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MOXI_PROCESS);
	}

	public function kill_proxyserver_process($remote_machine_name){
		self::kill_mcmux_process($remote_machine_name);
		self::kill_moxi_process($remote_machine_name);
	}
	
	public function start_proxyserver($remote_machine_name, $proxyserver_installed){
	
		if($proxyserver_installed == "mcmux"){
			self::start_mcmux_service($remote_machine_name);
		} else {
			self::start_moxi_service($remote_machine_name);
		}
	}
}
?>