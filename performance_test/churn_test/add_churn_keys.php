<?php

$script_path = dirname($_SERVER['SCRIPT_FILENAME']);
include_once $script_path."/config.php";
//include_once $script_path."/Histogram.php";

define('TEST_KEY_PREFIX', "test_key_");
define('DEBUG_LOG', "/tmp/add_churn_debug.log");

if(file_exists(dirname(DEBUG_LOG))) unlink(DEBUG_LOG);
$blob_value = generate_data(BLOB_SIZE);

// Add keys
if($argv[1] == "add"){
	$mc_master = new memcache();
	$mc_master->addserver(MASTER_SERVER, 11211);
	$stats_output = $mc_master->getStats();
	$max_memory = $stats_output["ep_max_data_size"];

	for($ikey = INSTALL_BASE ; $ikey > 0 ; $ikey--){
		if(!($ikey % 1000)){
			$stats_output = $mc_master->getStats();
				// If mem_used crosses 85% of max_memory, slow down pumping rate
			$mem_used = $stats_output["mem_used"];
			if($mem_used > $max_memory * 0.85){
				debug_log("memory crossed 85% mem_used:".$mem_used." max_memory:".$max_memory);
				sleep(5);
			}	
		}	
		while(!$mc_master->set(TEST_KEY_PREFIX.$ikey, $blob_value)){
			debug_log("set fail for :".TEST_KEY_PREFIX.$ikey);
			sleep(10);
		}
	}
	$mc_master->set("client_set_failure", 0);
	$mc_master->set("client_get_miss", 0);
	exit;
} else {

	$ip_address_list = explode(":", $argv[1]);
	// Churn keys	
	$histos = array();
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
					$start_time = time();
					while((time() - $start_time) < ONE_DAY_DURATION ){
						$mc_master_thread = new memcache();
						foreach($ip_address_list as $ip_address){
							$mc_master_thread->addserver($ip_address, 11211);
						}
						if(rand(1,100) < 96){	// 96% of the time get the keys from memory	
							$icount = array();
							for($iarr=0 ; $iarr<3 ; $iarr++){
								$icount[] = rand($ikey, $ikey + DAU);
							}
							for($iops=0 ; $iops<12 ; $iops++){
								if(rand(0, SET_GET_ratio) == SET_GET_ratio){
									set_key(TEST_KEY_PREFIX.$icount[array_rand($icount)]);
								} else {
									get_key(TEST_KEY_PREFIX.$icount[array_rand($icount)], False);
								}
							}			
						} else {
							$icount = array();
							for($iarr=0 ; $iarr<3 ; $iarr++){
								$icount[] = rand($ikey + MAU, INSTALL_BASE);
							}
							for($iops=0 ; $iops<12 ; $iops++){
								if(rand(0, SET_GET_ratio) == SET_GET_ratio){
									set_key(TEST_KEY_PREFIX.$icount[array_rand($icount)]);
								} else {
									get_key(TEST_KEY_PREFIX.$icount[array_rand($icount)], True);
								}
							}							
						}
						$mc_master_thread->close();	
					}
					$no_of_days++;
					debug_log("completed ".$no_of_days." days for thread ".$ithread);
				}
				for($ikey = MAU ; $ikey > 0 ; $ikey = $ikey - round(DAU * DAU_CHANGE) ){
					if($no_of_days > 30) exit;
					// DAU reverse run
					$start_time = time();
					while((time() - $start_time) < ONE_DAY_DURATION ){
						$mc_master_thread = new memcache();
						foreach($ip_address_list as $ip_address){
							$mc_master_thread->addserver($ip_address, 11211);
						}
						if(rand(1,100) < 96){	// 96% of the time get the keys from memory
							$icount = array();
							for($iarr=0 ; $iarr<3 ; $iarr++){
								$icount[] = rand($ikey, $ikey + DAU);
							}
							for($iops=0 ; $iops<12 ; $iops++){
								if(rand(0, SET_GET_ratio) == SET_GET_ratio){
									set_key(TEST_KEY_PREFIX.$icount[array_rand($icount)]);                									
								} else {
									get_key(TEST_KEY_PREFIX.$icount[array_rand($icount)], False);
								}
							}
						} else {
							$icount = array();
							for($iarr=0 ; $iarr<3 ; $iarr++){
								$icount[] = rand($ikey + MAU, INSTALL_BASE);
							}
							for($iops=0 ; $iops<12 ; $iops++){
								if(rand(0, SET_GET_ratio) == SET_GET_ratio){
									set_key(TEST_KEY_PREFIX.$icount[array_rand($icount)]);
								} else {
									get_key(TEST_KEY_PREFIX.$icount[array_rand($icount)], True);
								}
							}						
						}
						$mc_master_thread->close();
					}
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
}


function set_key($keyname){
	global $blob_value, $mc_master_thread;
	
//	$start_time = microtime(TRUE);
	if(!$mc_master_thread->set($keyname, $blob_value)){
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

function addToHisto($name, $v){
    global $histos;
    $name = "global_" . $name;
    if (!isset($histos[$name])) {
        $histos[$name] = new Histogram(ExponentialGenerator::generate(0, 4294967295, 2));
    }
    $histos[$name]->add($v * 1000000);
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