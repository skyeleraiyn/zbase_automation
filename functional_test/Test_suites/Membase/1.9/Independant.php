<?php

abstract class Basic_TestCase extends ZStore_TestCase {


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

