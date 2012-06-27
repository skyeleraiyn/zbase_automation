<?php 
class flushctl_commands{
	function Run_flushctl_command($remote_machine_name, $command_to_be_executed){
		return remote_function::remote_execution($remote_machine_name, FLUSHCTL_SCRIPT." localhost:".MEMBASE_PORT_NO." ".$command_to_be_executed." 2>&1");
	}

  // set min_data_age
	function Set_min_data_age($ServerName, $min_data_age){
		self::Run_flushctl_command($ServerName, " set min_data_age ".$min_data_age);
	}	

  // set queue_age_cap
	function Set_Queue_age_cap($ServerName, $queue_age_cap){
		self::Run_flushctl_command($ServerName, " set queue_age_cap ".$queue_age_cap);
	}
  
  // set maximum number of items in a flusher transaction
	function Set_max_txn_size($ServerName, $max_txn_size){
		self::Run_flushctl_command($ServerName, " set max_txn_size ".$max_txn_size);
	}
  
  // set max_size in bytes
	function Set_max_size($ServerName, $max_size){
		self::Run_flushctl_command($ServerName, " set max_size ".$max_size);
	}
  
  // set mem_high_wat in bytes
	function Set_mem_high_wat($ServerName, $mem_high_wat){
		self::Run_flushctl_command($ServerName, " set mem_high_wat ".$mem_high_wat);
	}
  
  // Set mem_low_wat in bytes
	function Set_mem_low_wat($ServerName, $mem_low_wat){
		self::Run_flushctl_command($ServerName, " set mem_low_wat ".$mem_low_wat);
	}
  
  // Set Expiry Pager Sleeptime
	function Set_exp_pager_stime($ServerName, $exp_pager_stime){
		self::Run_flushctl_command($ServerName, " set exp_pager_stime ".$exp_pager_stime);
	}
    	
	// Evict command
	function evictKeyFromMemory($ServerName, $keyname, $sleep_time_after_eviction = 0){
			// Attempt for 1s to evict the key, else report failure
		for($iattempt=0 ; $iattempt<3; $iattempt++){
			$evict_output = self::Run_flushctl_command($ServerName, " evict ".$keyname);
			if(stristr($evict_output, "error")){
				usleep(100);
				continue;	
			} else {
				usleep(500);
				sleep($sleep_time_after_eviction);
				return True;
			}
			/*
			$vkey_output = stats_functions::get_vkey_stats($ServerName, $keyname);
			if(stristr($vkey_output["key_valid"], "item_deleted")){ 
				sleep($sleep_time_after_eviction);
				return True;
			}
			else	
				usleep(100);
				*/
		}
		return False;
	}
	
	// Set bg_fetch_delay
	function Set_bg_fetch_delay($ServerName, $bg_fetch_delay){
		self::Run_flushctl_command($ServerName, " set bg_fetch_delay ".$bg_fetch_delay);
	}

	// Persistance command
	function Start_Stop_Persistance($ServerName, $State){
		self::Run_flushctl_command($ServerName, $State);
	}
}	

?>