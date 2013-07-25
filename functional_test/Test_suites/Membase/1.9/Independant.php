<?php

abstract class Basic_TestCase extends ZStore_TestCase {
	//Testcase to verify VBA connectivity failure is detected or not
	public function test_Break_VBA_Connectivity()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine=$test_machine_list[0];
		Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$command_to_be_executed = "sudo /sbin/iptables -A OUTPUT -p tcp --dport 14000 -j DROP";
		remote_function::remote_execution($machine, $command_to_be_executed);
		unset($test_machine_list[0]);
		$result=vba_functions::verify_server_not_present_in_map($machine);
                $this->assertEquals($result,True,"The down machine is still present in the vbucket map");
		sleep(100);
		
		$output=vba_functions::vbucket_sanity();
		$this->assertEquals($output,True,"Vbucket assignment is wrong");
		Data_generation::add_keys_to_cluster(300,NULL,1);
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");

	}

//Bring down multiple VBA ie one after another after vbucket rearrangement
	public function test_Bring_Down_Multiple_VBA()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine1=$test_machine_list[0];
		$machine2=$test_machine_list[1];
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		vba_functions::stop_vba($machine1);
		sleep(100);
		vba_functions::stop_vba($machine2);
		sleep(100);
		unset($test_machine_list[0]);
		unset($test_machine_list[1]);
		$output=vba_functions::vbucket_sanity();
                $this->assertEquals($output,True,"Vbucket assignment is wrong");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");

		Data_generation::add_keys_to_cluster(300,NULL,1);	
	}

	
//Verify that bring down one IP will remove the whole machine from cluster.
	public function test_Bring_Down_IP()
	{	
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine = $test_machine_list[0];
		Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		log_function::debug_log( "Bringing  down $machine");
		general_function::bring_down_ip($machine,"eth1");
		unset($test_machine_list[0]);
		$result=vba_functions::verify_server_not_present_in_map($machine);
                $this->assertEquals($result,True,"The down machine is still present in the vbucket map");
		sleep(100);
		
		$output = vba_functions::vbucket_sanity();
		$this->assertEquals($output,True,"Vbuckets are not assigned in the cluster as per the map");
		general_function::bring_up_ip($machine,"eth1");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
	}

	//Testcase to bring down VBA and verify that it is removed from the cluster
	public function  test_Bring_Down_VBA()
	{

		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$vbucket_map_before=vbs_functions::get_vb_map();
		$machine=$test_machine_list[0];
                Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$vbucket_active_info=vba_functions::get_vbuckets_from_server($machine);
		$vbucket_replica_info=vba_functions::get_vbuckets_from_server($machine,'replica');
		vba_functions::stop_vba($machine);
		unset($test_machine_list[0]);
		sleep(120);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$vbucket_map_after=vbs_functions::get_vb_map();
		$flag=true;

		$result=vba_functions::verify_server_not_present_in_map($machine);
		$this->assertEquals($result,True,"The down machine is still present in the vbucket map");

		//Verify the replica vbuckets for  the active in the down membase became active or not 
	
		foreach($vbucket_active_info as $vb_id=>$value)
			{
				$falg = true;
				$secondary_ip = general_function::get_secondary_ip(vba_functions::get_machine_from_id_active($vb_id));
				$primary_ip = vba_functions::get_machine_from_id_active($vb_id);
				
				
			if( !($vbucket_map_before[$vb_id]['replica'] == $primary_ip or $vbucket_map_before[$vb_id]['replica'] == $secondary_ip ))
				{
				$flag = false;
				log_function::debug_log( "Replica vbucket $vb_id didnt get activated");
				break;
				}
			}
		$this->assertEquals($flag,true,"Replica vbuckets didnt became active after failover");
		$flag=true;
		foreach($vbucket_replica_info as $vb_id=>$value)
			{
			log_function::debug_log( $vbucket_map_after[$vb_id]['replica']);
			if($vbucket_map_after[$vb_id]['replica']=='NIL'){
				 $flag =false;
				 log_function::debug_log( "Vbuckets didnt get respawned");
				}
				
			}
		$this->assertEquals($flag,true,"Replica vbuckets in the failed machine didnt get reassigned");
		$output=vba_functions::vbucket_sanity();
		$this->assertEquals($output,True,"Vbucket assignment is wrong");	
	        $output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");

	}

//Bring down membase and verify the vbuckets get redistributed properly	
	public function test_Bring_Down_Membase()
	{
	
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$vbucket_map_before=vbs_functions::get_vb_map();
		$machine=$test_machine_list[0];
		$vbucket_active_info=vba_functions::get_vbuckets_from_server($machine);
		$vbucket_replica_info=vba_functions::get_vbuckets_from_server($machine,'replica');
	        Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		membase_setup::kill_membase_server($machine);
		sleep(120);
		unset($test_machine_list[0]);
		$vbucket_map_after=vbs_functions::get_vb_map();
		$flag=true;
	
		//Verify the replica vbuckets for  the active in the down membase became active or not 
	
		foreach($vbucket_active_info as $vb_id=>$value)
			{
			$secondary_ip = general_function::get_secondary_ip(vba_functions::get_machine_from_id_active($vb_id));
			$primary_ip = vba_functions::get_machine_from_id_active($vb_id);  
			if( $vbucket_map_before[$vb_id]['replica'] == $primary_ip or $vbucket_map_before[$vb_id]['replica'] == $secondary_ip )
				$flag=true;
			else
				{
				$flag = false;
				log_function::debug_log( "Replica vbuckets didnt get activated");
				}
			}
		$this->assertEquals($flag,true,"Replica vbuckets didnt became active after failover");
		
		$flag=true;
		foreach($vbucket_replica_info as $vb_id=>$value)
			{
			log_function::debug_log( $vbucket_map_after[$vb_id]['replica']);
			if($vbucket_map_after[$vb_id]['replica']=='NIL')
				{ $flag =false; log_function::debug_log( "Vbuckets didnt get respawned");}
				
			}
		$this->assertEquals($flag,true,"Replica vbuckets in the failed machine didnt get reassigned");
		$output=vba_functions::vbucket_sanity();
		$this->assertEquals($output,True,"Vbucket assignment is wrong");	
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");
	}


	//Testcase verify cluster behaviour while a server dies during reshard up	
	public function test_Bring_Down_Server_While_Reshard_Up()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		global $spare_machine_list;
			
		$machine=$spare_machine_list[0];
		$vbucket_map_before=vbs_functions::get_vb_map();
		vbs_functions::add_server_to_cluster($machine);
		array_push($test_machine_list,$machine);
		$machine=$test_machine_list[0];
                membase_setup::kill_membase_server($machine);
                sleep(120);
                unset($test_machine_list[0]);
		$output=vba_functions::vbucket_sanity();
                $this->assertEquals($output,True,"Vbucket assignment is wrong");
                $output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");	
	}


}

class Basic_TestCase_Full  extends Basic_TestCase {

        public function keyProvider() {
                return Data_generation::provideKeys();
        }

        public function keyValueProvider() {
                return Data_generation::provideKeyValues();
        }

        public function keyValueFlagsProvider() {
                return Data_generation::provideKeyValueFlags();
        }
}

