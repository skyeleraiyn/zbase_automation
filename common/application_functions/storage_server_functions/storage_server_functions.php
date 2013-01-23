<?php

class storage_server_functions{
	
	public function get_sunday_date($date = NULL){
		if($date){
			return(date("Y-m-d", strtotime($date." last Sunday ")));
		} else	{ 
			//Utilize today's date itself.
			$date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			return(date("Y-m-d", strtotime($date." last Sunday ")));
		}
	}

	public function get_sunday_date_difference($date = NULL){
		$last_sunday_date = self::get_sunday_date($date); 
		if($date){
			return((strtotime($date) - strtotime($last_sunday_date)) / (60 * 60 * 24));
		} else {
			$date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			return((strtotime($date) - strtotime($last_sunday_date)) / (60 * 60 * 24));
		}
	}
	
	public function run_master_merge($storage_server = STORAGE_SERVER_1, $backup_hostpath = NULL, $no_of_days = NULL) {
		$command_to_be_executed = "sudo python26 ".MASTER_MERGE_FILE_PATH;
			// master merge with Enhanced Coalescers
		if($backup_hostpath <> NULL){
			if(stristr($backup_hostpath, "/"))      {
				$date = self::get_date($no_of_days);
				$command_to_be_executed = $command_to_be_executed." -p $backup_hostpath -d $date";
				return remote_function::remote_execution($storage_server, $command_to_be_executed);
            } else {
				$primary_mapping = diskmapper_functions::get_primary_partition_mapping($backup_hostpath);
				$primary_mapping_ss = $primary_mapping['storage_server'];
				$primary_mapping_disk = $primary_mapping['disk'];
				$date = self::get_date($no_of_days);
				$hostname = explode(".", $backup_hostpath);
				$command_to_be_executed = $command_to_be_executed." -p /$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD." -d $date";
				$status = remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed);
				if(stristr($status, "Merge complete")){ 
					return True;
				} else {
					return False;
				}
			}
		}	
			// Old style master merge
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}	
	
	public function run_daily_merge($storage_server = STORAGE_SERVER_1, $backup_hostpath = NULL, $no_of_days = NULL) {
		$command_to_be_executed = "sudo python26 ".DAILY_MERGE_FILE_PATH;
		if($backup_hostpath <> NULL){
			if(stristr($backup_hostpath, "/"))	{		               
				$date = self::get_date($no_of_days);
	            $command_to_be_executed = $command_to_be_executed." -p $backup_hostpath -d $date";
				return remote_function::remote_execution($storage_server, $command_to_be_executed);
			} else	{
				$primary_mapping = diskmapper_functions::get_primary_partition_mapping($backup_hostpath);
				$primary_mapping_ss = $primary_mapping['storage_server'];
				$primary_mapping_disk = $primary_mapping['disk'];
				$date = self::get_date($no_of_days); 
				$hostname = explode(".", $backup_hostpath);
				$command_to_be_executed = $command_to_be_executed." -p /$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD." -d $date";
				$status = remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed);
				if(stristr($status, "Merge complete")){
					return True;
				} else {
					return False;
				}
			}
		}		
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}	

	public function run_core_merge_script($validate=True, $split_size=NULL) {
		$command_to_be_executed = "sudo ".MERGE_INCREMENTAL_FILE_PATH." -i ".MERGE_INCREMENTAL_INPUT_FILE." -o ".TEMP_OUTPUT_FILE_PATTERN;
		if($validate == True){
			$command_to_be_executed = $command_to_be_executed." -v";
		}
		if($split_size){
			$command_to_be_executed = $command_to_be_executed." -s $split_size";
		}	
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}
			
	public function check_file_exists($file_name, $host_name, $type = 'primary', $parameter = "test"){
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];	
		$file_name = basename($file_name);
		$file_path = "/$disk/$type/$host_name/".MEMBASE_CLOUD."/$parameter/$file_name";
		return file_function::check_file_exists($storage_server, $file_path);
	}

	public function clear_host_primary($host_name)	{
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name]['primary']['storage_server'];
		$disk = $parsed_hostmap[$host_name]['primary']['disk'];
		$command_to_be_executed = "sudo rm -rf /$disk/primary/$host_name";
		return(remote_function::remote_execution($storage_server, $command_to_be_executed));
	}

	public function clear_host_secondary($host_name)    {
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name]['secondary']['storage_server'];
		$disk = $parsed_hostmap[$host_name]['secondary']['disk'];
		$command_to_be_executed = "sudo rm -rf /$disk/secondary/$host_name";
		return(remote_function::remote_execution($storage_server, $command_to_be_executed));

	}
	public function get_date($days)	{
		return(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $days, date("Y"))));
	}

	public function delete_daily_merge_directory($hostname, $date)	{
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
                $primary_mapping_ss = $primary_mapping['storage_server'];
                $primary_mapping_disk = $primary_mapping['disk'];
		$command_to_be_executed = "sudo rm -rf /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/daily/$date";
		return(remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed));
	}

	public function edit_date_folder($days=-1, $type="daily", $hostname = NULL) {
		if($hostname <> NULL){
			$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
			$primary_mapping_ss = $primary_mapping['storage_server'];
			$primary_mapping_disk = $primary_mapping['disk'];
			if($type == "daily"){
				$original_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));		
			} else	{
				$original_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			}
			$host = explode(".", $hostname);
			$path = "/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/$type/$original_date";
			$modified_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $days, date("Y")));
			$new_path = "/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/$type/$modified_date";
			$command_to_be_executed = "sudo mv $path $new_path";
			remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed);
			return $new_path;
		} else	{
			$path = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/".date("Y-m-d");
			$mod_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $days, date("Y")));
			$new_path = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/".$mod_date;
			$command_to_be_executed = "sudo mv $path $new_path";
			remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
			sleep(1);
			return $new_path;
		}
	}	
	

	public function create_lock_file() {
		$command_to_be_executed = "sudo touch /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/.lock-".TEST_HOST_2;
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}

	public function delete_lock_file() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/.lock-".TEST_HOST_2;
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}

	public function delete_input_file_entry($line_no){
		$command_to_be_executed = "sudo sed -i ".$line_no."d ".MERGE_INCREMENTAL_INPUT_FILE;
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}

	public function delete_split_file_entry($line_no, $storage_server = STORAGE_SERVER_1) {
		$array = self::list_incremental_backups($storage_server, "*.split");
		$command_to_be_executed = "sudo sed -i '".$line_no."d' $array[0]";
		return remote_function::remote_execution($storage_server, $command_to_be_executed);
	}

	public function delete_done_daily_merge($hostname = NULL) {
		if($hostname){
			$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
			$primary_mapping_ss = $primary_mapping['storage_server'];
			$primary_mapping_disk = $primary_mapping['disk'];
			$host = explode(".", $hostname);
			$command_to_be_executed = "sudo rm -rf /$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/incremental/done*";
			return remote_function::remote_execution($primary_mapping_ss,  $command_to_be_executed);
		} else	{
			$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/done*";
			return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		}
	}	
	
	public function set_input_file_merge($file_list_array, $mode = 'w') {
		if(is_array($file_list_array )){
			foreach($file_list_array as $file_list){
				remote_function::remote_execution(STORAGE_SERVER_1, "echo ".trim($file_list)." >>".MERGE_INCREMENTAL_INPUT_FILE);
			}
		} else {
			remote_function::remote_execution(STORAGE_SERVER_1, "echo ".trim($file_list_array)." >>".MERGE_INCREMENTAL_INPUT_FILE);
		}
		return 1;
	}
	
	public function list_daily_backups($storage_server = STORAGE_SERVER_1, $filetype = ".mbb", $no_of_days = NULL, $hostname = NULL) {
		if($hostname <> NULL){
			if($no_of_days <> NULL){
				$mod_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $no_of_days, date("Y")));
				$command_to_be_executed = "find /var/www/html/".GAME_ID."/".$hostname."/".MEMBASE_CLOUD."/daily/".$mod_date."/ -name \*".$filetype;
			} else {	
				$command_to_be_executed = "find /var/www/html/".GAME_ID."/".$hostname."/".MEMBASE_CLOUD."/daily/ -name \*".$filetype;
			}		
		} else {
			if($no_of_days <> NULL){
				$mod_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $no_of_days, date("Y")));
				$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/".$mod_date."/ -name \*".$filetype;
			} else {	
				$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/ -name \*".$filetype;
			}	
		}
		$string = trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
		$temp_array = explode("\n", $string);
		$array = array_map('trim', $temp_array);// For trimming newlines
		sort($array);
		return $array;		
	}
	
	public function list_master_backups($storage_server = STORAGE_SERVER_1, $filetype = ".mbb", $no_of_days = NULL, $hostname = NULL) {
		if($hostname <> NULL){
			if($no_of_days <> NULL){
				$mod_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $no_of_days, date("Y")));
				$command_to_be_executed = "find /var/www/html/".GAME_ID."/".$hostname."/".MEMBASE_CLOUD."/master/".$mod_date."/ -name \*".$filetype;
			} else {	
				$command_to_be_executed = "find /var/www/html/".GAME_ID."/".$hostname."/".MEMBASE_CLOUD."/master/ -name \*".$filetype;
			}		
		} else {
			if($no_of_days <> NULL){
				$mod_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + $no_of_days, date("Y")));
				$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/".$mod_date."/ -name \*".$filetype;
			} else {	
				$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/ -name \*".$filetype;
			}	
		}
		$string = trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
		$temp_array = explode("\n", $string);
		$array = array_map('trim', $temp_array);// For trimming newlines
		sort($array);
		return $array;
	}

	public function list_incremental_backups($storage_server = STORAGE_SERVER_1, $filetype = ".mbb", $hostname = NULL) {
		if($hostname <> NULL){
			$command_to_be_executed = "find /var/www/html/".GAME_ID."/".$hostname."/".MEMBASE_CLOUD."/incremental/ -name \*".$filetype; 
		} else {
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/ -name \*".$filetype; 
		}
		$string = trim(remote_function::remote_execution($storage_server, $command_to_be_executed));
		$temp_array = explode("\n", $string);
		$array = array_map('trim', $temp_array);// For trimming newlines
		sort($array);
		return $array;
	}

}
?>