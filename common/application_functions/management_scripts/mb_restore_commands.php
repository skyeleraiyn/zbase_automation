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
class mb_restore_commands {

	public function restore_server($hostname) {
                if(IBR_STYLE == 2.0 && defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> "") {
					$host = general_function::get_hostname($hostname);
				}
				else {
		 			$host = $hostname;	
				}
				$command_to_be_executed = "sudo python26 ".ZBASE_RESTORE_SCRIPT." -h $host";
		        return remote_function::remote_execution_popen($hostname, $command_to_be_executed);
	}
	public function restore_to_cluster($hostname, $vb_id,  $disk_mapper = DISK_MAPPER_SERVER_ACTIVE) {
		$command_to_be_executed = "sudo python26 ".ZBASE_RESTORE_SCRIPT." -v ".$vb_id." -d ".$disk_mapper;
		return remote_function::remote_execution_popen($hostname, $command_to_be_executed);
	}
}
?>
