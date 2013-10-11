/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php

$options = getopt("s:d:t:o:g:h");

if (isset($options["h"]))
{
usage();
}
	
$key_lower_limit = 1000000000;
$key_upper_limit = 1005000000;

if (isset($options["s"]))
    $source_server_name = $options["s"];
else
    usage();
if (isset($options["d"]))
    $destination_server_name_list = explode(":", $options["d"]);
else
    usage();
if (isset($options["g"])) GenerateData();
		
if (isset($options["t"]))
    $threads = $options["t"];
else
    usage();	
if (isset($options["o"]))
    $operations_per_Thread = $options["o"];
else
    usage();
	
	$mcmux_process = trim(shell_exec('/sbin/pidof mcmux'), "\n");
	if(is_numeric($mcmux_process)){
		ini_set('memcache.proxy_enabled', 1);
		ini_set('memcache.proxy_host', 'unix:///var/run/mcmux/mcmux.sock');
	}
	
	for($ithread=0 ; $ithread< $threads ; $ithread++){
		$pid = pcntl_fork();
		if ($pid == 0){
			$mc_destination = new memcache();
			foreach ($destination_server_name_list as $destination_server_name){
				$mc_destination->addserver($destination_server_name , 11211);
			}
			$mc_destination->setproperty("EnableChecksum", true);
			$mc_source = new memcache();
			$mc_source->addserver($source_server_name , 11211);	
			$mc_source->setproperty("EnableChecksum", true);	
			for($iOperations=0; $iOperations< $operations_per_Thread ; $iOperations++){
				$get_set_ratio = rand(1,5);
				$iUser = rand($key_lower_limit, $key_upper_limit);
				$UserID = "EMPIRES_&_ALLIES_USER_$iUser";
				if($get_set_ratio == 1){
					$input_element = array("Coins", "Dollars", "Levels", "Experience", "Combats", "Units", "Campaigns", "Airstrikes", "Resources", "Neighbours");
					$element_to_modify = array_rand($input_element);
					
					$source_data = $mc_source->getl($UserID);
					$destination_data = $mc_destination->getl($UserID);
					// if object is already locked skip the iteration
					if ((!$source_data) || (!$destination_data) ) continue;
					$source_data = unserialize($source_data);
					$destination_data = unserialize($destination_data);
					if ($source_data <> $destination_data){
						echo "Data corruption found for $UserID \n";	
					} else{
						$source_data[$input_element[$element_to_modify]] = $source_data[$input_element[$element_to_modify]] + rand(1, 10000);
						$source_data = serialize($source_data);
						if(!$mc_source->set($UserID, $source_data, MEMCACHE_COMPRESSED_LZO)) echo "Failed re-setting key on $source_server_name for $UserID \n";
						if(!$mc_destination->set($UserID, $source_data, MEMCACHE_COMPRESSED_LZO)) echo "Failed re-setting key on $destination_server_name for $UserID \n";;
					}	
				} else{
					$source_get_value = $mc_source->get2($UserID, $value);
					if(!($source_get_value)){
						echo "Get miss for $UserID \n";
					}					
				}
			}	
			$mc_source->close();
			$mc_destination->close();
		exit;	
		}
	}	

function GenerateData(){
	global $key_lower_limit, $key_upper_limit, $source_server_name, $destination_server_name_list;

	$main_pid_arr = array();
	$main_pid_count = 0;
	$main_pid = pcntl_fork();
	if ($main_pid == 0){		
		$mc_source = new memcache();
		$mc_source->addserver($source_server_name , 11211);
		$mc_source->setproperty("EnableChecksum", true);
		for ($iUser = $key_lower_limit ; $iUser < $key_upper_limit + 1 ; $iUser++){
			$UserID = "EMPIRES_&_ALLIES_USER_$iUser";
			$UserData = array(
						"ID" => $UserID,
						"Coins" => $iUser,
						"Dollars" => $iUser,
						"Levels" => $iUser,
						"Experience" => $iUser,
						"Combats" => $iUser,
						"Units" => $iUser,
						"Campaigns" => $iUser,
						"Airstrikes" => $iUser,
						"Resources" => $iUser,
						"Neighbours" => $iUser,
						);
			$UserData = serialize($UserData);	
			$mc_source->set($UserID, $UserData, MEMCACHE_COMPRESSED_LZO);
		}
		$mc_source->close();
		exit;
	}else{
		$main_pid_arr[$main_pid_count] = $main_pid;
		$main_pid_count = $main_pid_count + 1;
	}

	$main_pid = pcntl_fork();
	if ($main_pid == 0){		
		$mc_destination = new memcache();
		foreach ($destination_server_name_list as $destination_server_name){
			$mc_destination->addserver($destination_server_name , 11211);
		}
		$mc_destination->setproperty("EnableChecksum", true);
		for ($iUser = $key_lower_limit ; $iUser < $key_upper_limit + 1 ; $iUser++){
			$UserID = "EMPIRES_&_ALLIES_USER_$iUser";
			$UserData = array(
						"ID" => $UserID,
						"Coins" => $iUser,
						"Dollars" => $iUser,
						"Levels" => $iUser,
						"Experience" => $iUser,
						"Combats" => $iUser,
						"Units" => $iUser,
						"Campaigns" => $iUser,
						"Airstrikes" => $iUser,
						"Resources" => $iUser,
						"Neighbours" => $iUser,
						);
			$UserData = serialize($UserData);	
			$mc_destination->set($UserID, $UserData, MEMCACHE_COMPRESSED_LZO);
		}
		exit;
	}else{
		$main_pid_arr[$main_pid_count] = $main_pid;
		$main_pid_count = $main_pid_count + 1;
	}	
		
		
	while(count($main_pid_arr) > 0){
		$myId = pcntl_waitpid(-1, $status, WNOHANG);
		foreach($main_pid_arr as $key => $pid)
		{
			if($myId == $pid) unset($main_pid_arr[$key]);	
		}
		sleep(1);
	}
	echo "Adding data to source and destination complete \n";
	exit;
}

	
function usage(){
	$file = $_SERVER["SCRIPT_NAME"];
	$break = Explode('/', $file);
	$pfile = $break[count($break) - 1];

	echo $pfile; 
    echo "\n Usage: $pfile -s source_server -d destination_server1:destination_server2:... -t threads -o Operations_per_Thread \n";
	exit;
}

?>
