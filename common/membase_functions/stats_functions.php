<?php
class stats_functions{
	public function get_warmup_time_ascii($server_name){
		return shell_exec("echo stats | nc $server_name ".MEMBASE_PORT_NO." | grep ep_warmup_time");
	}

	public function get_stats_array($server_name, $stat_option = NULL){
		$conn = memcache_pconnect($server_name, MEMBASE_PORT_NO);
		$stats_array = $conn->getstats($stat_option);
		memcache_close($conn);
		return $stats_array;
	}
	
	public function get_all_stats($server_name){
		return self::get_stats_array($server_name);
	}

	public function get_checkpoint_stats($server_name){
		return self::get_stats_array($server_name, "checkpoint");
	}

	public function get_hash_stats($server_name){
		return self::get_stats_array($server_name, "hash");
	}

	public function get_tap_stats($server_name){
		return self::get_stats_array($server_name, "tap");
	}

	public function get_timings_stats($server_name){
		return self::get_stats_array($server_name, "timings");
	}

	public function reset_stats_counters($server_name){
		return self::get_stats_array($server_name, "reset");
	}
	
	public function get_vkey_stats($server_name, $test_key){
		return self::get_stats_array($server_name, "vkey ".$test_key." 0");
	}

	public function get_cas_value($server_name, $test_key){
		$vkey_stats_array = get_vkey_stats($server_name, $test_key);
		return $vkey_stats_array["key_cas"];
	}
	
	public function get_raw_stats($server_name, $raw_stat){
		return self::get_stats_array($server_name, "raw ".$raw_stat);
	}
	
	public function get_ep_version($server_name){

		return self::get_stat($server_name, "ep_version");
	}
	
	public function get_stat($server_name, $stat_name, $test_key = NULL){
	
		switch($stat_name){
			case "ep_flusher_state":
			case "bytes_read":
			case "bytes_written":
			case "cas_hits":
			case "cas_misses":
			case "cmd_flush":
			case "cmd_get":
			case "cmd_set":
			case "curr_connections":
			case "curr_items":
			case "curr_items_tot":
			case "daemon_connections":
			case "decr_hits":
			case "delete_hits":
			case "delete_misses":
			case "ep_bg_fetched":
			case "ep_commit_time":
			case "ep_commit_time_total":
			case "ep_dbname":
			case "ep_dbshards":
			case "ep_exp_pager_stime":
			case "ep_flush_duration":
			case "ep_flush_duration_total":
			case "ep_flusher_todo":
			case "ep_inconsistent_slave_chk":
			case "ep_items_rm_from_checkpoints":
			case "ep_keep_closed_checkpoints":
			case "ep_kv_size":
			case "ep_mem_high_wat":
			case "ep_mem_low_wat":
			case "ep_min_data_age":
			case "ep_num_eject_failures":
			case "ep_num_expiry_pager_runs":
			case "ep_num_non_resident":
			case "ep_num_pager_runs":
			case "ep_num_value_ejects":
			case "ep_oom_errors":
			case "ep_overhead":
			case "ep_queue_age_cap":
			case "ep_queue_size":
			case "ep_storage_age":
			case "ep_storage_type":
			case "ep_tap_bg_fetch_requeued":
			case "ep_tap_bg_fetched":
			case "ep_tap_keepalive":
			case "ep_tmp_oom_errors":
			case "ep_total_cache_size":
			case "ep_total_enqueued":
			case "ep_total_persisted":
			case "ep_vb_total":
			case "ep_version":
			case "ep_warmup_oom":
			case "ep_warmup_thread":
			case "ep_warmup_time":
			case "get_hits":
			case "get_misses":
			case "mem_used":
			case "rejected_conns":
			case "threads":
			case "time":
			case "total_connections":
			case "uptime":
			case "version":
				$get_all_stats_array = self::get_all_stats($server_name, $test_key);
				return trim($get_all_stats_array[$stat_name]);
				
			case "key_cas":
			case "key_data_age":
			case "key_dirtied":
			case "key_exptime":
			case "key_flags":
			case "key_is_dirty":
			case "key_last_modification_time":
			case "key_valid":			
				if(!($test_key)){
					return "requires test_key as input";
				} else {	
					$vkey_stats_array = self::get_vkey_stats($server_name, $test_key);
					if(array_key_exists($stat_name, $vkey_stats_array)){
						return trim($vkey_stats_array[$stat_name]);
					} else {
						return "cannot find the key";
					}
				}
		
		}
	
	}
	
	public function get_registered_tapname($sever_name){
		$checkpoint_stats_array = self::get_checkpoint_stats($sever_name);
		if(is_array($checkpoint_stats_array)){
			if(array_key_exists("cursor_checkpoint_id", $checkpoint_stats_array["vb_0"])){
				return key($checkpoint_stats_array["vb_0"]["cursor_checkpoint_id"]["eq_tapq"]);
			} else {
				return False;
			}	
		} else {
			return False;
		}	
	}	
	
}

?>