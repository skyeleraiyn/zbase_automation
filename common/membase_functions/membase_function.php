<?php

class membase_function{

	public function copy_memcached_files(array $remote_server_array){
		foreach($remote_server_array as $remote_server){
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_init.d", MEMCACHED_INIT, False, True, True);
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."membase-init.sql", MEMBASE_INIT_SQL, False, True, True);	
		}		
	}

	public function copy_slave_memcached_files(array $remote_server_array){
		foreach($remote_server_array as $remote_server){
			if(MEMBASE_VERSION == 1.6){
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
			} else {
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_slave_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
			}
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_init.d", MEMCACHED_INIT, False, True, True);	
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."membase-init.sql", MEMBASE_INIT_SQL, False, True, True);	
		}	
	}
	
	public function stop_membase_server_service($remote_machine_name) {
		service_function::control_service($remote_machine_name, MEMBASE_SERVER_SERVICE, "stop");
	}

	public function start_memcached_service($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMCACHED_SERVICE, "start");
	}

	public function restart_memcached_service($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMCACHED_SERVICE, "restart");
	}	
	public function restart_syslog_ng_service($remote_machine_name) {
		remote_function::remote_execution($remote_machine_name, "sudo rm -rf ".MEMBASE_LOG_FILE." ".VBUCKETMIGRATOR_LOG_FILE, False);
		return service_function::control_service($remote_machine_name, SYSLOG_NG_SERVICE, "restart");		
	}
	
	public function copy_membase_log_file($remote_machine_name, $destination_path){
		remote_function::remote_file_copy($remote_machine_name, MEMBASE_LOG_FILE, $destination_path."_membase.log", True);
	}

	public function kill_membase_server($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MEMCACHED_PROCESS);
	}
	
	public function get_membase_memory($remote_machine_name){
	 $command_output = trim(remote_function::remote_execution($remote_machine_name, "ps elf -U nobody | grep memcached | grep -v grep | awk '{print $8}'"));
	 
		if(isset($command_output) && $command_output <> "")
			return round(($command_output / 1048576), 2);
		else
			return False;
	}

	public function get_membase_db_size($remote_machine_name){
		$command_output = trim(remote_function::remote_execution($remote_machine_name, "du -sh ".MEMBASE_DATABASE_PATH." | awk '{print $1}'"));
		return $command_output;
	}	


	public function clear_membase_database($remote_machine_name) {
		// To ensure root folder doesn't get deleted if the constant is not defined
		if(!(defined('MEMBASE_DATABASE_PATH')) or MEMBASE_DATABASE_PATH == ""){
			log_function::result_log("Constant MEMBASE_DATABASE_PATH is not defined"); 
			exit;
		}			
		for($iattempt = 0 ; $iattempt < 60 ; $iattempt++) {
			if (stristr(remote_function::remote_execution($remote_machine_name, "ls ".MEMBASE_DATABASE_PATH), "No such file or directory")) {
				remote_function::remote_execution($remote_machine_name, "sudo mkdir ".MEMBASE_DATABASE_PATH);
				remote_function::remote_execution($remote_machine_name, "sudo chown -R nobody ".MEMBASE_DATABASE_PATH);
				return 1;
			} else {
				remote_function::remote_execution($remote_machine_name, "sudo rm -rf ".MEMBASE_DATABASE_PATH);
				sleep(20);
			}	
		}
		log_function::debug_log("Unable to clear database files on: $remote_machine_name");	
		return 0;	
	}

	public function reset_membase_servers($remote_machine_array_list, $clear_db = True){
		$pid_count = 0;
		foreach ($remote_machine_array_list as $remote_machine){
			$pid = pcntl_fork();
			if ($pid == 0){	
				if(!(self::kill_membase_server($remote_machine))){
					log_function::result_log("Failed to terminate membase on $remote_machine");
					exit(4);
				}
				if($clear_db){
					if(!(self::clear_membase_database($remote_machine))){
						log_function::result_log("Failed to clear DB files on $remote_machine");
						exit(4);
					}
				}
				if(!(self::start_memcached_service($remote_machine))){
					log_function::result_log("Failed to start membase on $remote_machine");
					exit(4);
				}
				for($iTime = 0 ; $iTime < 60 ; $iTime++){
					$output = stats_functions::get_warmup_time_ascii($remote_machine);
					if (stristr($output, "ep_warmup_time")){
						exit(0);
					}else  {
						sleep(1);
					}	
				}				
				exit(4);
			} else {
				$pid_arr[$pid_count] = $pid;
				$pid_count++;
			}	
		}

		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
			if(pcntl_wexitstatus($status) == 4) exit;
		}
		return True;
	}	
	
	public function restart_membase_servers($remote_machine_name){
		self::restart_memcached_service($remote_machine_name);
		for($iTime = 0 ; $iTime < 3 ; $iTime++){
			$output = stats_functions::get_warmup_time_ascii($remote_machine_name);
			if (stristr($output, "ep_warmup_time")){
				return True;
			}else  {
				sleep(1);
			}
		}
		log_function::debug_log("Membase failed to restart $remote_machine_name");		
		return False;
	}	
	
}	
?>