/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php
class diskmapper_functions{

	public function modify_diskmapper_config_file($remote_machine, $modify_storage_server = NULL, $modify_zruntime_settings = NULL){
		global $storage_server_pool;

		$zruntime_settings = array(
			'username' => ZRUNTIME_USERNAME,
			'password' => ZRUNTIME_PASSWORD,
			'gameid' => GAME_ID,
			'env' => EVN,
			'mcs_key_name' => ACTIVE_DISKMAPPER_KEY,
			'retries' => 60
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
		$string = $string."\t\t},\n\t'params':\n\t\t{\n\t\t'poll_interval' : 5,\n\t\t'log_level' : 'info',\n";
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

	public function query_disk_status_hostmapping_file($disk_mapper_server, $storage_server_ip, $storage_disk, $message_to_be_searched, $timeout = 6){
		for($icount=0 ; $icount< $timeout; $icount =  $icount + 3){
			$output = self::query_diskmapper_hostmapping_file($disk_mapper_server);
			if(!is_array($output)){
				sleep(3);
				continue;
			}
			if($output[$storage_server_ip][$storage_disk]["status"] == $message_to_be_searched){
				return True;
			} else {
				sleep(3);
			}
		}
		return False;
	}

	public function query_hostname_status_hostmapping_file($disk_mapper_server, $hostname, $server_role, $message_to_be_searched, $timeout = 6){
		for($icount=0 ; $icount< $timeout; $icount =  $icount + 3){
			$output = self::query_diskmapper_hostmapping_file($disk_mapper_server);
			if(!is_array($output)){
				sleep(3);
				continue;
			}
			foreach($output as $storage_server){
				foreach($storage_server as $disk_info){
					if($disk_info[$server_role] == $hostname) {
						if($disk_info["status"] == $message_to_be_searched){
							return True;
						} else {
							break 2;
						}
					}
				}
			}
			sleep(3);
		}
		return False;
	}

	public function query_diskmapper_hostmapping_file($disk_mapper_server){
		$output = trim(remote_function::remote_execution($disk_mapper_server , "python /tmp/pickle_json.py ".DISK_MAPPER_HOST_MAPPING));
		$output = json_decode($output, True);
		log_function::debug_log($output);
		return $output;
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
	        $host_name = general_function::get_hostname($host_name);
		if($host_name == NULL){
			$primary_storage_server_mapping = array();
			foreach ($parsed_hostmap as $host_name => $value) {
				$primary_storage_server_mapping[$host_name] = $value['primary'];
			}
			return($primary_storage_server_mapping);
		}
		return($parsed_hostmap[$host_name]['primary']);

	}


	public function get_vbucket_group($vb_id = NULL) {
		$parsed_hostmap= diskmapper_api::get_vb_mapping();
		if($vb_id == NULL) {
			$group_map=array();
			foreach($parsed_hostmap as $id => $map) {
				$group_map[$id]=$map['vb_group'];
			}
			return $group_map;
		}
        else {
		    return ($parsed_hostmap[$vb_id]['vb_group']);
        }
	}

	public function get_vbucket_path($vb_id = NULL) {
		$ss_map = diskmapper_api::get_ss_mapping();
		$path_map = array();
		foreach($ss_map as $ss=>$vb_map) {
			foreach($vb_map as $id => $details) {
				$path_map[$id]=$details['path_name'];
			}
		}
		if($vb_id == NULL) {
			return $path_map;
		}
		return $path_map[$vb_id];
	}

	public function get_vbucket_ss($vb_id = NULL) {
		$parsed_hostmap= diskmapper_api::get_vb_mapping();
		if($vb_id == NULL) {
			$group_map=array();
			foreach($parsed_hostmap as $id => $map) {
				$group_map[$id]=$map['storage_server'];
			}
			return $group_map;
		}
        else {
		    return ($parsed_hostmap[$vb_id]['storage_server']);
        }
	}



	//Gives basic mapping //If more information is needed in terms of about 'status', 'disk' or 'storage_server'.
	public function get_secondary_partition_mapping($host_name = NULL) {
		$parsed_hostmap = diskmapper_api::get_all_config();
     		$host_name = general_function::get_hostname($host_name);
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

	public function verify_both_disks_active($host_name, $time_out=200) {
        $host_name = general_function::get_hostname($host_name);
		$max_iter = $time_out/5;
		for($iter = 0; $iter <=$max_iter; $iter++) {
			$parsed_hostmap = diskmapper_api::get_all_config();
			if(array_key_exists("primary", $parsed_hostmap[$host_name]) AND array_key_exists("secondary", $parsed_hostmap[$host_name]))
				return True;
			else
				sleep(5);
		}
		log_function::debug_log("both disks not present in mapping after $time_out \n".$parsed_hostmap[$host_name]);
		return False;
        }

	public function compare_primary_secondary($host_name)	{
        $host_name = general_function::get_hostname($host_name);
		if(self::verify_both_disks_active($host_name)) {
			$parsed_hostmap = diskmapper_api::get_all_config();
			$PrimSS = $parsed_hostmap[$host_name]['primary']['storage_server'];
			$SecSS = $parsed_hostmap[$host_name]['secondary']['storage_server'];
			$PrimDisk = $parsed_hostmap[$host_name]['primary']['disk'];
			$PrimDisk = "/".$PrimDisk."/primary/";
			$SecDisk = $parsed_hostmap[$host_name]['secondary']['disk'];
			$SecDisk = "/".$SecDisk."/secondary/";
			return torrent_functions::verify_torrent_sync_across_servers(array($PrimSS => $PrimDisk, $SecSS => $SecDisk));
		} else {
			return False;
		}
	}

	public function add_bad_disk($host_name, $type){
		$host_name = general_function::get_hostname($host_name);
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];
		$status = diskmapper_api::curl_call("http://$storage_server/api/?action=add_entry&type=bad_disk&entry=$disk");
		if(stristr($status, "Success")) {
       	        	remote_function::remote_execution($storage_server, "sudo umount -l /$disk");
			return True;
		} else {
			return False;
		}
	}

	public function remove_bad_disk($host_name, $type){
        $host_name = general_function::get_hostname($host_name);
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];
		$status = diskmapper_api::curl_call("http://$storage_server/api/?action=remove_entry&type=bad_disk&entry=$disk");
		if(stristr($status, "Success")) {
                        remote_function::remote_execution($storage_server, "sudo mount  /$disk");
			return True;
		} else {
			return False;
		}
	}

	public function add_dirty_entry($host_name, $type, $entry)	{
        $host_name = general_function::get_hostname($host_name);
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
        $host_name = general_function::get_hostname($host_name);
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
        $host_name = general_function::get_hostname($host_name);
		$parsed_hostmap = diskmapper_api::get_all_config();
		$storage_server = $parsed_hostmap[$host_name][$type]['storage_server'];
		$disk = $parsed_hostmap[$host_name][$type]['disk'];
		$file_name = basename($file_name);
		return "/$disk/$type/$host_name/".ZBASE_CLOUD."/$parameter/$file_name";
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
