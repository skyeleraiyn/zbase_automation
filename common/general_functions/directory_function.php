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
	
	public function get_directory_contents($directory_path, $remote_machine_name = NULL){
		log_function::debug_log(general_function::get_caller_function());
		$commad_to_execute = "ls ".$directory_path;
		return trim(general_function::execute_command($commad_to_execute, $remote_machine_name));
	}
}	
?>