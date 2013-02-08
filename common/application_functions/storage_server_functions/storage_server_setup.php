<?php
class storage_server_setup{

	public function install_storage_server($storage_server){
		global $storage_server_build;
		
		// verify $storage_server is having CentOS 6
		if(general_function::get_CentOS_version($storage_server) == 5){
			log_function::exit_log_message("$storage_server has CentOS 5.4. Storage server needs CentOS 6");
		}
		
		if($storage_server_build <> "" || !SKIP_BUILD_INSTALLATION){
			rpm_function::uninstall_rpm($storage_server, STORAGE_SERVER_PACKAGE_NAME_2);
			self::clear_storage_server($storage_server, True);
			rpm_function::yum_install(BUILD_FOLDER_PATH.$storage_server_build, $storage_server, "zynga");
			self::modify_Master_Merge($storage_server);
			self::modify_Daily_Merge($storage_server);				
		} 
		// verify disk mapper is installed
		if(stristr(installation::get_installed_storage_server_version($storage_server), "not installed")){
			log_function::exit_log_message("Storage server is not installed on $storage_server");
		}		
	}

	public function install_zstore_and_configure_storage_server($membase_slave_hostname, $storage_server)	{
	
		self::install_zstore($storage_server);
		self::modify_Master_Merge($storage_server);
		self::modify_Daily_Merge($storage_server);					
		self::copy_test_split_files($membase_slave_hostname);
		self::export_file_split($membase_slave_hostname);
	
			// Update Storage Server info in the /etc/hosts file of slave machine
		$hostfile_contents = general_function::execute_command("cat /etc/hosts", $membase_slave_hostname);	
				// Get IPaddress of $storage_server
		if(filter_var($storage_server, FILTER_VALIDATE_IP)){
			$ip_address = $storage_server;
		} else {
			$ip_address = explode(" ", general_function::execute_command("host ".$storage_server));
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
        general_function::execute_command($command_to_be_executed, $storage_server);
						
			//Change no of partitions to be scanned from 7 to 1 in sample_config.php
		general_function::execute_command("sudo cp ".SAMPLE_CONFIG_PATH." ".GAMEID_CONFIG_PATH, $storage_server);
		$command_to_be_executed = "sudo sed -i 's/partition_count\ =\ 7/partition_count\ =\ 1/g' ".GAMEID_CONFIG_PATH;
        general_function::execute_command($command_to_be_executed, $storage_server);
	
	}

	public function clear_storage_server($storage_server = STORAGE_SERVER_1, $clear_membase_backup = False) {
		membase_backup_setup::clear_membase_backup_log_file($storage_server);
		if($clear_membase_backup){
			$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/*";
		} else {	
			$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/";
		}
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}

	private function copy_test_split_files($remote_machine_name) {
		remote_function::remote_file_copy($remote_machine_name, BASE_FILES_PATH."/test_splitlib.py", TEST_SPLITLIB_FILE_PATH, False, True, True);
	}
	
	private function export_file_split($remote_machine_name) {
		if(!stristr(remote_function::remote_execution($remote_machine_name, "cat ~/.bashrc"), "export LD_LIBRARY_PATH=/opt/sqlite3/lib/")){
			$string = "export LD_LIBRARY_PATH=/opt/sqlite3/lib/:\\\$LD_LIBRARY_PATH";
			remote_function::remote_execution($remote_machine_name, "echo ".$string." >> ~/.bashrc");
		}
	}
			
	private function install_zstore($storage_server){
		if(SKIP_BUILD_INSTALLATION){
			rpm_function::uninstall_rpm($storage_server, "zstore");
			installation::install_rpm_from_S3("zstore", $storage_server);
			$check_rpm_output = rpm_function::get_rpm_version($storage_server, "zstore");
			if(stristr($check_rpm_output, "not installed")){
				log_function::exit_log_message("Installation of zstore failed on ".$storage_server);
			}	
		}
	}

	private function modify_Daily_Merge($storage_server){
		$command_to_be_executed = "sudo sed -i 's/for i in range(7):/for i in range(1):/g' ".DAILY_MERGE_FILE_PATH;
        general_function::execute_command($command_to_be_executed, $storage_server);
		$command_to_be_executed = "sudo sed -i 's/pathname = \"\/data_%d\" %(i+1)/pathname = \"\/data_1\"/g' ".DAILY_MERGE_FILE_PATH;
        general_function::execute_command($command_to_be_executed, $storage_server);

	}

	private function modify_Master_Merge($storage_server) {
		$command_to_be_executed = "sudo sed -i 's/pathname = \"\/data_%s\" %part_no/pathname = \"\/data_1\"/g' ".MASTER_MERGE_FILE_PATH;
        general_function::execute_command($command_to_be_executed, $storage_server);
	}

	public function reset_dm_storage_servers($storage_server_list = array(STORAGE_SERVER_1,STORAGE_SERVER_2,STORAGE_SERVER_3)){
	
		// Clear torrent files, meta files, dirty entires and kill torrent process
		$pid_arr = array();
		foreach ($storage_server_list as $storage_server){
			$pid = pcntl_fork();
			if($pid == 0){	
				self::clear_dirty_entry($storage_server);
				self::clear_bad_disk_entry($storage_server);
				torrent_functions::clear_torrent_files($storage_server);
				self::clear_storage_server_meta_files($storage_server);
				self::clear_to_be_deleted_entry($storage_server);
				torrent_functions::kill_all_torrents($storage_server);		
				exit();
			} else {
				$pid_arr[] = $pid;
			}
		}
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
		}
		// Clear data files and server log file
		$pid_arr = array();
		foreach($storage_server_list as $storage_server){
			$pid = pcntl_fork();
			if($pid == 0){	
				self::clear_storage_server_data_folders($storage_server);
				self::clear_storage_server_log_file($storage_server);		
				exit();
			} else {
				$pid_arr[] = $pid;
			}
		}
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
		}		
		return True;
	}

	public function clear_to_be_deleted_entry($storage_server)	{
		$command_to_be_executed = "cat /dev/null | sudo tee /data_*/to_be_deleted; sudo chown storageserver /data_*/to_be_deleted";
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}

	public function clear_storage_server_data_folders($storage_server){
		$command_to_be_executed = "sudo rm -rf /data_*/*/*; sudo rm -rf /var/www/html/".GAME_ID;
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}

	public function clear_storage_server_meta_files($storage_server){
		$command_to_be_executed = "sudo rm -rf /data_*/.diffdata; sudo rm -rf /data_*/*.lock; sudo rm -rf /data_*/to_be_deleted; sudo rm -rf /var/tmp/disk_mapper/* ";
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}

	public function clear_dirty_entry($storage_server){
		$command_to_be_executed = "cat /dev/null | sudo tee /data_*/dirty ; sudo chown storageserver /data_*/dirty";
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}

	public function clear_bad_disk_entry($storage_server){
		$command_to_be_executed = "cat /dev/null | sudo tee /var/tmp/disk_mapper/bad_disk ; sudo chown storageserver /var/tmp/disk_mapper/bad_disk";
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}	

	public function delete_master_backups() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master";
		return remote_function::remote_execution(STORAGE_SERVER_1,$command_to_be_executed);
	}		

	public function delete_daily_backups() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/";
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}		
	
	public function delete_incremental_backups($filetype="") {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/$filetype";
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}
	
	public function clear_storage_server_log_file($remote_machine){
		file_function::clear_log_files($remote_machine, array(STORAGE_SERVER_LOG_FILE, MEMBASE_BACKUP_LOG_FILE));
	}	
	
}
?>