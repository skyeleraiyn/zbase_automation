<?php

class installation{

	public function create_rpm_combination_list($aBuildInstall){
		global $temp_array, $pos, $combination_list_array;
		$temp_array = $combination_list_array = array();
		$pos = 0;	
		general_function::generateCombination($aBuildInstall);
		return $combination_list_array;
	}
	
	public function clean_install_rpm($remote_machine_name, $rpm_path, $uninstall_packagename){
		rpm_function::uninstall_rpm($remote_machine_name, $uninstall_packagename);
		
		// For zbase package, extra remove to ensure remove the /opt/zbase folder before installing
		if(stristr($uninstall_packagename, "zbase") && !stristr($uninstall_packagename, "backup")){
			rpm_function::uninstall_rpm($remote_machine_name, "zbase-server");
		} 
			
		$output = rpm_function::install_rpm($remote_machine_name, $rpm_path);
		if (stristr($output, "error") or stristr($output, "fail")){
			log_function::debug_log($output);
			log_function::exit_log_message("Installation failed for ".basename($rpm_path)." on ".$remote_machine_name);
		} else {
			log_function::debug_log($output);
			return True;
		}	
	}

	public function verify_and_install_rpm($remote_machine_name, $rpm_name, $packagename){
		global $list_of_installed_rpms;
		
		$output = self::get_installed_component_version($rpm_name, $remote_machine_name);
		if(!(strstr($rpm_name, $output)) or !(in_array($remote_machine_name.$output, $list_of_installed_rpms))){
			self::clean_install_rpm($remote_machine_name, BUILD_FOLDER_PATH.$rpm_name, $packagename);
			$list_of_installed_rpms[] = self::get_installed_component_version($rpm_name, $remote_machine_name);
		} else {
			log_function::debug_log("Build $output is already installed, skipping installation.");
		}	
	}	
	
	public function get_installed_component_version($packagename, $remote_machine_name = NULL){
		switch (true) {
		case strstr($packagename, "php-pecl"):
			return self::get_installed_pecl_version($remote_machine_name);
		case strstr($packagename, "backup_tools"):
			return self::get_installed_backup_tools_version($remote_machine_name);			
		case strstr($packagename, "zbase"):
			return self::get_installed_zbase_version($remote_machine_name);
		case strstr($packagename, "mcmux"):
			return self::get_installed_mcmux_version($remote_machine_name);
		case strstr($packagename, "moxi"):
			return self::get_installed_moxi_version($remote_machine_name);
		default:
			log_function::exit_log_message("$packagename not defined.");	// Should not come to this step
		}
	}

	public function get_installed_pecl_version($remote_machine_name = NULL){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, PHP_PECL_PACKAGE_NAME);
		$check_rpm_output = str_replace(PHP_PECL_PACKAGE_NAME."-", "", $check_rpm_output);
		$check_rpm_output = str_replace("-5.2.10", "", $check_rpm_output);
		$check_rpm_output = str_replace("-5.3.3", "", $check_rpm_output);
		return $check_rpm_output;
	}

	public function get_installed_zbase_version($remote_machine_name){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, ZBASE_PACKAGE_NAME);
		$check_rpm_output = str_replace(ZBASE_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}

	public function get_installed_mcmux_version($remote_machine_name = NULL){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, MCMUX_PACKAGE_NAME);
		$check_rpm_output = str_replace(MCMUX_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
	
	public function get_installed_moxi_version($remote_machine_name = NULL){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, MOXI_PACKAGE_NAME);
		$check_rpm_output = str_replace(MCMUX_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
	
	public function get_installed_backup_tools_version($remote_machine_name){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, BACKUP_TOOLS_PACKAGE_NAME);
		$check_rpm_output = str_replace(BACKUP_TOOLS_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
	}

	public function get_installed_storage_server_version($remote_machine_name){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, STORAGE_SERVER_PACKAGE_NAME_2);
		$check_rpm_output = str_replace(STORAGE_SERVER_PACKAGE_NAME_2."-", "", $check_rpm_output);
		return $check_rpm_output;
	}
		
	public function get_installed_disk_mapper_version($remote_machine_name){
		$check_rpm_output = rpm_function::get_rpm_version($remote_machine_name, DISK_MAPPER_PACKAGE_NAME);
		$check_rpm_output = str_replace(DISK_MAPPER_PACKAGE_NAME."-", "", $check_rpm_output);
		return $check_rpm_output;
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
		return rpm_function::yum_install(BUILD_FOLDER_PATH.$rpm_name, $remote_machine_name);
	}
	
	public function get_rpm_path_from_S3($package_name){
		$rpm_path_name = general_function::execute_command("grep ".$package_name."= ".LATEST_RELEASED_RPM_LIST_LOCAL_PATH);
		return trim(str_replace($package_name."=", "", $rpm_path_name));
	}
	
	public function verify_php_pecl_DI_capable(){
		if(general_function::execute_command("cat /etc/php.d/memcache.ini | grep data_integrity_enabled") <> "")
			return True;
		else 
			return False;
	}
	
	public function verify_mcmux_DI_capable(){
		if(PROXY_RUNNING == False){
			log_function::debug_log("verify_mcmux_DI_capable: mcmux is not running");
			return True;
		} else {
			if(stristr(PROXY_RUNNING, "mcmux")){
				$proxy_output = trim(general_function::execute_command("sudo /etc/init.d/mcmux stats | grep chksum"));
			} else {
				$proxy_output = trim(general_function::execute_command("sudo /etc/init.d/moxi stats | grep chksum"));
			} 
			if(stristr($proxy_output,"not running")){
				return True;
			} else {
				if(stristr($proxy_output, "chksum")){
					return True;
				} else {
					return False;
				}	
			}		
		}
	}
	
	public function verify_zbase_DI_capable($remote_machine_name){
		// check if DI is implemented in the destination zbase server
		$cksum_output = trim(shell_exec("echo stats | nc $remote_machine_name 11211 | grep cksum"));
		if(stristr($cksum_output, "cksum")){
			log_function::debug_log("verify_zbase_DI_capable: ".$cksum_output);
			return True;
		} else {
			log_function::debug_log("verify_zbase_DI_capable: ".$cksum_output);
			return False;
		}	
	}

}
?>
