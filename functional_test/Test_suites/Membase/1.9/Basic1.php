<?php

abstract class Basic_TestCase extends ZStore_TestCase {


	public function test_Pump_Keys()
	{
	global $moxi_machines;
	print_r($moxi_machines);
	}

	//Testcase to verify that vbucketmigrator gets respawned by VBA after getting killed
	public function test_Kill_Vbucketmigrator()
	{
		global $test_machine_list;

		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();

		vba_functions::kill_vbucketmigrator(0);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		$flag=True;
		
		foreach($vbucketmigrator_map as $key => $value)
		{
			if($key == 0)
			{
			$flag = False;
			break;
			}
		}
		
	

		$this->assertEquals($flag, True,"Vbucketmigrator not stopped");

		sleep(40);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		
		$flag=False;
		foreach($vbucketmigrator_map as $key => $value)
		{
			if($key ==0)
			{
			$flag = True;
			break;
			}
		}
				
		$this->assertEquals($flag, True, "Vbukcetmigrator not started");
	
	}
	
	public function test_Kill_All_Machine_Vbucketmigrator()
	{
		global $test_machine_list;
		global $secondary_machine_list;
		$machine=$test_machine_list[0];
		$secondary_machine=$secondary_machine_list[0];

		$vbucketmigrator_map1=vba_functions::get_cluster_vbucket_information();
		$var=vba_functions::get_vbuckets_from_server($machine);

		$vbu1=array();	
		foreach($vbucketmigrator_map1 as $key => $value)
		{	
			if($value['source'] === $machine || $value['source'] === $secondary_machine)
			{
			array_push($vbu1,$key);
			}
		}
		asort($vbu1);
 	
		$vbuckets=array_keys($var);

		$command_to_be_executed = "sudo killall vbucketmigrator";
        	remote_function::remote_execution($machine, $command_to_be_executed);
		sleep(40);
		$vbucketmigrator_map2=vba_functions::get_cluster_vbucket_information();
		$vbu2=array();
		foreach($vbucketmigrator_map2 as $key => $value)
        	{
                	if($value['source'] === $machine || $value['source'] === $secondary_machine)
                        {      
				array_push($vbu2,$key);
                        }
        	}
	        asort($vbu2);
		$diff =array_diff($vbu1,$vbu2);
		$this->assertEquals(empty($diff),true,"Vbucketmigrator not respawned");
	}


	function test_Bring_Down_Kvstore()
	{
		global $test_machine_list;
		$machine = $test_machine_list[1];
		$kvstore="kvstore1";
		$disk="data_1";
		$vb_kvstore_before=vba_functions::get_vbuckets_per_disk($machine,$disk);
		print_r($vb_kvstore_before);
		$vb_id=$vb_kvstore_before['active'][0];
		
		print_r($vb_kvstore_before['active']);

		$replica_vbucket_for_active=array();

		foreach($vb_kvstore_before['active'] as $vb_id)
		{
			$replica_vbucket_for_active[$vb_id]=vba_functions::get_machine_from_id_replica($vb_id);
			
		}

		print_r($replica_vbucket_for_active);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		print_r(array_keys($vbucketmigrator_map));
		vba_functions::mark_disk_down_active($vb_id);
		//pump data
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		sleep(180);
		$vb_kvstore_after=vba_functions::get_vbuckets_per_disk($machine,$disk);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		print_r(array_keys($vbucketmigrator_map));
		
		if(empty($vb_kvstore_after['replica']) and empty($vb_kvstore_after['active']))
			{
			$flag = true;
			log_function::debug_log( "All the vbuckets are marked down");
			
			}
		else
			{
			$flag=false;
			log_function::debug_log( "Vbuckets are not marked dead");
			}
		$this->assertEquals($flag,true,"Vbuckets are not marked dead");	
		//Verify that the replica vbuckets became active
		print_r($vbucketmigrator_map);
		
		//print_r($vbucketmigrator_map);	
		foreach($replica_vbucket_for_active as $vb_id=>$server)
		{	
			$secondary_server = general_function::get_secondary_ip($server);
				
			if (($vbucketmigrator_map[$vb_id]['source']==$server) or ($vbucketmigrator_map[$vb_id]['source']== $secondary_server))
			{
				$flag=true;
				log_function::debug_log( "Verified that the replica vbuckets became active");
			}
			else
			{
				$flag=false;
				log_function::debug_log( "Replica vbuckets didnt get activated");
			}
		
		}
		$this->assertEquals($flag,true,"Replica vbuckets didnt get activated");	
		Data_generation::add_keys_to_cluster(300,NULL,1);
	}
	
	function  test_Bring_Down_VBA()
	{

		global $test_machine_list;
		$vbucket_map_before=vbs_functions::get_vb_map();
		$machine=$test_machine_list[0];
		
		$vbucket_active_info=vba_functions::get_vbuckets_from_server($machine);
		$vbucket_replica_info=vba_functions::get_vbuckets_from_server($machine,'replica');
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		vba_functions::stop_vba($machine);
		sleep(120);
		$vbucket_map_after=vbs_functions::get_vb_map();
		$flag=true;
		unset($test_machine_list[0]);
		//Verify the replica vbuckets for  the active in the down membase became active or not 
	
		foreach($vbucket_active_info as $vb_id=>$value)
			{
			$falg = true;
			$secondary_ip = general_function::get_secondary_ip(vba_functions::get_machine_from_id_active($vb_id));
			$primary_ip = vba_functions::get_machine_from_id_active($vb_id);
			print_r($vbucket_map_before);
			echo $vbucket_map_before[$vb_id]['replica']."\n"; 
			echo $secondary_ip." ".$primary_ip; 
			if( $vbucket_map_before[$vb_id]['replica'] == $primary_ip or $vbucket_map_before[$vb_id]['replica'] == $secondary_ip )
				{ }
			else
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
	
	Data_generation::add_keys_to_cluster(300,NULL,1);

	}

	//Bring down membase and verify the vbuckets get redistributed properly	
	function test_Bring_Down_Membase()
	{
	
		global $test_machine_list;
	
		$vbucket_map_before=vbs_functions::get_vb_map();
		$machine=$test_machine_list[0];
		
		$vbucket_active_info=vba_functions::get_vbuckets_from_server($machine);
		$vbucket_replica_info=vba_functions::get_vbuckets_from_server($machine,'replica');
	
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
	
	Data_generation::add_keys_to_cluster(300,NULL,1);
	}	

	function test_Reshard_Up()
	{
		global $test_machine_list;
		global $spare_machine_list;
			
		$machine=$spare_machine_list[0];
		$vbucket_map_before=vbs_functions::get_vb_map();
		vbs_functions::add_server_to_cluster($machine);
		sleep(100);
		array_push($test_machine_list,$machine);
	
		//Get the active and replica vbuckets in the newly added server after vbucket redistribution
		$vbucket_active_info=vba_functions::get_vbuckets_from_server($machine);
		$vbucket_replica_info=vba_functions::get_vbuckets_from_server($machine,'replica');
	
		$flag=True;
	
		//Verfiy that the vbuckets assigned to  the new machine is marked dead in the old machine
		foreach($vbucket_active_info as $vb_id=>$value)
			{
				//log_function::debug_log( vba_functions::get_machine_from_id_dead($vb_id);	 
				$primary_ip = general_function::get_primary_ip($vbucket_map_before[$vb_id]['active']);
				$secondary_ip = general_function::get_secondary_ip($vbucket_map_before[$vb_id]['active']);
				$dead_ip=vba_functions::get_machine_from_id_dead($vb_id);
				
				if( $dead_ip != $primary_ip and $dead_ip != $secondary_ip)
				{
				log_function::debug_log( $vb_id." not marked dead in old machine ".$dead_ip." ".$primary_ip." ".$secondary_ip);
				$flag=False;
				}
			
			}
			
		$this->assertEquals($flag,True,"Reassigned active vbucket not marked dead in the old server");
		
		$flag=True;
	
		foreach($vbucket_replica_info as $vb_id=>$value)
			{
                                $primary_ip = general_function::get_primary_ip($vbucket_map_before[$vb_id]['replica']);
                                $secondary_ip = general_function::get_secondary_ip($vbucket_map_before[$vb_id]['replica']);
                                $dead_ip=vba_functions::get_machine_from_id_dead($vb_id);

                                if( $dead_ip != $primary_ip and $dead_ip != $secondary_ip )
                                {
				log_function::debug_log( $vb_id." not marked dead in old machine ".$dead_ip." ".$primary_ip." ".$secondary_ip);
                                $flag=False;
                                }

			}
		
		$this->assertEquals($flag,True,"Reassigned replica vbucket not marked dead in the old server");

		 $output=vba_functions::vbucket_sanity();

                $this->assertEquals($output,True,"Vbucket assignment is wrong");
		Data_generation::add_keys_to_cluster(300,NULL,1);
	
	}


	//Reshard down using api not working
	function test_Reshard_Down()
	{
		global $test_machine_list;
		global $spare_machine_list;
		
		$machine=$test_machine_list[0];
		$vbucket_map_before=vbs_functions::get_vb_map();

		$result=vbs_functions::remove_server_from_cluster($machine);
		#sleep(80);
		#$this->assertEquals($result,True,"VBS didnt return success for the API");
		unset($test_machine_list[0]);
		//Verify all the vbuckets in the removed server are marked dead

		$vbucket_active_info_after_failover=vba_functions::get_vbuckets_from_server($machine);
                $vbucket_replica_info_after_failover=vba_functions::get_vbuckets_from_server($machine,'replica');
		$vbucket_dead_info_after_failover = vba_functions::get_vbuckets_from_server($machine,'dead');
		
	
		$vbucket_map_after=vbs_functions::get_vb_map();
		
		$this->assertEquals(empty($vbucket_active_info),True,"Vbuckets not marked dead");
		$this->assertEquals(empty($vbucket_replica_info),True,"Vbuckets not marked dead");
		
		$primary_ip = $machine;
		$secondary_ip = general_function::get_secondary_ip($machine);
		
		$flag = True;
		//Verify that all the dead vbuckets got reassigned
			
		foreach($vbucket_map_before as $vb_id=>$value)
			{
				if($vbucket_map_before[$vb_id]['active'] == $primary_ip or $vbucket_map_before[$vb_id]['active'] == $secondary_ip)
					{
			
					if ( $vbucket_map_after[$vb_id]['active'] == $vbucket_map_before[$vb_id]['replica'] || $vbucket_map_after[$vb_id]['active'] == general_function::get_secondary_ip($vbucket_map_before[$vb_id]['replica']))
						{
						$flag=True;

						}
					else
						{
						$flag = False;
						log_function::debug_log( "Vbucket ".$vb_id." didnt become active\n" );
						break;
						}

					}
				if($vbucket_map_before[$vb_id]['replica'] == $primary_ip or $vbucket_map_before[$vb_id]['replica'] == $secondary_ip)
					{
					
					if(vba_functions::get_machine_from_id_replica($vb_id))
						{
						$flag = True;
						}
					else
						{
						$flag=False;
						log_function::debug_log( "Vbucket ".$vb_id." didnt get assigned\n");
						break;
						}
					}
			}
		$this->assertEquals($flag,True,"Vbuckets didnt get assigned to the existing servers properly");

                $output=vba_functions::vbucket_sanity();

                $this->assertEquals($output,True,"Vbucket assignment is wrong");	
	

	}


	//Break the persistent connection
	function test_Break_Persistent_Conn_Moxi_VBS()
	{
		global $moxi_machines;
		$machine = $moxi_machines[0];
		log_function::debug_log( $machine);
	}

	
	//Bring down multiple VBA(Not working)
	function test_Bring_Down_Multiple_VBA()
	{
		global $test_machine_list;
		$machine1=$test_machine_list[0];
		$machine2=$test_machine_list[1];
		Data_generation::add_keys_to_cluster(300,NULL,1);	

		vba_functions::stop_vba($machine1);
		sleep(100);
		vba_functions::stop_vba($machine2);
		sleep(100);
		$output=vba_functions::vbucket_sanity();
                $this->assertEquals($output,True,"Vbucket assignment is wrong");
		Data_generation::add_keys_to_cluster(300,NULL,1);	

	
	}

	function test_Vbucket_Sanity()
	{
		$output = vba_functions::vbucket_sanity();
		$this->assertEquals($output, True,"Vbuckets are not assigned in the cluster as per the map");
	}
	
	function test_Bring_Down_IP()
	{	
		global $test_machine_list;
		$machine = $test_machine_list[3];
		log_function::debug_log( "Bringing  down $machine");
		general_function::bring_down_ip($machine,"eth1");
		sleep(100);
		$output = vba_functions::vbucket_sanity();
		$this->assertEquals($output,True,"Vbuckets are not assigned in the cluster as per the map");
		general_function::bring_up_ip($machine,"eth1");
		Data_generation::add_keys_to_cluster(300,NULL,1);	
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

?>

