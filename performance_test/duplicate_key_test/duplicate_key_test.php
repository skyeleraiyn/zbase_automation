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
/*
	Usage: php duplicate_key_test.php
	This script will:
		set keys with valid and invalid expiry value
		set and delete few keys. 
*/


	$instance = new memcache();
	$instance->addserver("localhost", 11211);
	$pid_arr = array();
	$time_now = time();
	for($ithread=0; $ithread<40 ; $ithread++){
		$pid = pcntl_fork();
		if ($pid == 0){
			while(1){
				for($ikey=0 ; $ikey<1000000 ; $ikey++){
					@$instance->set("test_key_negative_expiry_$ikey", "testvalue", 0, $time_now);	
					@$instance->set("test_key_without_expiry_$ikey", "testvalue");					
					@$instance->set("test_key_small_expiry_$ikey", "testvalue", 0, rand(1, 2));		
					@$instance->set("test_key_large_expiry_$ikey", "testvalue", 0, rand(5, 10));	
					@$instance->set("test_key_same_key_mutation", "testvalue", 0);
				}
			}				
			exit;
		} else{
			$pid_arr[] = $pid;
		}
	}
	for($ithread=0; $ithread<15 ; $ithread++){
		$pid = pcntl_fork();
		if ($pid == 0){
			while(1){
				$ikey = rand(1, 10000);
				@$instance->set("test_key_delete_$ikey", "testvalue");
				usleep(rand(0,1000));
				@$instance->delete("test_key_delete_$ikey");
			}
			exit;
		} else {
			$pid_arr[] = $pid;
		}
	}
	for($ithread=0; $ithread<15 ; $ithread++){
		$pid = pcntl_fork();
		if ($pid == 0){
			while(1){
				for($ikey=0 ; $ikey<1000000 ; $ikey++){
					@$instance->set("test_key_delete_$ikey", "testvalue", rand(0, 120));
				}
			}
			exit;
		} else {
			$pid_arr[] = $pid;
		}
	}
	for($ithread=0; $ithread<15 ; $ithread++){
		$pid = pcntl_fork();
		if ($pid == 0){
			while(1){
				for($ikey=0 ; $ikey<1000000 ; $ikey++){
					@$instance->set("test_key_delete_$ikey", "testvalue");
				}
			}
			exit;
		} else {
			$pid_arr[] = $pid;
		}
	}		
	foreach($pid_arr as $pid){	
		pcntl_waitpid($pid, $status);			
		usleep(100);
	}
		
?>
