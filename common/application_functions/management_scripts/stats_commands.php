<?php
class stats_commands{
	public function get_stats_from_management_script($remote_machine_name, $stat_name){
		return remote_function::remote_execution($remote_machine_name, STATS_SCRIPT." localhost:".MEMBASE_PORT_NO." ".$stat_name);
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
		return remote_function::remote_execution($remote_machine_name,"echo 'stats eviction' | nc localhost ".MEMBASE_PORT_NO);
	}	
	
	public function capture_eviction_stat_to_file($remote_machine_name, $stat_dump_path){
		$message_to_log = self::get_eviction_stat($remote_machine_name);
		file_function::write_to_file($stat_dump_path."_eviction_stats.log", $message_to_log, "w");
	}	
}	
?>