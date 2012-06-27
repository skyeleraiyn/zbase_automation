<?php
class directory_function{

	public function create_folder($path_to_be_created) {
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($path_to_be_created);
		
		if(!file_exists($path_to_be_created)){
			$output = mkdir($path_to_be_created, 0777, True); // Creates folder recursively
			if ($output) {
				log_function::debug_log("created folder $path_to_be_created");
				return 1;
			} else {
				log_function::debug_log("Failed to created $path_to_be_created : $output");	
				return $output;
			}	
		}else {
			log_function::debug_log("Folder $path_to_be_created already exists");
			return 1;
		}	
	}

	public function delete_directory($directory_path, $remote_machine_name = NULL){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name."".$directory_path);
		
		return general_function::execute_command("sudo rm -rf ".$directory_path, $remote_machine_name);		
	}

	public function get_directory_contents($directory_path, $remote_machine_name = NULL){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name."".$directory_path);
		
		$commad_to_execute = "ls ".$directory_path;
		return trim(general_function::execute_command($commad_to_execute, $remote_machine_name));
	}
}	
?>