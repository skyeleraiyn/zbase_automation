<?php 

class Utility {
	
	public static function check_compressed_length($testValue, $testFlags){
		
		$testValue_Length = strlen($testValue);
		switch ($testFlags) {
		case 0:
		case 32:
		default:
			return $testValue_Length;
		case 16:
		case 18:
		case 20:
		case 22:
			return ( $testValue_Length / 2.8 );
		case 2:
		case 6:
		case 34:
			return ( $testValue_Length / 2.5 );
		case 4:
		case 36:
			return ( $testValue_Length / 1.5 );
		}
	}

	public static function time_compare($starttime, $endtime, $logtime){
		$ourtime = $endtime - $starttime;
		if($logtime == 0) return false;
		//10ms extra and some buffer
		if($ourtime - $logtime > 10) return false;
		return true;
	}	
	
	private function parseLoggerFile($logpath) {

		$output = shell_exec("tail -1 $logpath");
		if ($output == NULL or $output == ""){
			$output = array("NA", "NA", "NA", "NA", "NA", "NA", "NA", "NA", "NA");
		} else {
			$output = explode("]", $output);
			$output = str_replace(":", "", $output);
			$output = explode(" ", $output[1]);
			if (!(stristr($logpath, "/var/log"))) shell_exec("> ".$logpath);
		}
		$log_output = array("logname" => $output[1], 
				"host" => $output[2],
				"apache_pid" => $output[3],
				"command" => $output[4],
				"key" => $output[5],
				"res_len" => $output[6],
				"res_code" => $output[7],
				"flags" => $output[8],
				"expire" => $output[9],
				"cas" => $output[10],
				"res_time" => $output[11],
				"serialize_time" => $output[12]);	
		return $log_output;
		
	}

	public function parseLoggerFile_syslog(){
		$logpath = "/var/log/pecl-memcache.log";
		return self::parseLoggerFile($logpath);
	}
	
	public function parseLoggerFile_temppath(){
		$logpath = "/tmp/pecl-memcache.log";
		return self::parseLoggerFile(PECL_LOGGING_FILE_PATH);	
	}	
	
	public function EvictKeyFromMemory_Master_Server($keyname, $sleep_time_after_eviction = 0){
		return flushctl_commands::evictKeyFromMemory(TEST_HOST_1, $keyname, $sleep_time_after_eviction);
	}

	public function EvictKeyFromMemory_Server_Array($keyname, $sleep_time_after_eviction = 0){
		if(!flushctl_commands::evictKeyFromMemory(TEST_HOST_1, $keyname, $sleep_time_after_eviction))
			return False;
		if(flushctl_commands::evictKeyFromMemory(TEST_HOST_2, $keyname, $sleep_time_after_eviction))
			return False;
		if(flushctl_commands::evictKeyFromMemory(TEST_HOST_3, $keyname, $sleep_time_after_eviction))
			return False;
	}	

	public function Set_bg_fetch_delay_Master_Server($bg_fetch_delay){
		flushctl_commands::Set_bg_fetch_delay(TEST_HOST_1, $bg_fetch_delay);
	}
	
	public function Check_keys_are_persisted($no_of_attempts = 5){
	
		// Check is based on two stats
		// Assumption is ep_queue_size and ep_flusher_todo will be zero if all the items are persisted
		for($iattempt = 0 ; $iattempt< $no_of_attempts ; $iattempt++){
			$stats_output = stats_functions::get_all_stats(TEST_HOST_1);
			if(($stats_output["ep_queue_size"] == 0) And ($stats_output["ep_flusher_todo"] == 0)){
				return True;
			} else {
				sleep(1);
			}
		}
		exit;
		log_function::debug_log("Persistance of key has failed in ".TEST_HOST_1);
		return False;
	}
	
	public function Get_ep_total_persisted($ep_total_persisted_count = -1){
		if($ep_total_persisted_count <> -1){
			for($iattempt = 0 ; $iattempt< 4; $iattempt++){
			if(stats_functions::get_stat(TEST_HOST_1, "ep_total_persisted") > $ep_total_persisted_count)
				return True;
			else
				usleep(500);
			}
			log_function::debug_log("ep_total_persisted didn't increment in ".TEST_HOST_1);	
			return False;
		} else {
			return stats_functions::get_stat(TEST_HOST_1, "ep_total_persisted");
		}	
	}
	
} 


?>