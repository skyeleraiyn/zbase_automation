<?php
class membase_function{
 
	public function define_membase_db_path(){
	
		// define db path name based on Cent OS version and MULTI_KV_STORE 
		if(defined('MULTI_KV_STORE') && MULTI_KV_STORE <> 0){
			$drive_array = array();
			for($idrive=1; $idrive<MULTI_KV_STORE + 1; $idrive++){
				if(CENTOS_VERSION == 5){
					$drive_array[] = "/db/membase/";
					break;
				} else {
					$drive_array[] = "/data_".$idrive."/membase/";
				}	
			}
			define('MEMBASE_DATABASE_PATH', serialize($drive_array));
		} else { 
			if(CENTOS_VERSION == 5){
				define('MEMBASE_DATABASE_PATH', serialize(array("/db/membase/")));
			} else {
				define('MEMBASE_DATABASE_PATH', serialize(array("/data_1/membase/")));
			}
		}
		log_function::write_to_temp_config("MEMBASE_DATABASE_PATH=".MEMBASE_DATABASE_PATH, "a");
	}
 
 	public function copy_membase_log_file($remote_machine_name, $destination_path){
		remote_function::remote_file_copy($remote_machine_name, MEMBASE_LOG_FILE, $destination_path."_membase.log", True);
	}

	public function get_membase_memory($remote_machine_name, $return_scale = NULL){
	 $command_output = trim(remote_function::remote_execution($remote_machine_name, "ps elf -U nobody | grep memcached | grep -v grep | awk '{print $8}'"));
	 
		if(isset($command_output) && $command_output <> ""){
			switch($return_scale){
				case "GB":
				return round(($command_output / 1048576), 2);
				case "MB":
				return round(($command_output / 1024), 2);
				case "KB":
				default:
				return round($command_output, 2);
			}
		} else{
			return "NA";
		}	
	}

	public function get_membase_db_size($remote_machine_name){
		$membase_dbpath = "";
		foreach(unserialize(MEMBASE_DATABASE_PATH) as $db_path){
			$membase_dbpath = $membase_dbpath.$db_path." "; 
		}
		return file_function::get_file_size($remote_machine_name, $membase_dbpath);
	}	

 	public function get_sqlite_item_count($remote_machine_name) {
		// Returns an array $count[] for total items under each disk partition
		$drive_array = unserialize(MEMBASE_DATABASE_PATH);
		$ep_dbshards = stats_functions::get_all_stats($remote_machine_name, "ep_dbshards");
		for($i=0;$i<count($drive_array);$i++){
			$count[$i] = 0;
			for($j=0;$j<$ep_dbshards;$j++){
				$file = $drive_array[$i]."/ep.db-".$j.".sqlite";
				$count[$i]+=sqlite_functions::sqlite_count($remote_machine_name, "kv", $file);
			}
		}             
		return $count;
	}
 
 	public function sqlite_cpoint_count($remote_machine_name, $file) { 
		return sqlite_functions::sqlite_count($remote_machine_name, "cpoint_op", $file);
	}

	public function sqlite_chkpoint_count($remote_machine_name, $file){
		return sqlite_functions::sqlite_count($remote_machine_name, "cpoint_state", $file);
	}

	public function db_sqlite_select($remote_machine_name, $field, $table_name)	{
		$db_checksum_array = array();
		$temp_array = array();
		$ep_dbshards = stats_functions::get_all_stats($remote_machine_name, "ep_dbshards");
		foreach(unserialize(MEMBASE_DATABASE_PATH) as $membase_dbpath){
			for ($i=0; $i<$ep_dbshards; $i++)	{
				$db_checksum_array = array_merge($temp_array, explode("\n", sqlite_functions::sqlite_select($remote_machine_name, $field, $table_name, $membase_dbpath."/ep.db-$i.sqlite")));
				$temp_array = $db_checksum_array;
			}
		}
		sort($db_checksum_array);
		return($db_checksum_array);
	}

	public function restart_membase_after_persistance(){
		if(Utility::Check_keys_are_persisted())
		 	return membase_setup::restart_membase_servers(TEST_HOST_1);
		else
			return False;
	}
	
	public function wait_for_LRU_queue_build($remote_machine_name, $initial_value=0, $timeout = 15){
		for($i=0 ; $i< $timeout ; $i++){
			$current_value = stats_functions::get_eviction_stats($remote_machine_name, "evpolicy_job_start_timestamp");
			if($current_value <> $initial_value){
				log_function::debug_log("rebuild took $i secs; returning True");
				return True;
			} else {	
				sleep(1);
			}
		}
		log_function::debug_log("rebuild took $i secs; returning False");
		return False;
	}
}	
?>