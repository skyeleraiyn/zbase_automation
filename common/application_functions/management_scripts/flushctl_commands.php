<?php 
class flushctl_commands{
	public function Run_flushctl_command($remote_machine_name, $command_to_be_executed){
		return remote_function::remote_execution($remote_machine_name, FLUSHCTL_SCRIPT." localhost:".ZBASE_PORT_NO." ".$command_to_be_executed);
	}  

	// Set flushctl parameters
	public function set_flushctl_parameters($remote_machine_name, $parameter, $value) {
		switch($parameter){
			case "chk_max_items":
				if($value<100 and $value>500000) {
					debug_log("WARNING: Input value for chk_max_items is not in range. Using default(500)");
					$value = 500;
				}
				break;
			case "chk_period":
				if($value<60 and $value>3600) {
					debug_log("WARNING: Input value for chk_period is not in range. Using default(60)");
					$value = 60;
				}	
				break;
			case "inconsistent_slave_chk":
				if(!(!strcasecmp($value, "true") or !strcasecmp($value, "false"))) {
					log_function::debug_log("WARNING: Input value for inconsistent_slave_chk not boolean. Using default(false)");
					$value = "false";
				}
				break;
		}
		return self::Run_flushctl_command($remote_machine_name, " set ".$parameter." ". $value);
	}

    	
	// Evict command
	function evictKeyFromMemory($remote_machine_name, $keyname, $sleep_time_after_eviction = 0){
		usleep(500);
			// Attempt for 1s to evict the key, else report failure
		for($iattempt=0 ; $iattempt<3; $iattempt++){
			$evict_output = self::Run_flushctl_command($remote_machine_name, " evict ".$keyname);
			if(stristr($evict_output, "error")){
				usleep(100);
				continue;	
			} else {
				usleep(500);
				sleep($sleep_time_after_eviction);
				return True;
			}
		}
		return False;
	}

	
  // set max_size in bytes
	function Set_max_size($ServerName, $max_size){
		self::Run_flushctl_command($ServerName, " set max_size ".$max_size);
	}
	
	// Set bg_fetch_delay
	function Set_bg_fetch_delay($ServerName, $bg_fetch_delay){
		self::Run_flushctl_command($ServerName, " set bg_fetch_delay ".$bg_fetch_delay);
	}

// Persistance command
	public function Start_Stop_Persistance($remote_machine_name, $State){
		self::Run_flushctl_command($remote_machine_name, $State);
	}
		
}	

?>
