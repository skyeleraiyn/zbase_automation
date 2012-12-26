<?php

class storage_server_functions{
	
	public function run_master_merge() {
		$command_to_be_executed = "sudo python26 ".MASTER_MERGE_FILE_PATH;
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}

	public function run_daily_merge() {
		$command_to_be_executed = "sudo python26 ".DAILY_MERGE_FILE_PATH;
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
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

	public function edit_date_folder($days=-1, $type="daily") {
		$path = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/".date("Y-m-d");
		$date = mktime(0,0,0,date("m"),date("d")+$days,date("Y"));
		$mod_date = date("Y-m-d", $date);
		$new_path = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/".$mod_date;
		$command_to_be_executed = "sudo mv $path $new_path";
		remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		sleep(1);
		return $new_path;
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

	public function delete_split_file_entry($line_no) {
		$array = self::list_incremental_backups("*.split");
		$command_to_be_executed = "sudo sed -i '".$line_no."d' $array[0]";
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}

	public function delete_done_daily_merge() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/done*";
		return remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
	}
	
	public function get_merged_files($type) {
		$return_array = array();
		if($type == "incremental"){ 
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/*.mbb"; 
		} else {
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/*/*.mbb";
		}
		$return_list = trim(remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed));
		$return_array = explode("\n", $return_list);
		return($return_array);

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
	
	public function list_daily_backups($filetype = ".mbb", $date = NULL) {
		if($date <> NULL){
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/".$date."/ -name \*".$filetype;
		} else {	
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/ -name \*".$filetype;
		}	
		$string = trim(remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed));
		$array = preg_split("/(\n)/", $string);
		sort($array);
		return $array;
	}

	public function list_master_backups($filetype = ".mbb", $date = NULL) {
		if($date <> NULL){
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/".$date."/ -name \*".$filetype;
		} else {
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/ -name \*".$filetype;
		}
		$string = trim(remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed));
		$temp_array = explode("\n", $string);
		$array = array_map('trim', $temp_array);// For trimming newlines
		sort($array);
		return $array;
	}

	public function list_incremental_backups($filetype = ".mbb") {
		$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/ -name \*".$filetype; 
		$string = remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		$string = trim($string);
		$array = array();
		$array = preg_split("/(\n)/", $string);
		sort($array);
		return $array;
	}

	public function copy_master_backups() {
		$command_to_be_executed= "mkdir -p /tmp/copy_of_backups/master_backups; sudo rm -rf /tmp/copy_of_backups/master_backups/*;";
		remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		$backups_array=self::list_master_backups();
		$split_array=self::list_master_backups(".split");
		foreach($backups_array as $backup) {
			$command_to_be_executed = "sudo cp $backup /tmp/copy_of_backups/master_backups/";
			remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		}
		foreach($split_array as $split) {
			$command_to_be_executed = "sudo cp $split /tmp/copy_of_backups/master_backups/";
			remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		}
	}
	

}
?>