<?php

class storage_server{

	public function install_backup_tools_rpm($remote_machine_name){
		$nstalled_backup_tools_version = rpm_function::get_installed_backup_tools_version($remote_machine_name);	
		if(stristr($nstalled_backup_tools_version, "not installed")){
			rpm_function::install_jemalloc_rpm($remote_machine_name);
			rpm_function::install_rpm_from_S3(BACKUP_TOOLS_PACKAGE_NAME, $remote_machine_name);
			self::configure_incremental_backup_feature($remote_machine_name);
		}				
	}
	
	public function configure_incremental_backup_feature($remote_machine_name){
		file_function::modify_value_ini_file(DEFAULT_INI_FILE, array("game_id", "cloud", "interval"), array(GAME_ID, MEMBASE_CLOUD, 30), $remote_machine_name);
		$installed_membase_version = rpm_function::get_installed_membase_version($remote_machine_name);
			// create tmpfs only in the box where membase is installed
		if(!stristr(rpm_function::get_installed_membase_version($remote_machine_name), "not installed")){
			self::create_tmpfs($remote_machine_name);
		}	
	}

	public function create_tmpfs($remote_machine_name){
		directory_function::create_directory("/db_backup", $remote_machine_name);
		if(!stristr(general_function::execute_command("mount", $remote_machine_name), "db_backup")){
			general_function::execute_command("sudo mount -t tmpfs -o size=3584M none /db_backup", $remote_machine_name);
		}
		if(!stristr(general_function::execute_command("cat /etc/fstab", $remote_machine_name), "db_backup")){
			general_function::execute_command("sudo sh -c 'echo \"none /db_backup tmpfs size=3584M 0 0\" >> /etc/fstab'", $remote_machine_name);
		}
	}
	
	public function configure_storage_server($membase_slave_hostname, $membase_storage_server_hostname)	{
	
		self::install_zstore();
		self::modify_Master_Merge();
		self::modify_Daily_Merge();					
		self::copy_test_split_files($membase_slave_hostname);
		self::export_file_split($membase_slave_hostname);
	
			// Update Storage Server info in the /etc/hosts file of slave machine
		$hostfile_contents = general_function::execute_command("cat /etc/hosts", $membase_slave_hostname);	
				// Get IPaddress of STORAGE_SERVER
		if(filter_var(STORAGE_SERVER, FILTER_VALIDATE_IP)){
			$ip_address = STORAGE_SERVER;
		} else {
			$ip_address = explode(" ", general_function::execute_command("host ".STORAGE_SERVER));
			$ip_address = end($ip_address);
		}
				// Add if the host file doesn't have an entry
		if(!stristr($hostfile_contents, $ip_address." ".GAME_ID.".".STORAGE_CLOUD.".zynga.com")){
			$string = "\n$ip_address ".GAME_ID.".".STORAGE_CLOUD.".zynga.com ".GAME_ID.".int.zynga.com \n$ip_address ".GAME_ID."-0.".STORAGE_CLOUD.".zynga.com ".GAME_ID."-0.int.zynga.com\n";
			general_function::execute_command("sudo sh -c 'echo \"$string\" >> /etc/hosts'", $membase_slave_hostname);
		}
		
			//Changing cloud info on zstore_cmd command on the slave box
		$command_to_be_executed = "sudo sed -i 's/mbbackup.zynga.com/'".STORAGE_CLOUD."'.zynga.com/g' ".ZSTORE_CMD_FILE_PATH;
        general_function::execute_command($command_to_be_executed, $membase_slave_hostname);
			
			//Changing cloud info on handler.php on the storage server
		$command_to_be_executed = "sudo sed -i 's/mbbackup.zynga.com/'".STORAGE_CLOUD."'.zynga.com/g' ".HANDLER_PHP_FILE_PATH;
        general_function::execute_command($command_to_be_executed, STORAGE_SERVER);
						
			//Change no of partitions to be scanned from 7 to 1 in sample_config.php
		general_function::execute_command("sudo cp ".SAMPLE_CONFIG_PATH." ".GAMEID_CONFIG_PATH, STORAGE_SERVER);
		$command_to_be_executed = "sudo sed -i 's/partition_count\ =\ 7/partition_count\ =\ 1/g' ".GAMEID_CONFIG_PATH;
        general_function::execute_command($command_to_be_executed, STORAGE_SERVER);
	
	}

	public function copy_test_split_files($remote_machine_name) {
		remote_function::remote_file_copy($remote_machine_name, BASE_FILES_PATH."/test_splitlib.py", TEST_SPLITLIB_FILE_PATH, False, True, True);
	}
	
	public function export_file_split($remote_machine_name) {
		if(!stristr(remote_function::remote_execution($remote_machine_name, "cat ~/.bashrc"), "export LD_LIBRARY_PATH=/opt/sqlite3/lib/")){
			$string = "export LD_LIBRARY_PATH=/opt/sqlite3/lib/:\\\$LD_LIBRARY_PATH";
			remote_function::remote_execution($remote_machine_name, "echo ".$string." >> ~/.bashrc");
		}
	}
			// Install zstore rpm on STORAGE_SERVER
	public function install_zstore(){
		$check_rpm_output = general_rpm_function::get_rpm_version(STORAGE_SERVER, "zstore");
		if(stristr($check_rpm_output, "not installed")){
			rpm_function::install_httpd_rpm(STORAGE_SERVER);
			rpm_function::install_rpm_from_S3("zstore", STORAGE_SERVER);
			$check_rpm_output = general_rpm_function::get_rpm_version(STORAGE_SERVER, "zstore");
			if(stristr($check_rpm_output, "not installed")){
				log_function::exit_log_message("Installation of zstore failed on ".STORAGE_SERVER);
			}
		}	
	}
	
	public function modify_Daily_Merge(){
		$command_to_be_executed = "sudo sed -i 's/for i in range(7):/for i in range(1):/g' ".DAILY_MERGE_FILE_PATH;
        general_function::execute_command($command_to_be_executed, STORAGE_SERVER);
		$command_to_be_executed = "sudo sed -i 's/pathname = \"\/data_%d\" %(i+1)/pathname = \"\/data_1\"/g' ".DAILY_MERGE_FILE_PATH;
        general_function::execute_command($command_to_be_executed, STORAGE_SERVER);

	}

	public function modify_Master_Merge() {
	//	$command_to_be_executed = "sudo sed -i 's/for i in range(7):/for i in range(1):/g' ".MASTER_MERGE_FILE_PATH;
    //    general_function::execute_command($command_to_be_executed, STORAGE_SERVER);
		$command_to_be_executed = "sudo sed -i 's/pathname = \"\/data_%s\" %part_no/pathname = \"\/data_1\"/g' ".MASTER_MERGE_FILE_PATH;
        general_function::execute_command($command_to_be_executed, STORAGE_SERVER);
	}

	
}
?>