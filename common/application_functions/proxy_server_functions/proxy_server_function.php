/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
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
