<?php
class membase_setup{

	public function clear_membase_log_file($remote_machine){
		file_function::clear_log_files($remote_machine, MEMBASE_LOG_FILE);
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
				if(CENTOS_VERSION == 5){
					remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_multikvstore_config_0", MEMCACHED_MULTIKV_CONFIG, False, True, True);
				} else {
					remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."memcached_multikvstore_config_".MULTI_KV_STORE, MEMCACHED_MULTIKV_CONFIG, False, True, True);
				}
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

	public function memcached_service($remote_machine_name, $command) {
		return service_function::control_service($remote_machine_name, MEMCACHED_SERVICE, $command);
	}	

	public function kill_membase_server($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, MEMCACHED_PROCESS);
	}

	public function clear_cluster_membase_database($reset_spare = False){
		global $test_machine_list;
		global $spare_machine_list;
		if($reset_spare) {
			foreach($spare_machine_list as $spare) {
				self::clear_membase_database($spare);
			}
		}
		$pid_arr = array();
		foreach ($test_machine_list as $test_machine) {
			$pid = pcntl_fork();
			if($pid == 0) {
				self::clear_membase_database($test_machine);
				exit();
			}
			else {
				$pid_arr[]=$pid;
			}
		}
		foreach ($pid_arr as $pid) {
                                pcntl_waitpid($pid, $status);
		}
	}
	
	public function clear_membase_database($remote_machine_name) {
		// To ensure root folder doesn't get deleted if the constant is not defined
		if(!(defined('MEMBASE_DATABASE_PATH')) or MEMBASE_DATABASE_PATH == ""){
			log_function::result_log("Constant MEMBASE_DATABASE_PATH is not defined"); 
			exit;
		}
		remote_function::remote_execution($remote_machine_name, "sudo mount -a ");
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
		membase_backup_setup::stop_backup_daemon($slave_server);
		backup_tools_functions::clear_backup_data($slave_server);
		if(IBR_STYLE == 2.0 && defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> ""){
			diskmapper_setup::reset_diskmapper_storage_servers();
		} else {
			storage_server_setup::clear_storage_server();
		}
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
				if(!(self::memcached_service($remote_machine, "start"))){
					log_function::result_log("Failed to start membase on $remote_machine");
					exit(4);
				}
				sleep(1);
				for($iTime = 0 ; $iTime < 60 ; $iTime++){
					$output = stats_functions::get_stats_netcat($remote_machine, "ep_warmup_time");
					if (stristr($output, "ep_warmup_time")){
						sleep(1);
						if(defined('MULTI_KV_STORE') && MULTI_KV_STORE <> 0){
							if(defined('EVICTION_HEADROOM')){
								flushctl_commands::set_flushctl_parameters($remote_machine, "eviction_headroom", EVICTION_HEADROOM);
							}
							if(defined('EVICTION_POLICY')){
								flushctl_commands::set_flushctl_parameters($remote_machine, "eviction_policy", EVICTION_POLICY);
							}							
						}	
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
	public function restart_membase_cluster($restart_spare = False){
		global $test_machine_list;
		global $spare_machine_list;
		if($restart_spare) {
			foreach($spare_machine_list as $spare) {
				self::restart_membase_servers($spare);
			}
		}

		$pid_arr= array();
		foreach ($test_machine_list as $test_machine) {
			$pid = pcntl_fork();
			if($pid == 0) {
	                        self::restart_membase_servers($test_machine);
				exit();
			}
			else {
				$pid_arr[] = $pid;
			}
		}
		foreach ($pid_arr as $pid) {
			pcntl_waitpid($pid, $status);
                }
		
	}	
	public function restart_membase_servers($remote_machine_name){
		self::memcached_service($remote_machine_name, "restart");
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
		
	public function edit_multikv_config_file($remote_server , $parameter , $value , $operation){
		file_function::add_modified_file_to_list($remote_server, MEMCACHED_MULTIKV_CONFIG);
		file_function::edit_config_file($remote_server , MEMCACHED_MULTIKV_CONFIG , $parameter , $value , $operation);
	}
	
	public function edit_sysconfig_file($remote_server , $parameter , $value , $operation = "replace"){
		file_function::add_modified_file_to_list($remote_server, MEMCACHED_SYSCONFIG);
				
		switch($operation){
			case "delete":
				$command_to_be_executed = "sudo sed -i 's/$parameter=[A-Za-z0-9]*;//g' ".MEMCACHED_SYSCONFIG;
				break;
			case "modify":
				$command_to_be_executed = "sudo sed -i 's/$parameter=[A-Za-z0-9]*;/$parameter=$value;/g' ".MEMCACHED_SYSCONFIG;
				break;
			default:	
				$command_to_be_executed = "sudo sed -i 's/$parameter/$value/g' ".MEMCACHED_SYSCONFIG;
				break;
		}
		return remote_function::remote_execution($remote_server, $command_to_be_executed);		
		
	}
		
	public function add_log_filter_rsyslog($remote_machine_name){
		 
		$rsysconfig_file = "/etc/rsyslog.conf";
		general_function::execute_command("sudo chattr -i ".$rsysconfig_file, $remote_machine_name);
		$membase_log = file_function::query_log_files($rsysconfig_file, "membase", $remote_machine_name);
		if(!stristr($membase_log, "membase")){
			general_function::execute_command("sudo sh -c 'echo :msg, contains, \"membase\" >> '".$rsysconfig_file, $remote_machine_name);
			general_function::execute_command("sudo sh -c 'echo \*\.\* /var/log/membase.log >> '".$rsysconfig_file."' ; echo \"\" >> '".$rsysconfig_file, $remote_machine_name);
		}	
	
		$membasebackup_log = file_function::query_log_files($rsysconfig_file, "MembaseBackup", $remote_machine_name);
		if(!stristr($membasebackup_log, "MembaseBackup")){
			general_function::execute_command("sudo sh -c 'echo :msg, contains, \"MembaseBackup\" >> '".$rsysconfig_file, $remote_machine_name);
			general_function::execute_command("sudo sh -c 'echo \*\.\* /var/log/membasebackup.log >> '".$rsysconfig_file."' ; echo \"\" >> '".$rsysconfig_file, $remote_machine_name);
		}	
		
		$vbucketmigrator_log = file_function::query_log_files($rsysconfig_file, "vbucketmigrator", $remote_machine_name);
		if(!stristr($vbucketmigrator_log, "vbucketmigrator")){
			general_function::execute_command("sudo sh -c 'echo :msg, contains, \"vbucketmigrator\" >> '".$rsysconfig_file, $remote_machine_name);
			general_function::execute_command("sudo sh -c 'echo \*\.\* /var/log/vbucketmigrator.log >> '".$rsysconfig_file."' ; echo \"\" >> '".$rsysconfig_file, $remote_machine_name);
		}	
		general_function::execute_command("sudo chattr +i ".$rsysconfig_file, $remote_machine_name);	
	}
	
}
?>
