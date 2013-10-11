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

class enhanced_backup_functions {

        public function clear_local_backups($remote_machine_name = TEST_HOST_2, $file ="all") {
                if($file == "all")
                        $command_to_be_executed = "sudo rm -rf ".ZBASE_DB_LOCAL_BACKUP_FOLDER."*";
                else
                        $command_to_be_executed = "sudo rm -rf ".ZBASE_DB_LOCAL_BACKUP_FOLDER."/".$file;

                return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
        }


        public function get_recent_local_backup_name($backup_number = -1) {
                $command_to_be_executed = "ls -lt ".ZBASE_DB_LOCAL_BACKUP_FOLDER." | grep -v total | cut -d ' ' -f9-10 | cut -d ' ' -f2";
                $list = remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed);
                $output_list = preg_split("/[\s,]+/", $list);
                $output = array_filter(array_map('trim', $output_list));
                $output = array_unique($output);
                if($backup_number == -1)
                        return $output;
                else {
                        if(count($output)<$backup_number) {
                                log_function::debug_log("requested recent backup $backup_number , only ".count($output)." available. \n ".print_r($output, True));
                                return False;
                        }
                        else
                                return $output[$backup_number - 1];

                }
        }

	public function clear_local_host_config_file($remote_machine_name = TEST_HOST_2) {
		$command_to_be_executed = "sudo rm -rf ".LOCAL_DISK_MAPPER_HOST_CONFIG_FILE;
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function get_host_mapping_local($remote_machine_name = TEST_HOST_2) {
		if(general_function::get_CentOS_version($remote_machine_name) == 6) {
			$command_to_be_executed = "python /tmp/string_json.py ".LOCAL_DISK_MAPPER_HOST_CONFIG_FILE;
		}
                $command_to_be_executed = "python26 /tmp/string_json.py ".LOCAL_DISK_MAPPER_HOST_CONFIG_FILE;
		$output = remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
		$output = json_decode($output, True);
		log_function::debug_log($output);
		return $output;
	}

	public function trim_slave_host_name($remote_machine_name = TEST_HOST_2) {
		return general_function::get_hostname($remote_machine_name);
	}

}
