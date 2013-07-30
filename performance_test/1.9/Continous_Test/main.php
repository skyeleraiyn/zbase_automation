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
#Pump_class::pump();
function call_failover($function_name,$machine)
	{
	$failover=new Failover_class;
	$failover->$function_name($machine);
	}
include('config.php');
function main()
{
	include_files();
	$test=new Pump_class;
	$test->pump();
	//cluster_setup::setup_membase_cluster();
        $verify=new Verify_Class;	
	$failure_array=array('kill_membase','reshard_up','reshard_down','reshard_up');
	
	sleep(300);
	$original_vbucket_key_count_array=vba_functions::get_key_count_cluster_for_each_vbucket();

	$pid = pcntl_fork();
	if($pid == -1)
		die('could not fork');
	
	else if($pid)	
	{	//Parent
		echo "parent\n";
		while(1)
		{
		try{
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
	//Children		
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
			echo $machine;
			$spare_machine[0]=$machine;
			unset($test_machine_list[0]);
			$test_machine_list=array_values($test_machine_list);
		}

		else
		{	
			$machine=$spare_machine[0];
			array_push($test_machine_list,$machine);
		}

		print_r($test_machine_list);	
		echo "Calling failover for ".$failure_array[$i%4]." ".$machine."\n";
		call_failover($failure_array[$i%4],$machine);
		echo "Sleeping for 100 seconds\n";
		sleep(600);
		//verify	
		$verify->verify_all($original_vbucket_key_count_array); 
		$i++;

		sleep($timeperiod_failure);
	}	
	}
}

main();
?>
