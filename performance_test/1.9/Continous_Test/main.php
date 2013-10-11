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

function include_files(){
	
	include('config.php');
	define('__ROOT__',"../../../");
	
	require_once(__ROOT__."common/common.php");
	
	foreach(glob(dirname(__FILE__)."/*.php") as $filename)
		{
		require_once($filename);
		}
	}	

//Function to do failover
function call_failover($function_name,$machine){
	$failover=new Failover_class;
	$failover->$function_name($machine);
	}


function main(){

		include_files();
		
		log_function::result_log("INFO Calling failover for ");
		$test=new Pump_class;
		$test->pump();
        	$verify=new Verify_Class;	
		$failure_array=array('kill_zbase','reshard_up','reshard_down','reshard_up');
	
		sleep(300);
		$original_vbucket_key_count_array=vba_functions::get_key_count_cluster_for_each_vbucket();

		$pid = pcntl_fork();
		//Forking for Failover and pump
		if($pid == -1)
			die('could not fork');
	
		else if($pid)	
		{	
		//Parent
			echo "parent\n";
			while(1)
			{
				try
				{
				 $test->pump();
		    		}
				catch(Exception $e)
				{
				echo 'Message: ' .$e->getMessage();
				continue;
  				}
			}
		pcntl_wait($status);	
		}
		else
		{
		//Child		
		$init_time=time();
		global $timeperiod_failure;
		global $test_machine_list;
		global $spare_machine;
		$i=0;
		while(1)
		{	
		
			if($failure_array[$i%4] == 'kill_zbase' or $failure_array[$i%4] == 'reshard_down')
			{
				$machine=$test_machine_list[0];
				$spare_machine[0]=$machine;
				unset($test_machine_list[0]);
				$test_machine_list=array_values($test_machine_list);
			}

			else
			{	
				$machine=$spare_machine[0];
				array_push($test_machine_list,$machine);
			}

		log_function::result_log("INFO Calling failover for ".$failure_array[$i%4]." ".$machine);
		call_failover($failure_array[$i%4],$machine);
		log_function::result_log("INFO Sleeping for 600 seconds");
		sleep(600);
		//verify keycount matches	
		$verify->verify_all($original_vbucket_key_count_array); 
		$i++;

		sleep($timeperiod_failure);
		}	
	}
}

main();
?>
