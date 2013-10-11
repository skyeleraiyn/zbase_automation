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
class vba_setup {


	public function vba_cluster_start_stop($command = "start", $spare  = False)	{
		global $test_machine_list;
                global $spare_machine_list;
		if($spare) {
			foreach($spare_machine_list as $test_machine) {
				self::vba_start_stop($test_machine, $command);
			}
		}

		foreach ($test_machine_list as $test_machine) {
			$pid = pcntl_fork();
			if($pid==0) {
				self::vba_start_stop($test_machine, $command);
				exit();
			}
			else {
				$pid_arr[] = $pid;
			}
		}
		foreach ($pid_arr as $pid) {
			pcntl_waitpid($pid, $status);
		}		
	}

        public function vba_start_stop($remote_machine_name, $command = "start")      {
		return service_function::control_service($remote_machine_name, VBA_SERVICE, $command);
        }

}
?>
