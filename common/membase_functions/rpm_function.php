<?php

class rpm_function{

	public function create_rpm_combination_list($aBuildInstall){
		global $temp_array, $pos, $combination_list_array;
		$temp_array = $combination_list_array = array();
		$pos = 0;	
		general_function::generateCombination($aBuildInstall);
		return $combination_list_array;
	}
	
	public function clean_install_rpm($remote_machine_name, $rpm_path, $uninstall_packagename){
		general_rpm_function::uninstall_rpm($remote_machine_name, $uninstall_packagename);
		
		// For membase package, extra remove to ensure remove the /opt/membase folder before installing
		if(stristr($uninstall_packagename, "membase") && !stristr($uninstall_packagename, "backup")){
			general_rpm_function::uninstall_rpm($remote_machine_name, "membase-server");
		} 
			
		$output = general_rpm_function::install_rpm($remote_machine_name, $rpm_path);
		if (stristr($output, "error") or stristr($output, "fail")){
			log_function::debug_log($output);
			log_function::exit_log_message("Installation failed for ".basename($rpm_path)." on ".$remote_machine_name);
		} else {
			log_function::debug_log($output);
			return True;
		}	
	}

	public function install_backup_tools_rpm($remote_machine_name, $rpm_path, $uninstall_packagename){
		self::clean_install_rpm($remote_machine_name, $rpm_path, $uninstall_packagename);
			// Modify default.ini file on successful installation of 
		file_function::modify_value_ini_file(DEFAULT_INI_FILE, array("game_id", "cloud", "interval"), array(23, 23, 30, $remote_machine_name));
		// CHECK need to check if the above function works and where the file has to be dumped 
		return True;
	}
	
	public function get_installed_component_version($packagename, $remote_machine_name = NULL){
		switch (true) {
		case strstr($packagename, "php-pecl"):
			return self::get_installed_pecl_version($remote_machine_name);
		case strstr($packagename, "membase"):
			return self::get_installed_membase_version($remote_machine_name);
		case strstr($packagename, "mcmux"):
			return self::get_installed_mcmux_version($remote_machine_name);
		case strstr($packagename, "moxi"):
			return self::get_installed_moxi_version($remote_machine_name);
		case strstr($packagename, "backup_tools"):
			return self::get_installed_backup_tools_version($remote_machine_name);
		default:
			log_function::exit_log_message("$packagename not defined.");	// Should not come to this step
		}
	}

	public function get_installed_pecl_version($remote_machine_name = NULL){
		$check_rpm_output = general_rpm_function::get_rpm_version($remote_machine_name, PHP_PECL_PACKAGE_NAME);
		$check_rpm_output = str_replace(PHP_PECL_PACKAGE_NAME."-", "", $check_rpm_output);
		$check_rpm_output = str_replace("-5.2.10", "", $check_rpm_output);
		return $check_rpm_output;
	}

	public function get_installed_membase_version($remote_machine_name){
		$check_rpm_output = general_rpm_function::get_rpm_version($remote_machine_name, MEMBASE_PACKAGE_NAME);
		$check_rpm_output = str_replace(MEMBASE_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}

	public function get_installed_mcmux_version($remote_machine_name = NULL){
		$check_rpm_output = general_rpm_function::get_rpm_version($remote_machine_name, MCMUX_PACKAGE_NAME);
		$check_rpm_output = str_replace(MCMUX_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
	public function get_installed_moxi_version($remote_machine_name = NULL){
		$check_rpm_output = general_rpm_function::get_rpm_version($remote_machine_name, MOXI_PACKAGE_NAME);
		$check_rpm_output = str_replace(MCMUX_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
	public function get_installed_backup_tools_version($remote_machine_name){
		$check_rpm_output = general_rpm_function::get_rpm_version($remote_machine_name, BACKUP_TOOLS_PACKAGE_NAME);
		$check_rpm_output = str_replace(BACKUP_TOOLS_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
	
	public function get_installed_disk_mapper_version($remote_machine_name){
		$check_rpm_output = general_rpm_function::get_rpm_version($remote_machine_name, DISK_MAPPER_PACKAGE_NAME);
		$check_rpm_output = str_replace(DISK_MAPPER_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
	
	public function install_jemalloc_rpm($remote_machine_name){
			// check if jemalloc is installed
		if(stristr(general_rpm_function::get_rpm_version($remote_machine_name, JEMALLOC), "not installed")){
			if(stristr(general_function::execute_command("cat /etc/redhat-release", $remote_machine_name), "5.")){
				self::install_rpm_from_S3("JEMALLOC5", $remote_machine_name);
			} else{
				self::install_rpm_from_S3("JEMALLOC6", $remote_machine_name);	// Support CentOS 6.x
			}	
		}	
	}
	
	public function install_httpd_rpm($remote_machine_name){
		if(stristr(general_rpm_function::get_rpm_version($remote_machine_name, "httpd"), "not installed")){
			general_function::execute_command("sudo yum install php", $remote_machine_name);
		}
	}
	
	public function install_rpm_from_S3($package_name, $remote_machine_name){
		log_function::debug_log($package_name." not installed on ".$remote_machine_name.". Pulling the latest version from S3");
		$rpm_path_to_download = self::get_rpm_path_from_S3($package_name);
		if($rpm_path_to_download == ""){
			log_function::exit_log_message("Error downloading file. Path not found for ".$package_name);
		}
		$rpm_name = explode("/", $rpm_path_to_download);
		$rpm_name = end($rpm_name);
		$check_if_rpm_is_downloaded = general_function::execute_command("ls ".BUILD_FOLDER_PATH.$rpm_name, $remote_machine_name);
		if(stristr($check_if_rpm_is_downloaded, "No such file")){
			$download_output = general_function::execute_command("wget --directory-prefix=".BUILD_FOLDER_PATH." ".$rpm_path_to_download." 2>&1", $remote_machine_name);
			if(stristr($download_output, "Forbidden") or stristr($download_output, "Name or service not known")){
				log_function::exit_log_message("Error downloading file ".$rpm_path_to_download);
			}
		} else {
			log_function::debug_log("$rpm_name present in ".BUILD_FOLDER_PATH.". Skipping downloading");
		}
		return general_rpm_function::install_rpm($remote_machine_name, BUILD_FOLDER_PATH.$rpm_name);
	}
	
	public function get_rpm_path_from_S3($package_name){
		$rpm_path_name = general_function::execute_command("grep ".$package_name."= ".LATEST_RELEASED_RPM_LIST_LOCAL_PATH);
		return trim(str_replace($package_name."=", "", $rpm_path_name));
	}
}
?>