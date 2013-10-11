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
class stats_functions{
		// stats through netcat
	public function get_stats_netcat($server_name, $stats_to_be_searched, $parameter = ""){
		return general_function::execute_command("echo stats ".$parameter."| nc $server_name ".ZBASE_PORT_NO." | grep ".$stats_to_be_searched);
	}

		// Basic stats
	public function get_stats_array($server_name, $stat_option = NULL){
		for($iattempt = 0 ;$iattempt < 50; $iattempt++ ){
			$conn = @memcache_connect($server_name, ZBASE_PORT_NO);
			if(is_object($conn)) break;
			sleep(1);
		}	
		$stats_array = $conn->getstats($stat_option);
		memcache_close($conn);
		return $stats_array;
	}
	
	public function get_all_stats($server_name, $specific_stat_name = NULL){
		$all_stats_output = self::get_stats_array($server_name);
		if($specific_stat_name){
			if(array_key_exists($specific_stat_name, $all_stats_output)){
				return trim($all_stats_output[$specific_stat_name]);
			} else {
				return "NA";
			}	
		} else {
			return $all_stats_output;
		}
	}
	
	public function get_checkpoint_stats($server_name, $specific_stat_name = NULL){
		$acheckpoint_stats = self::get_stats_array($server_name, "checkpoint");
		$acheckpoint_stats = $acheckpoint_stats["vb_0"];
		if($specific_stat_name){
			if(array_key_exists($specific_stat_name, $acheckpoint_stats)){
				if($specific_stat_name == "cursor_checkpoint_id"){
					return trim(key($acheckpoint_stats["cursor_checkpoint_id"]["eq_tapq"]));
				}
				return trim($acheckpoint_stats[$specific_stat_name]);
			} else {
				return "NA";
			}			
		}
		return $acheckpoint_stats;
	}

	public function get_hash_stats($server_name, $specific_stat_name = NULL){
		$ahash_stats = self::get_stats_array($server_name, "hash");
		if($specific_stat_name){
			$ahash_stats = $ahash_stats["vb_0"];
			if(array_key_exists($specific_stat_name, $ahash_stats)){
				return trim($ahash_stats[$specific_stat_name]);
			} else {
				return "NA";
			}			
		}		
		return $ahash_stats;
	}

	public function get_tap_stats($server_name, $specific_stat_name = NULL){
		return self::get_stats_array($server_name, "tap");
	}

	public function get_timings_stats($server_name, $specific_stat_name = NULL){
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
		
	public function get_kvstore_stats($server_name , $specific_stat_name = NULL){
        return self::get_stats_array($server_name,"kvstore");
	}
		
	public function get_eviction_stats($server_name , $specific_stat_name = NULL){
		$eviction_stats= self::get_stats_array($server_name,"eviction");
		if($specific_stat_name){
			if(array_key_exists($specific_stat_name, $eviction_stats)){
				return trim($eviction_stats[$specific_stat_name]);
			} else {
				return "NA";
			}
		} else {
			return $eviction_stats;
		}
	}
		
}

?>
