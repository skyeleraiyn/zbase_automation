<?php
class disk_mapper{

	public function install_disk_mapper_rpm($remote_machine){
		global $disk_mapper_build;
		
		// verify $remote_machine is having CentOS 6
		if(general_function::get_CentOS_version($remote_machine) == 5){
			log_function::exit_log_message("$remote_machine has CentOS 5.4. Disk mapper needs CentOS 6");
		}
		
		if($disk_mapper_build <> ""){
			// install httpd, mod_wsgi
			general_function::execute_command("sudo yum install -y -q httpd httpd php php-cli php-common php-devel php-pdo php-pear mod_wsgi 2>&1", $remote_machine);
			rpm_function::clean_install_rpm($remote_machine, BUILD_FOLDER_PATH.$disk_mapper_build, DISK_MAPPER_PACKAGE_NAME);
			
			// configure diskmapper
			// /opt/disk_mapper/config.py 
			file_function::edit_config_file($remote_server , $file , $parameter , $value , $operation = 'replace');
				$storage_server_pool;
				
				$command_to_be_executed = "sudo sed -i 's/server_1_ip//g' $ini_file_path";
			general_function::execute_command($command_to_be_executed, $remote_machine_name);
			
			self::disk_mapper_service($remote_machine, "restart");			
		} else {
			// verify disk mapper is installed
			if(rpm_function::get_installed_disk_mapper_version($remote_machine) == "not installed"){
				log_function::exit_log_message("Disk mapper is not installed on $remote_machine");
			}
		}
	}
	
	public function disk_mapper_service($remote_machine_name, $command){
		return service_function::control_service($remote_machine_name, DISK_MAPPER_SERVICE, $command);
	}

}
?>