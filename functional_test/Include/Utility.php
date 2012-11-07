<?php 

class Utility {
	
	public static function proxy_header($mb=TEST_HOST_1, $port=MEMBASE_PORT_NO) {
		if (PROXY_RUNNING)
			return "A:$mb:$port";
		else
			return "";
	}
	public function netcat_execute($testkey, $flag, $testvalue, $servername){
		$stringlen = strlen($testvalue);
		if(self::verify_membase_DI_capable($servername)){
			shell_exec("echo -ne 'set '$testkey' '$flag' 0 '$stringlen' 0001:\r\n'$testvalue'\r\n' | nc '$servername' '".MEMBASE_PORT_NO."'");
		} else {
			shell_exec("echo -ne 'set '$testkey' '$flag' 0 '$stringlen'\r\n'$testvalue'\r\n' | nc '$servername' '".MEMBASE_PORT_NO."'");
		}
	}
		// To support old style checksum in pecl
	public function get_flag_checksum_test(){
		if(	self::verify_php_pecl_DI_capable() && 
			self::verify_membase_DI_capable(TEST_HOST_1) && 
			self::verify_mcmux_DI_capable()){
			return 0;
		} else {
			return 8;
		}
	}
	
	public function verify_php_pecl_DI_capable(){
		if(general_function::execute_command("cat /etc/php.d/memcache.ini | grep data_integrity_enabled") <> "")
			return True;
		else 
			return False;
	}
	
	public function verify_mcmux_DI_capable(){
		if(PROXY_RUNNING == False){
			log_function::debug_log("verify_mcmux_DI_capable: mcmux is not running");
			return True;
		} else {
			if(stristr(PROXY_RUNNING, "mcmux")){
				$proxy_output = trim(general_function::execute_command("sudo /etc/init.d/mcmux stats | grep chksum"));
			} else {
				$proxy_output = trim(general_function::execute_command("sudo /etc/init.d/moxi stats | grep chksum"));
			} 
			if(stristr($proxy_output,"not running")){
				return True;
			} else {
				if(stristr($proxy_output, "chksum")){
					return True;
				} else {
					return False;
				}	
			}		
		}
	}
	
	public function verify_membase_DI_capable($remote_machine_name){
		// check if DI is implemented in the destination membase server
		$cksum_output = trim(shell_exec("echo stats | nc $remote_machine_name 11211 | grep cksum"));
		if(stristr($cksum_output, "cksum")){
			log_function::debug_log("verify_membase_DI_capable: ".$cksum_output);
			return True;
		} else {
			log_function::debug_log("verify_membase_DI_capable: ".$cksum_output);
			return False;
		}	
	}
		
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
	
	private function parseLoggerFile($logpath, $lines = 1) {

		$bulk = shell_exec("tail -$lines $logpath");
		$bulk = explode("\n", $bulk);
		foreach($bulk as $output){
			if ($lines-- == 0) break;
			if ($output == NULL or $output == ""){
				$output = array("NA", "NA", "NA", "NA", "NA", "NA", "NA", "NA", "NA");
			} else {
				$output = explode("]", $output);
				$output = str_replace(":", "", $output);
				$output = explode(" ", $output[1]);
			}
			$log_output[] = array("logname" => $output[1], 
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
		}
		if (!(stristr($logpath, "/var/log"))) shell_exec("> ".$logpath);
		if (count($log_output) > 1)
			return $log_output;
		else
			return $log_output[0];
		
	}	

	public function parseLoggerFile_syslog(){
		$logpath = "/var/log/pecl-memcache.log";
		return self::parseLoggerFile($logpath);
	}
	
	public function parseLoggerFile_temppath($lines = 1){
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
	
	public function Check_keys_are_persisted($remote_machine_name = TEST_HOST_1, $no_of_keys_persisted = False , $no_of_attempts = 10){
	
		// Check is based on two stats
		// Assumption is ep_queue_size and ep_flusher_todo will be zero if all the items are persisted
		for($iattempt = 0 ; $iattempt< $no_of_attempts ; $iattempt++){
			$stats_output = stats_functions::get_all_stats($remote_machine_name);
			if(($stats_output["ep_queue_size"] == 0) And ($stats_output["ep_flusher_todo"] == 0)){
				if($no_of_keys_persisted <> False){
					if(stats_functions::get_all_stats($remote_machine_name, "ep_total_persisted") == $no_of_keys_persisted ){
						return True;
					}
				}
				return True;
			} else {
				sleep(2);
			}
		}
		log_function::debug_log("Persistance of key has failed in ".$remote_machine_name);
		return False;
	}
	
	public function Get_ep_total_persisted($remote_server, $ep_total_persisted_count = -1){ // configure this for other machines
		if($ep_total_persisted_count <> -1){
			for($iattempt = 0 ; $iattempt< 4; $iattempt++){
				if(stats_functions::get_all_stats($remote_server, "ep_total_persisted") > $ep_total_persisted_count)
					return True;
				else
					usleep(500);
			}
			log_function::debug_log("ep_total_persisted didn't increment in ".$remote_server);	
			return False;
		} else {
			return stats_functions::get_all_stats($remote_server, "ep_total_persisted");
		}	
	}

	public function mutate_key($instance ,$key, $val, $no_of_mutations,$period){
		for($count=1;$count<=$no_of_mutations;$count++){
			$instance->set($key,$val);
			sleep($period);
			if($count%10 == 0)
			sleep(1);
		}
	}	
	
} 


?>