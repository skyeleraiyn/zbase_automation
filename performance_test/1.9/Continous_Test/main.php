<?php

function include_files(){
	define('__ROOT__', dirname(__FILE__));
	foreach(glob(__ROOT__."/*.php")as $filename){
		if(!stristr($filename,__FILE__))
			require_once($filename);
		}

	foreach(glob(__ROOT__."/lib/*.php")as $filename){
		require_once($filename);
		}

	}	

//Function to do failover
function call_failover($function_name,$machine){
	$failover=new Failover_class;
	$failover->$function_name($machine);
	}

include('config.php');

function main(){

		include_files();
		$test=new Pump_class;
		$test->pump();
        	$verify=new Verify_Class;	
		$failure_array=array('kill_membase','reshard_up','reshard_down','reshard_up');
	
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
		
			if($failure_array[$i%4] == 'kill_membase' or $failure_array[$i%4] == 'reshard_down')
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

		log_function::debug_log("Calling failover for ".$failure_array[$i%4]." ".$machine);
		call_failover($failure_array[$i%4],$machine);
		log_function::debug_log("Sleeping for 100 seconds");
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
