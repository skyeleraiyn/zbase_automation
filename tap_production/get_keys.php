<?php

$file_list = array(
		"/dev/shm/front-mb-object-a-001.log",
		"/dev/shm/front-mb-object-a-002.log",
		"/dev/shm/front-mb-object-a-003.log",
		"/dev/shm/front-mb-object-a-004.log"
		);


while(1){
	$filearray = array();
	foreach ($file_list as $filename){			
		$filearray[] = fopen($filename,"r") or exit("Unable to open file!");
	}
	$pid_arr = array();
	$no_of_threads = 10;
	
	foreach($filearray as $file){
		for($ithread=0 ; $ithread< $no_of_threads ; $ithread++){
			$pid = pcntl_fork();
			if($pid == 0){
				$mc = new memcache();
				$mc->addserver("localhost", 11211);
				while(!feof($file)) {
					$command_key = trim(fgets($file));
					if($command_key == "") continue;
					$command_key = explode(" ", $command_key);
					if(count($command_key)<2) continue;
					
					$command = str_replace("..", "", $command_key[1]);
					if($command_key[0] == "get"){
						@$mc->get($command);
					} 
					if($command_key[0] == "getl"){
						@$mc->getl($command);
					} 	
				}
				$mc->close();
				exit;
			} else {
				$pid_arr[] = $pid;
			}
		}
	}

	foreach($pid_arr as $pid){	
		pcntl_waitpid($pid, $status);		
	}

	foreach($filearray as $file){	
		fclose($file);
	}
}


?>
