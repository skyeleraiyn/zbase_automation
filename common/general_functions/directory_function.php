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
class directory_function{

	public function delete_directory($directory_path, $remote_machine_name = NULL){
		log_function::debug_log(general_function::get_caller_function());		
		return general_function::execute_command("sudo rm -rf ".$directory_path, $remote_machine_name);		
	}

	public function create_directory($directory_path, $remote_machine_name = NULL, $sudo = False){
		if($sudo){
			return general_function::execute_command("sudo mkdir -p ".$directory_path, $remote_machine_name);
		} else {
			return general_function::execute_command("mkdir -p ".$directory_path, $remote_machine_name);
		}
        
    }

	public function rename_directory($old_dir_name, $new_dir_name, $remote_machine_name)	{
		return general_function::execute_command("sudo mv $old_dir_name $new_dir_name", $remote_machine_name);
	}
	
	public function list_files_recursive($directory_path, $remote_machine_name = NULL){
		$command_to_be_executed = "sudo find ".$directory_path." -not -type d";
		$list =trim(general_function::execute_command($command_to_be_executed, $remote_machine_name));
		$output_list = preg_split("/[\s,]+/", $list);
		$output = array_filter(array_map('trim', $output_list));
		$output = array_unique($output);
		sort($output);
		return $output;
	}
}	
?>
