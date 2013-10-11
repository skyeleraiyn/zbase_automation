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
class stats_commands{
	public function get_stats_from_management_script($remote_machine_name, $stat_name){
		return remote_function::remote_execution($remote_machine_name, STATS_SCRIPT." localhost:".ZBASE_PORT_NO." ".$stat_name);
	}
	
	public function get_all_stats_from_management_script($remote_machine_name){
		return self::get_stats_from_management_script($remote_machine_name, "all");
	}

	public function get_timings_stats_from_management_script($remote_machine_name){
		return self::get_stats_from_management_script($remote_machine_name, "timings");
	}

	public function get_tap_stats_from_management_script($remote_machine_name){
		return self::get_stats_from_management_script($remote_machine_name, "tap");
	}

	public function get_checkpoint_stats_from_management_script($remote_machine_name){
		return self::get_stats_from_management_script($remote_machine_name, "checkpoint");
	}
	
	public function get_vkey_stats_from_management_script($remote_machine_name, $keyname){
		return self::get_stats_from_management_script($remote_machine_name, "vkey $keyname 0");
	}	
	
	public function capture_all_stats_to_file($remote_machine_name, $stat_dump_path){
		$message_to_log = self::get_all_stats_from_management_script($remote_machine_name);
		file_function::write_to_file($stat_dump_path."_all_stats.log", $message_to_log, "w");
	}	
	
	public function capture_timings_stats_to_file($remote_machine_name, $stat_dump_path){
		$message_to_log = self::get_timings_stats_from_management_script($remote_machine_name);
		file_function::write_to_file($stat_dump_path."_timings_stats.log", $message_to_log, "w");
	}

	public function capture_tap_stats_to_file($remote_machine_name, $stat_dump_path){
		$message_to_log = self::get_tap_stats_from_management_script($remote_machine_name);
		file_function::write_to_file($stat_dump_path."_tap_stats.log", $message_to_log, "w");
	}	

	public function capture_checkpoint_stats_to_file($remote_machine_name, $stat_dump_path){
		$message_to_log = self::get_checkpoint_stats_from_management_script($remote_machine_name);
		file_function::write_to_file($stat_dump_path."_checkpoint_stats.log", $message_to_log, "w");
	}
	
	private function get_eviction_stat($remote_machine_name){
		return remote_function::remote_execution($remote_machine_name,"echo 'stats eviction' | nc localhost ".ZBASE_PORT_NO);
	}	
	
	public function capture_eviction_stat_to_file($remote_machine_name, $stat_dump_path){
		$message_to_log = self::get_eviction_stat($remote_machine_name);
		file_function::write_to_file($stat_dump_path."_eviction_stats.log", $message_to_log, "w");
	}	
}	
?>
