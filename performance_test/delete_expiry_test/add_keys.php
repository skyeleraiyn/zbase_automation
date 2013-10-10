<?php

$script_path = dirname($_SERVER['SCRIPT_FILENAME']);
include_once $script_path."/config.php";

$blob_value  = array();
foreach(unserialize(BLOB_SIZE) as $size){
	$blob_value[] = generate_data($size);
}

$mc_master = new memcache();
$mc_master->addserver(MASTER_SERVER, 11211);
	
if($argv[1] == "delete"){
	$start_time = time();
	for($ikey = INSTALL_BASE ; $ikey > round(INSTALL_BASE * MAX_DELETE_EXPIRY) ; $ikey--){
		delete_key(TEST_KEY_PREFIX.$ikey);
	}
	$total_time = time() - $start_time;
	debug_log("Time taken to issue delete $total_time");
	$total_time = verify_keys_delete($mc_master);
	debug_log("Time taken to persist deleted keys $total_time");
} else {
// Add keys
	$expiry_time = $argv[1];
	$start_time = time();
	while(1){
		for($ikey = INSTALL_BASE ; $ikey > 0 ; $ikey--){
			if(rand(1,10) < round(MAX_DELETE_EXPIRY * 10)){
				set_key(TEST_KEY_PREFIX.$ikey, 0);
			} else {
				if(SESSION_TIME == 0){
					set_key(TEST_KEY_PREFIX.$ikey, $expiry_time);
				} else {
					set_key(TEST_KEY_PREFIX.$ikey, rand(5, $expiry_time));
				}
			}
		}
		if($expiry_time == 0){
			break;
		} else {
			// if SESSION_TIME is 0 break after single run, else wait until SESSION_TIME is complete
			if(SESSION_TIME == 0){
				break;
			} else {
				$total_time = time() - $start_time;
				if(SESSION_TIME < $total_time){
					break;
				} 
			}
		}	
	}
	$total_time = time() - $start_time;
	debug_log("Time taken to add keys $total_time");
	
		// Ignore persistance or wait for keys to expiry if SESSION_TIME is set
	if(SESSION_TIME == 0){
		while(1){
			$stats_output = $mc_master->getStats();
			$ep_total_persisted = $stats_output["ep_total_persisted"];
			if($ep_total_persisted >= INSTALL_BASE) 
				break;
			else
				sleep(2);
		}
		$total_time = time() - $start_time;
		debug_log("Time taken to persist keys $total_time");	
	
		$mc_master->set("client_set_failure", 0);
		$mc_master->set("client_get_miss", 0);
		
		if($expiry_time <> 0){
			$time = time() - $start_time;
			sleep($expiry_time - $time);
			flushctl_commands::set_flushctl_parameters($remote_machine, "exp_pager_stime", 60);
			$total_time = verify_keys_delete($mc_master);	
			debug_log("Time taken to persist expired keys $total_time");
		}
	}
	exit;
}

function delete_key($key_name){
	global $blob_value, $mc_master;
	
	while(!$mc_master->delete($key_name)){
		debug_log("delelte fail for :".$key_name);
		sleep(5);
	}
}

function set_key($key_name, $expiry_time){
	global $blob_value, $mc_master;
	
	while(!$mc_master->set($key_name, $blob_value[array_rand($blob_value)], 0, $expiry_time)){
		debug_log("set fail for :".$key_name);
		sleep(5);
	}
}

function verify_keys_delete($mc_master){
	$start_time = time();
	while(1){
		$stats_output = $mc_master->getStats();
		$ep_total_del_items = $stats_output["ep_total_del_items"];
		
		if($ep_total_del_items >= round(INSTALL_BASE * MAX_DELETE_EXPIRY) ) 
			break;
		else 
			sleep(1);
	}
	$total_time = time() - $start_time;
	return $total_time;	
}

function generate_data($object_size){
	$UserData = "GAME_ID_#@";
	while(1){
		if(strlen($UserData) >= $object_size) 
			break;
		else
			$UserData = $UserData.rand(11111, 99999);	
	}
	return serialize($UserData);
}

function debug_log($message_to_log){

	$filePointer = fopen(DEBUG_LOG, "a");
	fputs($filePointer,$message_to_log."\r\n");
	fclose($filePointer);	
	
}
?>
