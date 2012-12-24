<?php
class zruntime_functions{

	public function get_zruntime_object(){ 
		global $zruntime_object;
		$zruntime_object = new zRuntimeAPI(ZRUNTIME_USERNAME, ZRUNTIME_PASSWORD, GAME_ID, EVN);
	}		
	
	public function get_live_revision_number(){
		global $zruntime_object;
		if(!(is_object($zruntime_object))){
			self::get_zruntime_object();
		}	
		$existing_keys = $zruntime_object->zRTGetLive();
		log_function::debug_log(general_function::get_caller_function(NULL, NULL, 0));
		log_function::debug_log($existing_keys);
		return $existing_keys["rev"];		
	}

	public function update_key($key_value){
		global $zruntime_object;
		if(!(is_object($zruntime_object))){
			self::get_zruntime_object();
		}
		$live_revision = self::get_live_revision_number();
		$update_key = $zruntime_object->zRTUpdateKeys($live_revision, $key_value);
		log_function::debug_log(general_function::get_caller_function(NULL, NULL, 0));		
		log_function::debug_log($update_key);
		return $update_key;
	}

	public function add_key($key_value){
		global $zruntime_object;
		if(!(is_object($zruntime_object))){
			self::get_zruntime_object();
		}
		$live_revision = self::get_live_revision_number();
		$add_key = $zruntime_object->zRTAddKeys($live_revision, $key_value);
		log_function::debug_log(general_function::get_caller_function(NULL, NULL, 0));		
		log_function::debug_log($add_key);		
		return $add_key;
	}

	
	public function add_key_if_update_fails($key_value){
		$update_output = self::update_key($key_value);
		if($update_output <>  1){
			if(stristr($update_output["error"], "doesn't already exist")){
				$add_output = self::add_key($key_value);
				if($add_output <>  1)
					return $add_output;
				else
					return 1;
			} else {
				return $update_output;
			}
		}
		return 1;
	}
	
	public function populate_Active_MCS_ANR_details(){
				
			// Active MCS
		$key_value = array("ACTIVE_MCS" => ACTIVE_MCS_MACHINE);
		if(self::add_key_if_update_fails($key_value) <> 1)
			log_function::exit_log_message("unable to update in zruntime for ACTIVE_MCS");
			// ANR_MODE
		$key_value = array("ANR_MODE" => "ACTIVE");
		if(self::add_key_if_update_fails($key_value) <> 1)
			log_function::exit_log_message("unable to update in zruntime for ANR_MODE");		
	}

	
	public function populate_zRuntime_durable_pool(){
		
		// Populate MASTER_SLAVE_MAPPING
		foreach(unserialize(MASTER_SLAVE_MAPPING) as $master_server => $slave_server){
			$key_value = array($master_server => array(MASTER_MEMBASE_SERVER_1.':11211'));
			if(self::add_key_if_update_fails($key_value) <> 1)
				log_function::exit_log_message("unable to update in zruntime for ".$master_server);	
			$key_value = array($slave_server => array(SLAVE_MEMBASE_SERVER_1.':11211'));
			if(self::add_key_if_update_fails($key_value) <> 1)
				log_function::exit_log_message("unable to update in zruntime for ".$slave_server);			
			
			// cannot have more than one pair of master - slave
			break;
		}	
		$key_value = array("MB_DURABLE_va1_SPARE" => array(DURABLE_SPARE_SERVER_1.':11211', DURABLE_SPARE_SERVER_2.':11211'));
		if(self::add_key_if_update_fails($key_value) <> 1)
			log_function::exit_log_message("unable to update in zruntime for MB_DURABLE_va1_SPARE");		

		$key_value = array("MB_DURABLE_va2_SPARE" => array(DURABLE_SPARE_SERVER_1.':11211', DURABLE_SPARE_SERVER_2.':11211'));
		
                if(self::add_key_if_update_fails($key_value) <> 1)
                        log_function::exit_log_message("unable to update in zruntime for MB_DURABLE_va2_SPARE");



		// Empty VOLATILE_SPARE_MAPPING as the same IP's will be used for Durable pool
		foreach(unserialize(VOLATILE_SPARE_MAPPING) as $volatile_server => $swap_server){
			$key_value = array($volatile_server => array(""));
			if(self::add_key_if_update_fails($key_value) <> 1)
				log_function::exit_log_message("unable to update in zruntime for ".$master_server);
			$key_value = array("MB_VOLATILE_SPARE" => array(""));
			if(self::add_key_if_update_fails($key_value) <> 1)
				log_function::exit_log_message("unable to update in zruntime for MB_VOLATILE_SPARE");				
			
			
			// cannot have more than one pair of master - slave
			break;
		}			
		
	}


}	
?>	
