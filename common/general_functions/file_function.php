<?php
class file_function{

	public function clear_log_files($remote_machine, $file_path_to_be_cleared){
		if(is_array($file_path_to_be_cleared)){
			$temp_path = "";
			foreach($file_path_to_be_cleared as $file_name){
				$temp_path = $file_name." ".$temp_path;
			}
			$file_path_to_be_cleared = 	$temp_path;
		}
		remote_function::remote_execution($remote_machine, "cat /dev/null | sudo tee ".$file_path_to_be_cleared);
		service_function::control_service($remote_machine, SYSLOG_NG_SERVICE, "restart");
	}
	
	public function query_log_files($file_to_query, $query_name, $remote_machine_name = NULL){
		$search_log_files = general_function::execute_command("sudo cat $file_to_query | grep -i $query_name", $remote_machine_name);	
		if($search_log_files == "" or $search_log_files  == NULL)
			return 0;
		else
			return $search_log_files ;

	}

	public function modify_value_ini_file($ini_file_path, $search_key, $replace_value, $remote_machine_name = NULL){
		$command_to_be_executed = "";
		if(is_array($search_key)){
			foreach(array_combine($search_key, $replace_value) as $search_key_value => $replace_key_value){
				$command_to_be_executed = "sudo sed -i 's/^$search_key_value =.*$/$search_key_value = $replace_key_value/g' $ini_file_path";
				general_function::execute_command($command_to_be_executed, $remote_machine_name);
			}
		} else {
			$command_to_be_executed = "sudo sed -i 's/^$search_key = .*$/$search_key = $replace_value/g' $ini_file_path";
			general_function::execute_command($command_to_be_executed, $remote_machine_name);
		}
	}	
	
	public function write_to_file($file_name, $message_to_log, $write_mode){
			// skip logging if the directory is not created
		if(file_exists(dirname($file_name))){
			$filePointer = fopen($file_name, $write_mode);
			fputs($filePointer,$message_to_log."\r\n");
			fclose($filePointer);	
		}	
	}
	
}	
?>