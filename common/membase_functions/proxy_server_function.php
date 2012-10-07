<?php

class proxy_server_function{

	public function mcmux_service($command, $remote_machine_name = NULL) {
		return service_function::control_service($remote_machine_name, MCMUX_SERVICE, $command);
	}

	public function kill_mcmux_process($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MCMUX_PROCESS);
	}

	public function moxi_service($command, $remote_machine_name = NULL) {
		return service_function::control_service($remote_machine_name, MOXI_SERVICE, $command);
	}

	public function kill_moxi_process($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MOXI_PROCESS);
	}

	public function kill_proxyserver_process($remote_machine_name){
		self::mcmux_service("stop", $remote_machine_name);
		self::moxi_service("stop", $remote_machine_name);
		self::kill_mcmux_process($remote_machine_name);
		self::kill_moxi_process($remote_machine_name);
	}
	
	public function start_proxyserver($remote_machine_name, $proxyserver_installed){
	
		if($proxyserver_installed == "mcmux"){
			self::mcmux_service("restart", $remote_machine_name);
		} else {
			self::moxi_service("restart", $remote_machine_name);
		}
	}
}
?>