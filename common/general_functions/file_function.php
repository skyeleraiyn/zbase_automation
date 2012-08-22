<?php
class file_function{

	public function clear_log_files($remote_machine_array_list){
		$pid_count = 0;
		foreach ($remote_machine_array_list as $remote_machine){
			$pid = pcntl_fork();
			if ($pid == 0){	
				remote_function::remote_execution($remote_machine, "cat /dev/null | sudo tee ".MEMBASE_LOG_FILE." ".VBUCKETMIGRATOR_LOG_FILE);
				service_function::control_service($remote_machine, SYSLOG_NG_SERVICE, "restart");
				exit;
			} else {
				$pid_arr[$pid_count] = $pid;
				$pid_count++;
			}	

			foreach($pid_arr as $pid){	
				pcntl_waitpid($pid, $status);			
				if(pcntl_wexitstatus($status) == 4) exit;
			}	
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
				$command_to_be_executed = $command_to_be_executed." \; sudo sed -i 's/$search_key=.*$/$search_key=$replace_value/g' $ini_file_path";
			}
		} else {
			$command_to_be_executed = "sudo sed -i 's/$search_key=.*$/$search_key=$replace_value/g' $ini_file_path";
		}
		return general_function::execute_command($command_to_be_executed, $remote_machine_name);
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