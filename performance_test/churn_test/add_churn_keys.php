<?php

$script_path = dirname($_SERVER['SCRIPT_FILENAME']);
include_once $script_path."/config.php";
//include_once $script_path."/Histogram.php";

define('TEST_KEY_PREFIX', "test_key_");
define('DEBUG_LOG', "/tmp/add_churn_debug.log");

if(file_exists(dirname(DEBUG_LOG))) unlink(DEBUG_LOG);
$blob_value = array();
foreach(unserialize(BLOB_SIZE) as $memory_size){
	$blob_value[] = generate_data($memory_size);
}
$blob_count = count($blob_value);

// Add keys
if($argv[1] == "add"){
	$mc_master = new memcache();
	$mc_master->addserver(MASTER_SERVER, 11211);
	
	$start_time_fill = time();
	for($ikey = INSTALL_BASE ; $ikey > 0 ; $ikey--){
		$mod_value = $ikey % $blob_count;
		$blob = $blob_value[$mod_value];
		while(!$mc_master->set(TEST_KEY_PREFIX.$ikey, $blob)){
			debug_log("set fail for :".TEST_KEY_PREFIX.$ikey);
			sleep(5);
		}
	}
	$total_time_fill = time() - $start_time_fill;
	debug_log("Time taken to add keys $total_time_fill");
	$mc_master->set("client_set_failure", 0);
	$mc_master->set("client_get_miss", 0);
	exit;
} else {

	$ip_address_list = explode(":", $argv[1]);
	// Churn keys	
	//$histos = array();
	$no_of_keys_per_session = round(ONE_DAY_DURATION / SESSION_TIME);
	$start_time_complete_execution = time();
	for($ithread=0 ; $ithread< TEST_EXECUTION_THREADS ; $ithread++){
		$pid = pcntl_fork();
		$pid_count = 0;
		if ($pid == 0){
			$no_of_days = 0;
			// MAU run
			while(True){
				for($ikey = 0 ; $ikey < MAU ; $ikey = $ikey + round(DAU * DAU_CHANGE) ){
					if($no_of_days > 30) break(2);								
					// DAU run
					dau_run($ikey, $no_of_keys_per_session);
					$no_of_days++;
					debug_log("completed ".$no_of_days." days for thread ".$ithread);
				}
					// reverse run
				for($ikey = MAU ; $ikey > 0 ; $ikey = $ikey - round(DAU * DAU_CHANGE) ){					
					if($no_of_days > 30) break(2);								
						// DAU run
					dau_run($ikey, $no_of_keys_per_session);
					$no_of_days++;
					debug_log("completed ".$no_of_days." days for thread ".$ithread);
				}
			}
			//	file_put_contents("/tmp/histos_file.log", serialize($histos));
			exit;
		} else {
			$pid_arr[$pid_count] = $pid;
			$pid_count++;
		}
	}
	foreach($pid_arr as $pid){	
		pcntl_waitpid($pid, $status);			
		if(pcntl_wexitstatus($status) == 4) exit;
	}
	$total_time_complete_execution = time() - $start_time_complete_execution;
	debug_log("Time taken to churn keys $total_time_complete_execution");	
}


function dau_run($ikey, $no_of_keys_per_session){
	$start_time_one_day = time();
	while((time() - $start_time_one_day) < ONE_DAY_DURATION ){
		// session run
		for($jkey = $ikey ; $jkey < $ikey + DAU ; $jkey = $jkey + $no_of_keys_per_session){
			$start_time_session = time();
			while((time() - $start_time_session) < SESSION_TIME){
				run_api($jkey, $no_of_keys_per_session);
			}
		}
	}
}

function run_api($jkey, $no_of_keys_per_session){
	global $ip_address_list, $mc_master_thread;
	
	$mc_master_thread = new memcache();
	foreach($ip_address_list as $ip_address){
		$mc_master_thread->addserver($ip_address, 11211);
	}
		// Set + Get API 96% of the time get the keys from memory	
	$ops_iteration = rand(1,100);
	if($ops_iteration < 96){	
		$icount = array();
		for($iarr=0 ; $iarr<10 ; $iarr++){
			$icount[] = rand($jkey, $jkey + $no_of_keys_per_session);
		}
		$background_fetch = True;
	} else {
		$icount = array();
		for($iarr=0 ; $iarr<5 ; $iarr++){
			$icount[] = rand(MAU, INSTALL_BASE);
		}	
		$background_fetch = False;
	}
	for($iops=0 ; $iops<40 ; $iops++){
		if(rand(0, SET_GET_ratio + 1) == SET_GET_ratio){
			set_key(TEST_KEY_PREFIX.$icount[array_rand($icount)]);
		} else {
			get_key(TEST_KEY_PREFIX.$icount[array_rand($icount)], $background_fetch);
		}
	}
		// 15% of times run other API's
	if($ops_iteration > 99){
		run_other_ops();
	}
	$mc_master_thread->close();

}


function set_key($keyname){
	global $blob_value, $blob_count, $mc_master_thread;
	
	$ikey = trim(str_replace(TEST_KEY_PREFIX, "", $keyname));
	$mod_value = $ikey % $blob_count;
	$blob = $blob_value[$mod_value];
	
//	$start_time = microtime(TRUE);
	if(!$mc_master_thread->set($keyname, $blob)){
		$mc_master_thread->increment("client_set_failure", 1);
	}
/*	$total_time = microtime(TRUE) - $start_time; 
	addToHisto("set_latency", $total_time);
    addToHisto("total_ops_latency", $total_time);
	*/
}

function get_key($keyname, $key_from_disk){
	global $mc_master_thread;
	
//	$start_time = microtime(TRUE);
	$get_output = $mc_master_thread->get($keyname);
	if($get_output == False){
		$mc_master_thread->increment("client_get_miss", 1);
	} 
/*	$total_time = microtime(TRUE) - $start_time; 
	
	addToHisto("get_latency", $total_time);
    addToHisto("total_ops_latency", $total_time);
    if($key_from_disk) addToHisto("bg_fetch_latency", $total_time);
*/		
}


function run_other_ops(){
	global $mc_master_thread;
		// Add + Replace + + Append + Prepend + Delete
	$add_value = generate_data(1024);
	for($i=0 ; $i<100 ; $i++){
		$mc_master_thread->add("test_key_add_$i", $add_value);
	}
	$replace_value = generate_data(2048);
	for($i=0 ; $i<10 ; $i++){
		$mc_master_thread->replace("test_key_add_$i", $replace_value);
	}
	for($i=10 ; $i<20 ; $i++){
		for($j=0 ; $j<10 ; $j++){
			$mc_master_thread->append("test_key_add_$i", $add_value);
			$mc_master_thread->prepend("test_key_add_$i", $add_value);
		}
	}	
	for($i=0 ; $i<100 ; $i++){
		$mc_master_thread->delete("test_key_add_$i");
	}	

		// Set with expiry + Getl + Unlock
	for($i=0 ; $i<100 ; $i++){
		$mc_master_thread->set("test_key_with_expiry_$i", $add_value, 0, 5);
	}		
	for($i=0 ; $i<50 ; $i++){
		$mc_master_thread->getl("test_key_with_expiry_$i");
	}	
	for($i=0 ; $i<25 ; $i++){
		$mc_master_thread->unlock("test_key_with_expiry_$i");
	}	
	for($i=0 ; $i<50 ; $i++){
		$mc_master_thread->set("test_key_with_expiry_$i", $add_value, 0, 5);
	}	

		// Set + Increment + Decrement
	for($i=0 ; $i<10 ; $i++){
		$mc_master_thread->set("test_key_incr_decr_$i", 0);
	}
	for($i=0 ; $i<10 ; $i++){
		$mc_master_thread->increment("test_key_incr_decr_$i", rand(0, 5));
		$mc_master_thread->decrement("test_key_incr_decr_$i", rand(0, 5));
	}	
	
}

/*
function addToHisto($name, $v){
    global $histos;
    $name = "global_" . $name;
    if (!isset($histos[$name])) {
        $histos[$name] = new Histogram(ExponentialGenerator::generate(0, 4294967295, 2));
    }
    $histos[$name]->add($v * 1000000);
}
*/
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