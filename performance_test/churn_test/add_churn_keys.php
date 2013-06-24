<?php

$script_path = dirname($_SERVER['SCRIPT_FILENAME']);
include_once $script_path."/config.php";
//include_once $script_path."/Histogram.php";

define('TEST_KEY_PREFIX', "test_key_");
define('DEBUG_LOG', "/tmp/add_churn_debug.log");

if(file_exists(dirname(DEBUG_LOG))) unlink(DEBUG_LOG);

// Add keys
if($argv[1] == "add"){

	$blob_value_1 = array();

	$blob_size_list = unserialize(BLOB_SIZE);
	$i  = 0;
	foreach($blob_size_list as $percentage => $blob_size_array){
		foreach($blob_size_array as $memory_size){
			$blob_value_1[$i][] = generate_data($memory_size);
		}
		$i++;
	}

	$mc_master = new memcache();
	$mc_master->addserver(MASTER_SERVER, 11211);
	$mc_master->setproperty("EnableChecksum", ENABLE_CHECKSUM);
	
	$start_time_fill = time();
	$pid_arr = array();
	$no_of_threads = 1;
	$start_key_index = INSTALL_BASE;
	for($ithread=0 ; $ithread< $no_of_threads ; $ithread++){
		$pid = pcntl_fork();
		if($pid == 0){
			for($ikey = $start_key_index ; $ikey > 0 ; $ikey = $ikey - $no_of_threads){			
				$blob_number = mt_rand(1,100);
				foreach($blob_distribution_bracket as $key => $value){
					if($blob_number <= $value){
						$blob_value = $blob_value_1[$key];
						break;
					}
			
				}
				$blob_count = count($blob_value);
				$mod_value = $ikey % $blob_count;
				$blob = $blob_value[$mod_value];
				while(!$mc_master->set(TEST_KEY_PREFIX.$ikey, $blob)){
					debug_log("set fail for :".TEST_KEY_PREFIX.$ikey);
					sleep(5);
				}
			}
			exit;
		} else {
			$pid_arr[] = $pid;
		}
		$start_key_index--;
	}

	foreach($pid_arr as $pid){	
		pcntl_waitpid($pid, $status);		
	}	
	$total_time_fill = time() - $start_time_fill;
	debug_log("Time taken to add keys $total_time_fill");
	$mc_master->set("client_set_failure", 0);
	$mc_master->set("client_get_miss", 0);
	exit;
} else {

	// churn keys

		// For each blob size, create five sets of blobs varying by -40%, -20%, 0%, 20%, 40% of the original size 
		// These will be switched in the session run under run_api function
	$blob_value_1 = $blob_value_2 = $blob_value_3 = $blob_value_4 = $blob_value_5 = array();

	$blob_size_list = unserialize(BLOB_SIZE);
	$i  = 0;
	foreach($blob_size_list as $percentage => $blob_size_array){
		foreach($blob_size_array as $memory_size){
			$mem_increment = $memory_size * 0.2;
			$blob_value_1[$i][] = generate_data($memory_size);
			$temp_size = $memory_size - $mem_increment;
			$blob_value_2[$i][] = generate_data($temp_size);
			$temp_size = $temp_size - $mem_increment;
			$blob_value_3[$i][] = generate_data($temp_size);
			$temp_size = $memory_size + $mem_increment;
			$blob_value_4[$i][] = generate_data($temp_size);
			$temp_size = $temp_size + $mem_increment;
			$blob_value_5[$i][] = generate_data($temp_size);	
		}
		$i++;
	}

	// For other ops
	$add_value = generate_data(1024);


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
					if($no_of_days > NUMBER_OF_DAYS) break(2);								
					// DAU run
					dau_run($ikey, $no_of_keys_per_session);
					$no_of_days++;
					debug_log("completed ".$no_of_days." days for thread ".$ithread);
				}
					// reverse run
				for($ikey = MAU ; $ikey > 0 ; $ikey = $ikey - round(DAU * DAU_CHANGE) ){					
					if($no_of_days > NUMBER_OF_DAYS) break(2);								
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
		sleep(1);
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
	global $ip_address_list, $mc_master_thread, $blob_distribution_bracket, $blob_count;
	global $blob_value, $blob_value_1, $blob_value_2, $blob_value_3,  $blob_value_4,  $blob_value_5;
	
	$blob_number = mt_rand(1,100);
	foreach($blob_distribution_bracket as $key => $value){
		if($blob_number <= $value){
			$blob_number = $key;
			break;
		}
	}
					
	switch(mt_rand(1, 5)){
		case 1:
		$blob_value = $blob_value_1[$blob_number];
		break;
		case 2:
		$blob_value = $blob_value_2[$blob_number];
		break;
		case 3:
		$blob_value = $blob_value_3[$blob_number];
		break;
		case 4:
		$blob_value = $blob_value_4[$blob_number];
		break;
		case 5:
		$blob_value = $blob_value_5[$blob_number];
		break;		
	}
	$blob_count = count($blob_value);
	$mc_master_thread = new memcache();
	foreach($ip_address_list as $ip_address){
		$mc_master_thread->addserver($ip_address, 11211);
	}
	$mc_master_thread->setproperty("EnableChecksum", ENABLE_CHECKSUM);
	
		// Set + Get API 96% of the time get the keys from memory	
	$ops_iteration = mt_rand(1,100);
	if($ops_iteration < FETCH_FROM_MEMORY){	
		$icount = array();
		for($iarr=0 ; $iarr<10 ; $iarr++){
			$icount[] = mt_rand($jkey, $jkey + $no_of_keys_per_session);
		}
		$background_fetch = True;
	} else {
		$icount = array();
		for($iarr=0 ; $iarr<5 ; $iarr++){
			$icount[] = mt_rand(MAU, INSTALL_BASE);
		}	
		$background_fetch = False;
	}
	for($iops=0 ; $iops<40 ; $iops++){
		if(mt_rand(0, SET_GET_ratio + 1) == SET_GET_ratio){
			set_key(TEST_KEY_PREFIX.$icount[array_rand($icount)]);
		} else {
			get_key(TEST_KEY_PREFIX.$icount[array_rand($icount)], $background_fetch);
		}
	}
		// small % run other API's
	$other_ops = mt_rand(1,100000);	
	if($other_ops > 99990){
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
	global $ip_address_list, $add_value;
	
	$mc_master_thread = new memcache();
	$mc_master_thread_2 = new memcache();
	$mc_master_thread_3 = new memcache();
	foreach($ip_address_list as $ip_address){
		$mc_master_thread->addserver($ip_address, 11211);
		$mc_master_thread_2->addserver($ip_address, 11211);
		$mc_master_thread_3->addserver($ip_address, 11211);
	}
	$mc_master_thread->setproperty("EnableChecksum", ENABLE_CHECKSUM);
	$mc_master_thread_2->setproperty("EnableChecksum", ENABLE_CHECKSUM);
	$mc_master_thread_3->setproperty("EnableChecksum", ENABLE_CHECKSUM);
	
	
	$replace_value = $add_value.$add_value;
	// Add + Replace + CAS + Delete
	$pid = pcntl_fork();
	if ($pid == 0){
		$start_key = mt_rand(1,10000);
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread->add("test_key_add_$i", $add_value);
		}
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread_2->replace("test_key_add_$i", $replace_value);
		}

		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread->get("test_key_add_$i", $returnFlags, $returnCAS);
			$mc_master_thread_2->cas("test_key_add_$i", $add_value, $returnFlags, 0, $returnCAS);
			$mc_master_thread_3->cas("test_key_add_$i", $replace_value, $returnFlags, 0, $returnCAS);
		}		
		
		sleep(2);
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread_3->delete("test_key_add_$i");
		}
		exit;
	} else {
		$pid_arr[] = $pid;
	}
	
		/* Check if blob exists. 
			If blob exists then check the value.
				If blob is above 1MB reset to 2k.
				Else append / prepend the blob with 2k value
			Else create a blob of 2k.
		*/
	$pid = pcntl_fork();
	if ($pid == 0){
		$start_key = mt_rand(1,10000);
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$blob_value = $mc_master_thread->get("test_key_append_$i");
			if($blob_value){
				$blob_size = strlen($blob_value);
				if($blob_size > 1048576){
					$mc_master_thread->set("test_key_append_$i", $add_value);
				} else {
					$mc_master_thread_2->append("test_key_append_$i", $add_value);
					$mc_master_thread_3->prepend("test_key_append_$i", $add_value);
				}
			} else {
				$mc_master_thread->set("test_key_append_$i", $add_value);
			}
		}		
		exit;
	} else {
		$pid_arr[] = $pid;
	}	
			// Getl + Unlock
		$pid = pcntl_fork();
	if ($pid == 0){
		$start_key = mt_rand(1,10000);
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread->set("test_key_with_lock_$i", $add_value);
		}		
		for($i=$start_key ; $i<$start_key+50 ; $i++){
			$mc_master_thread->getl("test_key_with_lock_$i");
		}	
		for($i=$start_key ; $i<$start_key+50 ; $i++){
			$ikey = mt_rand(0, 1000000);
			$mc_master_thread->unlock("test_key_with_lock_$ikey");
		}
		for($i=$start_key ; $i<$start_key+50 ; $i++){
			$ikey = mt_rand(0, 1000000);
			$mc_master_thread_2->getl("test_key_with_lock_$ikey");
		}				
		for($i=$start_key ; $i<$start_key+50 ; $i++){
			$ikey = mt_rand(0, 1000000);
			$mc_master_thread->set("test_key_with_lock_$ikey", $add_value);
		}
		for($i=$start_key ; $i<$start_key+50 ; $i++){
			$ikey = mt_rand(0, 1000000);
			$mc_master_thread_3->getl("tests_key_with_lock_$ikey");
		}		
		exit;
	} else {
		$pid_arr[] = $pid;
	}
		
			// Expiry + Increment + Decrement	
	$pid = pcntl_fork();
	if ($pid == 0){
		$expiry_value = $add_value;
		for($i=0 ; $i<mt_rand(2, 10) ; $i++){
			$expiry_value = $expiry_value.$expiry_value;
		}
		$start_key = mt_rand(1,10000);
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread->set("test_key_with_expiry_$i", $expiry_value, 0, mt_rand(0, 200));
		}
		
			// Increment and decrement
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread->set("test_key_incr_decr_$i", 0);
		}
		for($i=$start_key ; $i<$start_key+100 ; $i++){
			$mc_master_thread_2->increment("test_key_incr_decr_$i", mt_rand(50, 50000000));
			$mc_master_thread_3->decrement("test_key_incr_decr_$i", mt_rand(50, 50000000));
		}		
		exit;
	} else {
		$pid_arr[] = $pid;
	}
	
	foreach($pid_arr as $pid){	
		pcntl_waitpid($pid, $status);			
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
			$UserData = $UserData.mt_rand(11111, 99999);	
	}
	return serialize($UserData);
}

function debug_log($message_to_log){

	$filePointer = fopen(DEBUG_LOG, "a");
	fputs($filePointer,$message_to_log."\r\n");
	fclose($filePointer);	
	
}
?>
