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
		for($iattempt = 0 ; $iattempt < 60 ; $iattempt++) {
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
