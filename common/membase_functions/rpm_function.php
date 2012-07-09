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
		remote_function::remote_file_copy($remote_machine_name, $rpm_path, "/tmp");
		general_rpm_function::uninstall_rpm($remote_machine_name, $uninstall_packagename);
		
			// For membase package, remove the /opt/membase folder before installing
		if(stristr($uninstall_packagename, "membase"))
			general_function::execute_command("sudo rm -rf /opt/membase", $remote_machine_name);
			
		$output = general_rpm_function::install_rpm($remote_machine_name, "/tmp/".basename($rpm_path));
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
}
?>