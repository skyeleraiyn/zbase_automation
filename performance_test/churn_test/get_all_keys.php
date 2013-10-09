<?php

$mc = new memcache();
$mc->addserver("localhost",11211);

while(1){
	$stats = $mc->getstats();
	$total_keys = $stats["curr_items"];
	
	$pid_arr = array();
	$no_of_threads = 10;
	for($ithread=0 ; $ithread< $no_of_threads ; $ithread++){
		$pid = pcntl_fork();
		if($pid == 0){
			for($ikey = $total_keys ; $ikey > 0 ; $ikey = $ikey - $no_of_threads){				
				$mc->get("test_key_".rand(1,$total_keys));	// Random fetch
				$mc->get("test_key_$ikey"); 	// Serial fetch
			}
			exit;
		} else {
			$pid_arr[] = $pid;
		}
		$total_keys--;
	}

	foreach($pid_arr as $pid){	
		pcntl_waitpid($pid, $status);		
	}	
}
?>
