<?php

class torrent_functions{

	public function verify_torrent_file_creation($storage_server, $timout = 20){
		
		$command_to_be_executed = "ls /var/www/html/torrent/";
		for($i=0 ; $i<$timout; $i++){
			$status = trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
			if(strlen($status) > 0){
				return True;
			} else {	
				sleep(1);
			}
		}
		return False;
	}
	
	public function check_torrent_process_exists($storage_server = False)       {
                if($storage_server){
			$command_to_be_executed = "ps -elf | grep aria2c | grep -v grep";
			$status=trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
			if($status <> "")
                        	return True;
			else
				return False;
                }
                $storage_server_list = array(STORAGE_SERVER_1,STORAGE_SERVER_2,STORAGE_SERVER_3);
                foreach ($storage_server_list as $storage_server){
                       if(self::check_torrent_process_exists($storage_server)) {
				log_function::debug_log("torrent exists on".$storage_server);
				return True;
			}
                }
		return False;

        }

	
	public function get_torrent_filename($storage_server){
		$command_to_be_executed = "ls /var/www/html/torrent/";
		$output = trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
		return explode("\n", $output);
	}

	public function kill_all_torrents($storage_server = False)	{
		$command_to_be_executed = "sudo killall -9 aria2c";
		if($storage_server)	{
			remote_function::remote_execution($storage_server, $command_to_be_executed);
		} else {
			$storage_server_list = array(STORAGE_SERVER_1,STORAGE_SERVER_2,STORAGE_SERVER_3);
			foreach ($storage_server_list as $storage_server)	{
				remote_function::remote_execution($storage_server, $command_to_be_executed);
			}
		}
	}

	public function clear_torrent_files($storage_server){
		$command_to_be_executed = "sudo rm -rf /var/www/html/torrent/*";
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}
	
	/* $slave_host_name can take array of IP => Disk and not array of hostnames
		if IP => Disk array is sent then verify_torrent_sync_across_servers is called which doesn't use diskmapper API's
		sending just a hostname will call compare_primary_secondary which uses diskmapper API
	*/
	public function wait_for_torrent_copy($slave_host_name, $time_to_wait){
		$iterations = $time_to_wait/2;
		for($i=0;$i<$iterations;$i++){
			if(is_array($slave_host_name)){
				if(self::verify_torrent_sync_across_servers($slave_host_name)){
					sleep(2);
					return True;
				} else {
					sleep(2);
				}
			} else {		
				if(diskmapper_functions::compare_primary_secondary($slave_host_name)){
					sleep(5);
					return True;
				} else {
					sleep(2);
				}				
			}			
		}
		return False;
	}

	public function verify_torrent_sync_across_servers($server_pool){
		foreach($server_pool as $SS => $SDisk){
			$storage_server_array[] = $SS;
			$disk_array[] = $SDisk;
		}
		
		$file_list_master = directory_function::list_files_recursive($disk_array[0],$storage_server_array[0]);
		$file_list_slave = directory_function::list_files_recursive($disk_array[1],$storage_server_array[1]);
		if(count($file_list_master) == count($file_list_slave)){
			$md5_master = file_function::get_md5sum($storage_server_array[0],$file_list_master);
			$md5_slave = file_function::get_md5sum($storage_server_array[1],$file_list_slave);
			$diff_md5 = array_diff($md5_master, $md5_slave);
			if(empty($diff_md5)){
			return True;	
			}
			else {
				return False;
			}	
		} else { 
			log_function::debug_log("File list difference \n master ".print_r($file_list_master, True)."\n slave ".print_r($file_list_slave, True));
			return False; 
		}
		
		$rsync_param = " --dry-run --checksum --recursive --verbose --quiet";
		$output  = remote_function::remote_execution($storage_server_array[0], "rsync ".$disk_array[0]." ".$storage_server_array[1].":".$disk_array[1].$rsync_param);
		if($output <> ""){
			return False;
		} else {
			return True;
		}
	}

	public function wait_till_torrent_file_exists($storage_server, $torrent_file){
	
		$command_to_be_executed = "ls /var/www/html/torrent/$torrent_file";
		while(1){
			$status = trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
			if(stristr($status, "No such file")){
				return True;
			} else {	
				sleep(1);
			}
		}	
	}

	public function torrent_service($storage_server, $command){
		return service_function::control_service($storage_server, TORRENT_SERVICE, $command);
	}	
	
	public function create_storage_directories(array $storage_server, $disk, $hostname){
		$file_path_in_primary = "/$disk/primary/$hostname/".MEMBASE_CLOUD."/test/";
		$file_path_in_secondary = "/$disk/secondary/$hostname/".MEMBASE_CLOUD."/test/";
		directory_function::create_directory($file_path_in_primary, $storage_server[0]);
		directory_function::create_directory($file_path_in_secondary, $storage_server[1]);
		return array($file_path_in_primary, $file_path_in_secondary) ;
	}
	
	public function chown_storageserver(array $storage_server){
		foreach($storage_server as $server){
			remote_function::remote_execution($server, "sudo chown -R storageserver /data_*");
		}
	}
	
	public function create_test_file($storage_server, $file_path, $file_size = 1024, $no_of_files = 1, $file_contents = "urandom"){
		$filename = basename($file_path);
		$file_path = dirname($file_path);
		$ifile_count_start = str_replace("test_file_", "",  $filename);
		$file_list = array();
		for($ifile=$ifile_count_start ; $ifile<$no_of_files + 1 ; $ifile++){
			$file_list[] = $file_path."/test_file_$ifile";
			file_function::create_dummy_file($storage_server, $file_path."/test_file_$ifile", $file_size, True, $file_contents);
		}
		return $file_list;
	}
	
	public function create_dirty_file($storage_server_disk, $file_list){
		$temp_file = "/tmp/dirty";
		if (file_exists($temp_file)) 
			general_function::execute_command("sudo rm -rf $temp_file");
		foreach($file_list as $file_path){
			file_function::write_to_file($temp_file, $file_path, "a");
		}
		foreach($storage_server_disk as $storage_server => $storage_disk){
			log_function::debug_log(general_function::execute_command("sudo dos2unix $temp_file 2>&1; sudo chmod 777 $temp_file"));
			remote_function::remote_file_copy($storage_server, $temp_file, "/".$storage_disk."/dirty", False, True, True, False);
			remote_function::remote_execution($storage_server, "sudo chmod 777 /".$storage_disk."/dirty");
		}
		if (file_exists($temp_file)) 
			general_function::execute_command("sudo rm -rf $temp_file");
	}

	public function update_dirty_file($storage_server, $disk, $dirty_file_entry){
		$dirty_file_path = "/$disk/dirty";
		$command = "sudo sh -c 'echo \"$dirty_file_entry\" >> $dirty_file_path '";
		return remote_function::remote_execution($storage_server, $command);
	}

	public function query_dirty_file($storage_server, $disk){
		$dirty_file_path = "/$disk/dirty";
		$command = "cat $dirty_file_path";
		$file_path = explode("\n", remote_function::remote_execution($storage_server, $command));
		return $file_path;
	}
	
}
?>