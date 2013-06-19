<?php
class diskmapper_setup{

	public function install_disk_mapper_rpm($remote_machine){
		global $disk_mapper_build;

		// verify $remote_machine is having CentOS 6
		if(general_function::get_CentOS_version($remote_machine) == 5){
			log_function::exit_log_message("$remote_machine has CentOS 5.4. Disk mapper needs CentOS 6");
		}

		if($disk_mapper_build <> ""){
			rpm_function::uninstall_rpm($remote_machine, DISK_MAPPER_PACKAGE_NAME);
			rpm_function::yum_install(BUILD_FOLDER_PATH.$disk_mapper_build, $remote_machine);
			self::setup_diskmapper_config_file($remote_machine);
			self::disk_mapper_service($remote_machine, "restart");			
		} else {
			// verify disk mapper is installed
			if(installation::get_installed_disk_mapper_version($remote_machine) == "not installed"){
				log_function::exit_log_message("Disk mapper is not installed on $remote_machine");
			}
		}
	}

	public function disk_mapper_service($remote_machine, $command){
		return service_function::control_service($remote_machine, DISK_MAPPER_SERVICE, $command);
	}

	public function reset_diskmapper_storage_servers($SS_pool = NULL){
		self::kill_zstore_cmd();
		if($SS_pool != NULL) {
              		storage_server_setup::reset_dm_storage_servers($SS_pool);
		}
		else {
			storage_server_setup::reset_dm_storage_servers();
		}
		self::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		self::clear_diskmapper_log_files();
		self::clear_host_mapping_file();
		self::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
	}
		
	public function kill_zstore_cmd(){
		return process_functions::kill_process(TEST_HOST_2, "zstore_cmd");
	}

	public function clear_diskmapper_log_files($hostname = DISK_MAPPER_SERVER_ACTIVE) {
		file_function::clear_log_files($hostname, DISK_MAPPER_LOG_FILE);
	}
	
	public function clear_host_mapping_file($machine= DISK_MAPPER_SERVER_ACTIVE) {
		$command_to_be_executed = "sudo rm ".DISK_MAPPER_HOST_MAPPING;
		return remote_function::remote_execution($machine, $command_to_be_executed);
	}

	public function setup_diskmapper_config_file($remote_machine){
		global $storage_server_pool;
		
		//update zruntime page with active disk mapper ip
		
		if(!filter_var($remote_machine, FILTER_VALIDATE_IP)) {
			$ip_address = general_function::get_ip_address($remote_machine);
			$ip_address = $ip_address[0];
		} else {
			$ip_address = $remote_machine;
		}
		zruntime_functions::add_key_if_update_fails(array(ACTIVE_DISKMAPPER_KEY => $ip_address));
		
		$zruntime_settings = array(
			'username' => ZRUNTIME_USERNAME,
			'password' => ZRUNTIME_PASSWORD,
			'gameid' => GAME_ID,
			'env' => EVN,
			'mcs_key_name' => ACTIVE_DISKMAPPER_KEY 
			);
		
		$string = diskmapper_functions::create_config_file($zruntime_settings, $storage_server_pool);
		diskmapper_functions::update_diskmapper_config_file($remote_machine, $string);	
	}
	

}
?>
