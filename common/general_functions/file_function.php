<?php
class file_function{

	public function check_file_exists($remote_machine, $file_path){
		$output = remote_function::remote_execution($remote_machine, "ls ".$file_path);
		if(stristr($output, "cannot access") || stristr($output, "No such file")){
			return False;
		} else {
			return True;
		}
	}

	public function clear_log_files($remote_machine, $file_path_to_be_cleared){
		if(is_array($file_path_to_be_cleared)){
			$temp_path = "";
			foreach($file_path_to_be_cleared as $file_name){
				$temp_path = $file_name." ".$temp_path;
			}
			$file_path_to_be_cleared = 	$temp_path;
		}
		remote_function::remote_execution($remote_machine, "cat /dev/null | sudo tee ".$file_path_to_be_cleared);
		if(general_function::get_CentOS_version($remote_machine) == 5){
			service_function::control_service($remote_machine, SYSLOG_NG_SERVICE, "restart");
		} else {
			service_function::control_service($remote_machine, RSYSLOG, "restart");
		}
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
		// Should remove modify_value_ini_file and use a common function
	public function edit_config_file($remote_server , $file , $parameter , $value , $operation = 'replace'){
		//specify $parameter and $value as complete string
		//$operation can be delete , append, replace

		if(strstr($operation , 'delete')){
			$command_to_be_executed = "sudo sed -i '/$parameter/d' $file";
		}else if(strstr($operation , 'append')){
			$command_to_be_executed = "sudo sed -i '/$parameter/a$value' $file";
		} else {
			$command_to_be_executed = "sudo sed -i 's@$parameter@$value@' $file";
		}
		return remote_function::remote_execution($remote_server, $command_to_be_executed);
	}
	
	public function write_to_file($file_name, $message_to_log, $write_mode){
			// skip logging if the directory is not created
		if(file_exists(dirname($file_name))){
			$filePointer = fopen($file_name, $write_mode);
			fputs($filePointer,$message_to_log."\r\n");
			fclose($filePointer);	
		}	
	}
	
	public function read_from_file($file_name){
			// skip logging if the directory is not created		
		if(file_exists(dirname($file_name))){
			$filePointer = fopen($file_name, "r");
			$file_contents = fread($filePointer, filesize($file_name));
			fclose($filePointer);	
			return $file_contents;
		}  else {
			return False;
		}
	}
	
	public function add_modified_file_to_list($remote_server, $file_name){
		global $modified_file_list;
		
		$file_name = $remote_server."::".$file_name;
		if(!in_array($file_name, $modified_file_list)) $modified_file_list[] = $file_name;
	}
	
}	
?>