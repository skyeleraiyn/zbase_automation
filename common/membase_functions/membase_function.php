<?php

class membase_function{
 
	public function define_membase_db_path(){
	
		// define db path name based on Cent OS version and MULTI_KV_STORE 

		if(defined('MULTI_KV_STORE') && MULTI_KV_STORE <> 0){
			$drive_array = array();
			for($idrive=1; $idrive<MULTI_KV_STORE + 1; $idrive++){
				if(CENTOS_VERSION == 5){
					$drive_array[] = "/db/membase/";
					break;
				} else {
					$drive_array[] = "/data_".$idrive."/membase/";
				}	
			}
			define('MEMBASE_DATABASE_PATH', serialize($drive_array));
		} else { 
			if(CENTOS_VERSION == 5){
				define('MEMBASE_DATABASE_PATH', serialize(array("/db/membase/")));
			} else {
				define('MEMBASE_DATABASE_PATH', serialize(array("/data_1/membase/")));
			}
		}
		log_function::write_to_temp_config("MEMBASE_DATABASE_PATH=".MEMBASE_DATABASE_PATH, "a");
	}
 
	public function clear_membase_log_file($remote_machine){
		file_function::clear_log_files($remote_machine, MEMBASE_LOG_FILE);
	}

	public function clear_membase_backup_log_file($remote_machine){
		file_function::clear_log_files($remote_machine, MEMBASE_BACKUP_LOG_FILE);
	}
	
	public function copy_memcached_files(array $remote_server_array){
		if(defined('SKIP_BASEFILES_SETUP') && SKIP_BASEFILES_SETUP){
			log_function::debug_log("SKIP_BASEFILES_SETUP is set to True. Skipping copying of base files");
		} else {
			foreach($remote_server_array as $remote_server){
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_init.d", MEMCACHED_INIT, False, True, True);
				if(defined('MULTI_KV_STORE') && MULTI_KV_STORE){
					remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_sysconfig_multikv_store", MEMCACHED_SYSCONFIG, False, True, True);
					if(CENTOS_VERSION == 5){
						remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_multikvstore_config_0", MEMCACHED_MULTIKV_CONFIG, False, True, True);
					} else {
						remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_multikvstore_config_".MULTI_KV_STORE, MEMCACHED_MULTIKV_CONFIG, False, True, True);
					}	
				} else {
					remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
				}
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."membase-init.sql", MEMBASE_INIT_SQL, False, True, True);	
			}
		}		
	}

	public function copy_slave_memcached_files(array $remote_server_array){
		foreach($remote_server_array as $remote_server){
			if(defined('MULTI_KV_STORE') && MULTI_KV_STORE){
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_sysconfig_multikv_store", MEMCACHED_SYSCONFIG, False, True, True);
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_multikvstore_config_".MULTI_KV_STORE, MEMCACHED_MULTIKV_CONFIG, False, True, True);
			} else {
				remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
			}	
			if(MEMBASE_VERSION <> 1.6){
				remote_function::remote_execution($remote_server, "sudo sed -i 's/inconsistent_slave_chk=false/inconsistent_slave_chk=true/g' ".MEMCACHED_SYSCONFIG);
			}
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_init.d", MEMCACHED_INIT, False, True, True);	
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."membase-init.sql", MEMBASE_INIT_SQL, False, True, True);	
		}	
	}
	
	public function stop_membase_server_service($remote_machine_name){
		service_function::control_service($remote_machine_name, MEMBASE_SERVER_SERVICE, "stop");
	}

	public function start_memcached_service($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMCACHED_SERVICE, "start");
	}

	public function read_membase_config($remote_machine_name) {
 	//Read the memcached parameters into associative array

      		// Copy file from remote machine to tmp folder and open it to read the data
		remote_function::remote_file_copy($remote_machine_name, "/etc/sysconfig/memcached", "/tmp/memcached", True);
	        $my_file = "/tmp/memcached";
        	$handle = fopen($my_file, 'r');
	        $old_data = fread($handle,filesize($my_file));
		// Select the strip that has memcached parameters declared
	        $strpos = strpos($old_data, "'");
		$substr = substr($old_data, $strpos+1, strlen($old_data)-$strpos-4);
		// Make the parameters into an associative arraycd h
	        $array = array();
        	foreach(explode(";",$substr) as $element) {
			$parts = explode("=", $element);
		        $array[$parts[0]] = $parts[1];
        	}
        	fclose($handle);
	        return $array;
    	}	

	public function write_membase_config($remote_machine_name, $config_array) {
	//Write the edited associative array memcahed parameters into the file. The existing file is overwritten.

		$my_file = "/tmp/memcached";
        	$handle = fopen($my_file, 'r');
	        $old_data = fread($handle,filesize($my_file));
        	// Select the strip that has memcached parameters declared
	        $strpos = strpos($old_data, "'");
        	$substr = substr($old_data, $strpos+1, strlen($old_data)-$strpos-4);
		fclose($handle);
		$string = "";
		$terms = count($config_array);
		foreach($config_array as $field => $value) {
			$string = $string.$field."=".$value;
			$terms--;
			if($terms) {
				$string = $string.";";
			}
		}
		$new_data = str_replace($substr, $string, $old_data);
		$handle = fopen($my_file, 'w');
		fwrite($handle, $new_data);
		fclose($handle);
		remote_function::remote_file_copy($remote_machine_name, "/tmp/memcached", "/etc/sysconfig/memcached", False, True, True);
	}

	public function stop_memcached_service($remote_machine_name) {
		service_function::control_service($remote_machine_name, MEMCACHED_SERVICE, "stop");
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
		$command_output = 0;
		foreach(unserialize(MEMBASE_DATABASE_PATH) as $membase_dbpath){
			$command_output += trim(remote_function::remote_execution($remote_machine_name, "du -sh ".$membase_dbpath." | awk '{print $1}'"));
		}
		return $command_output;
	}	

	public function clear_membase_database($remote_machine_name) {
	
		// To ensure root folder doesn't get deleted if the constant is not defined
		if(!(defined('MEMBASE_DATABASE_PATH')) or MEMBASE_DATABASE_PATH == ""){
			log_function::result_log("Constant MEMBASE_DATABASE_PATH is not defined"); 
			exit;
		}
		foreach(unserialize(MEMBASE_DATABASE_PATH) as $membase_dbpath){
			if($membase_dbpath == ""){
				log_function::result_log("membase_dbpath is not defined"); 
				exit;
			}
		
			for($iattempt = 0 ; $iattempt < 60 ; $iattempt++) {
				if (stristr(remote_function::remote_execution($remote_machine_name, "ls ".$membase_dbpath), "No such file or directory")) {
					remote_function::remote_execution($remote_machine_name, "sudo mkdir ".$membase_dbpath);
					remote_function::remote_execution($remote_machine_name, "sudo chown -R nobody ".$membase_dbpath);
					break;
				} else {
					if(stristr(remote_function::remote_execution($remote_machine_name, "ls ".$membase_dbpath), "sqlite")) {
						remote_function::remote_execution($remote_machine_name, "sudo rm -rf ".$membase_dbpath."/*");
						sleep(5);
					} else {
						// skip the loop checking the db path
						break;
					}
				}	
			}
			if($iattempt == 60){
				log_function::debug_log("Unable to clear database files on: $remote_machine_name");	
				return False;	
			}
		}
		return True;
	}

	public function reset_servers_and_backupfiles($master_server, $slave_server){
		self::reset_membase_vbucketmigrator($master_server, $slave_server);
		mb_backup_commands::stop_backup_daemon($slave_server);
		mb_backup_commands::clear_backup_data($slave_server);
		mb_backup_commands::clear_storage_server();
	}
	
	public function reset_membase_vbucketmigrator($master_server, $slave_server) {
		self::reset_membase_servers(array($master_server, $slave_server));
		remote_function::remote_execution($slave_server, " sudo killall -9 python26");
		vbucketmigrator_function::attach_vbucketmigrator($master_server, $slave_server);
		tap_commands::register_backup_tap_name($slave_server);
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
				sleep(2);
				if($clear_db){
					self::clear_membase_log_file($remote_machine);
					if(!(self::clear_membase_database($remote_machine))){
						log_function::result_log("Failed to clear DB files on $remote_machine");
						exit(4);
					}
				}
				sleep(4);
				if(!(self::start_memcached_service($remote_machine))){
					log_function::result_log("Failed to start membase on $remote_machine");
					exit(4);
				}
				sleep(1);
				for($iTime = 0 ; $iTime < 60 ; $iTime++){
					$output = stats_functions::get_stats_netcat($remote_machine, "ep_warmup_time");
					if (stristr($output, "ep_warmup_time")){
						sleep(1);
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
			$output = stats_functions::get_stats_netcat($remote_machine_name, "ep_warmup_time");
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