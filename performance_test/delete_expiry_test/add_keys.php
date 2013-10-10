<?php

$script_path = dirname($_SERVER['SCRIPT_FILENAME']);
include_once $script_path."/config.php";

define('TEST_KEY_PREFIX', "test_key_");
define('DEBUG_LOG', "/tmp/delete_debug.log");

if(file_exists(dirname(DEBUG_LOG))) unlink(DEBUG_LOG);
$blob_value = generate_data(BLOB_SIZE);
$mc_master = new memcache();
$mc_master->addserver(MASTER_SERVER, 11211);
	
if($argv[1] == "delete"){
	$start_time = time();
	for($ikey = INSTALL_BASE ; $ikey > INSTALL_BASE / 2 ; $ikey--){
		$mc_master->delete(TEST_KEY_PREFIX.$ikey);
	}
	$total_time = time() - $start_time;
	debug_log("Time taken to issue delete $total_time");
	$total_time = verify_keys_delete($mc_master);
	debug_log("Time taken to persist expired keys $total_time");
} else {
// Add keys
	$expiry_time = $argv[1];
	$start_time = time();
	for($ikey = INSTALL_BASE ; $ikey > 0 ; $ikey--){
		if(rand(1,10) < 8){
			while(!$mc_master->set(TEST_KEY_PREFIX.$ikey, $blob_value, 0, 0)){
				debug_log("set fail for :".TEST_KEY_PREFIX.$ikey);
				sleep(5);
			}
		} else {
			while(!$mc_master->set(TEST_KEY_PREFIX.$ikey, $blob_value, 0, $expiry_time)){
				debug_log("set fail for :".TEST_KEY_PREFIX.$ikey);
				sleep(5);
			}
		}
	}
	$total_time = time() - $start_time;
	debug_log("Time taken to add keys $total_time");
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
	exit;
}

function verify_keys_delete($mc_master){
	$start_time = time();
	while(1){
		$stats_output = $mc_master->getStats();
		$ep_total_del_items = $stats_output["ep_total_del_items"];
		
		if($ep_total_del_items >= INSTALL_BASE / 2 ) 
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
