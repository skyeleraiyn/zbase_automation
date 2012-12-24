<?php
class diskmapper_functions{
	
	public function modify_diskmapper_config_file($remote_machine, $modify_storage_server = NULL, $modify_zruntime_settings = NULL){
		global $storage_server_pool;
		
		$zruntime_settings = array(
			'username' => ZRUNTIME_USERNAME,
			'password' => ZRUNTIME_PASSWORD,
			'gameid' => GAME_ID,
			'env' => EVN,
			'mcs_key_name' => ACTIVE_DISKMAPPER_KEY 
			);
			
		if($modify_zruntime_settings <> NULL){
			foreach($modify_zruntime_settings as $key => $value){
				$zruntime_settings[$key] = $value;
			}
		}
		
		if($modify_storage_server <> NULL){
			$server_pool = $modify_storage_server;
		} else {
			$server_pool = $storage_server_pool;
		}
			
		file_function::add_modified_file_to_list($remote_machine, DISK_MAPPER_CONFIG);
		$string = self::create_config_file($zruntime_settings, $server_pool);
		self::update_diskmapper_config_file($remote_machine, $string);
	}
	
	public function create_config_file($zruntime_settings, $storage_server_pool){
	
		$string = "#!/usr/bin/env python \n\n";

			// server info
		$string = $string."config = {\n\t'storage_server':\n\t\t[\n"; 
		foreach($storage_server_pool as $server){
			if(!filter_var($server, FILTER_VALIDATE_IP)) {
				$ip_address = general_function::get_ip_address($server);
				$server = $ip_address[0];
			}
			$string = $string."\t\t'".$server."',\n";
		}
		$string = $string."\t\t],\n";
		
			// zruntime settings
		$string = $string."\t'zruntime':\n\t\t{\n";
		foreach($zruntime_settings as $key => $value){
			$string = $string."\t\t'".$key."' : '".$value."',\n";
		}
		$string = $string."\t\t},\n}\n";
		return $string;
	
	}
	
	public function update_diskmapper_config_file($remote_machine, $string){
		$temp_file_path = "/tmp/config.py";
		file_put_contents($temp_file_path, $string);
		remote_function::remote_file_copy($remote_machine, $temp_file_path, DISK_MAPPER_CONFIG, False, True, True);
		unlink($temp_file_path);
	}
			
	public function query_diskmapper_log_file($disk_mapper_server, $message_to_be_verified, $timeout = 10){
		for($icount=0 ; $icount< $timeout ; $icount++){
			$log_output = remote_function::remote_execution($disk_mapper_server , "cat ".DISK_MAPPER_LOG_FILE);
			if(stristr($log_output, $message_to_be_verified)){
				return True;
			} else {
				sleep(3);
			}
		}
		return False;
	}

	public function query_diskmapper_hostmapping_file($disk_mapper_server, $message_to_be_searched, $timeout = 10){
		for($icount=0 ; $icount< $timeout ; $icount++){
			$log_output = explode("\n", trim(remote_function::remote_execution($disk_mapper_server , "cat ".DISK_MAPPER_HOST_MAPPING." | grep ".$message_to_be_searched)));
			if($log_output > 0){
				return count($log_output);
			} else {
				sleep(3);
			}
		}
		return False;
	}	

	public function verify_mapping_is_created($host_name, $timeout = 20){
		for($icount = 0 ; $icount < $timeout; $icount++){
			$parsed_hostmap = diskmapper_api::get_all_config();
			if(count($parsed_hostmap[$host_name]) > 1){
				return True;
			} else {
				sleep(1);
			}
		}
		return False;
	}
	
	//Gives basic mapping //If more information is needed in terms of about 'status', 'disk' or 'storage_server' 
	public function get_primary_partition_mapping($host_name = NULL)	{
		$parsed_hostmap = diskmapper_api::get_all_config();
		if($host_name == NULL){
			$primary_storage_server_mapping = array();
			foreach ($parsed_hostmap as $host_name => $value) {
				$primary_storage_server_mapping[$host_name] = $value['primary'];
			}
			return($primary_storage_server_mapping);
		}
		return($parsed_hostmap[$host_name]['primary']);

	}

	//Gives basic mapping //If more information is needed in terms of about 'status', 'disk' or 'storage_server'.
	public function get_secondary_partition_mapping($host_name = NULL) {
		$parsed_hostmap = diskmapper_api::get_all_config();
		if($host_name == NULL){
			$secondary_storage_server_mapping = array();
			foreach ($parsed_hostmap as $host_name => $value) {
				$secondary_storage_server_mapping[$host_name] = $value['secondary'];
			}
			return($secondary_storage_server_mapping);
		}
		return($parsed_hostmap[$host_name]['secondary']);
	}
	
	public function get_mapping_param($host_name, $storage_type, $param){
		$parsed_hostmap = diskmapper_api::get_all_config();
		return $parsed_hostmap[$host_name][$storage_type][$param];
	}
	
	public function compare_primary_secondary($host_name)	{
		$parsed_hostmap = diskmapper_api::get_all_config();
		$PrimSS = $parsed_hostmap[$host_name]['primary']['storage_server'];
		$SecSS = $parsed_hostmap[$host_name]['secondary']['storage_server'];
		$PrimDisk = $parsed_hostmap[$host_name]['primary']['disk'];
		$PrimDisk = "/".$PrimDisk."/primary/";
		$SecDisk = $parsed_hostmap[$host_name]['secondary']['disk'];
		$SecDisk = "/".$SecDisk."/secondary/";
		return torrent_functions::verify_torrent_sync_across_servers(array($PrimSS => $PrimDisk, $SecSS => $SecDisk));
		
	}	

	public function add_bad_disk($host_name, $type){ 
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];
		$status = diskmapper_api::curl_call("http://$storage_server/api/?action=add_entry&type=bad_disk&entry=$disk");
		if(stristr($status, "Success")) {
			return True;
		} else {
			return False;
		}
	}

	public function remove_bad_disk($host_name, $type){
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];	
		$status = diskmapper_api::curl_call("http://$storage_server/api/?action=remove_entry&type=bad_disk&entry=$disk");
		if(stristr($status, "Success")) {
			return True;
		} else {
			return False;
		}
	}

	public function add_dirty_entry($host_name, $type, $entry)	{
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];			
		$status = diskmapper_api::curl_call("http://$storage_server/api/?action=add_entry&type=dirty_files&entry=$entry");
		if(stristr($status, "Success")) {
			return True;
		} else {
			return False;
		}
	}

	public function remove_dirty_entry($host_name, $type, $entry)	{
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];
		$status = diskmapper_api::curl_call("http://$storage_server/api/?action=remove_entry&type=dirty_files&entry=$entry");
		if(stristr($status, "Success")) {
			return True;
		} else  {
			return False;
		}
	}		

	public function get_file_path_from_disk_mapper($file_name, $host_name, $type = 'primary', $parameter = "test"){
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];	
		$file_name = basename($file_name);
		return "/$disk/$type/$host_name/".MEMBASE_CLOUD."/$parameter/$file_name";
	}	
	
	public function wait_until_param_change($host_name, $storage_type, $param, $current_value){
		for($itimeout = 0 ; $itimeout<60 ; $itimeout++){
			$parsed_hostmap = diskmapper_api::get_all_config();
			if($parsed_hostmap[$host_name][$storage_type][$param] <> $current_value){
				return True;
			} else {
				sleep(1);
			}
		}
		return False;
	}

}
?>