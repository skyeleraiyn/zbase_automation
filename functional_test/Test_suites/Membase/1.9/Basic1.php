<?php

abstract class Basic_TestCase extends ZStore_TestCase {




	//Testcase to verify that vbucketmigrator gets respawned by VBA after getting killed
	public function test_Kill_Vbucketmigrator()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		//Kills vbucketmigrator for vb_id 0
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
	

	//Testcase to verify that vbucketmigrator gets respawned by VBA after killing all the vbucketmigrators in a machine
	public function test_Kill_All_Machine_Vbucketmigrator()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine=$test_machine_list[0];
		$secondary_machine = general_function::get_secondary_ip($machine);
		$vbucketmigrator_map1=vba_functions::get_cluster_vbucket_information();
		$vbucketmigrator_before_kill=array();	
		foreach($vbucketmigrator_map1 as $key => $value)
		{	
			if($value['source'] === $machine || $value['source'] === $secondary_machine)
			{
				array_push($vbucketmigrator_before_kill,$key);
			}
		}
		asort($vbucketmigrator_before_kill);
		$command_to_be_executed = "sudo killall vbucketmigrator";
        	remote_function::remote_execution($machine, $command_to_be_executed);
		sleep(40);
		$vbucketmigrator_map2=vba_functions::get_cluster_vbucket_information();
		$vbucketmigrator_after_kill=array();
		foreach($vbucketmigrator_map2 as $key => $value)
        	{
                	if($value['source'] === $machine || $value['source'] === $secondary_machine)
                        {      
				array_push($vbucketmigrator_after_kill,$key);
                        }
        	}
	        asort($vbucketmigrator_after_kill);
		$diff =array_diff($vbucketmigrator_before_kill,$vbucketmigrator_after_kill);
		$this->assertEquals(empty($diff),true,"Vbucketmigrator not respawned");
	}


	//Testcase to verify that all vbucketmigrators in the cluster gets respawned after killing all the vbucketmigrators in the cluster
	public function test_Kill_All_Vbucketmigrator()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		foreach($test_machine_list as $machine)
		{ 
		$command_to_be_executed = "sudo killall vbucketmigrator";
		remote_function::remote_execution($machine, $command_to_be_executed);
		}
		sleep(60);
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");	
	}
	

	//Testcase to verify that the disk if it is marked down and again brought up is detected as a bad disk and failover done for that
	public function test_Behavior_Diskdown_Diskup()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine = $test_machine_list[1];
                $kvstore="kvstore1";
                $disk="data_1";
                $vb_kvstore_before=vba_functions::get_vbuckets_per_disk($machine,$disk);
		$vb_id=$vb_kvstore_before['active'][0];
                $replica_vbucket_for_active=array();
		Data_generation::add_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();	
                foreach($vb_kvstore_before['active'] as $vb_id)
                {
                        $replica_vbucket_for_active[$vb_id]=vba_functions::get_machine_from_id_replica($vb_id);

                }
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		vba_functions::mark_disk_down_active($vb_id);
		sleep(60);
		vba_functions::mark_disk_up_active($vb_id);
		//pump data
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		sleep(180);
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$vb_kvstore_after=vba_functions::get_vbuckets_per_disk($machine,$disk);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
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
		$this->assertEquals($flag,true,"Vbuckets are not marked dead and membase didnt recognise disk failure");	
		
		//Verify that the replica vbuckets became active
		
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
				log_function::debug_log( "Replica vbuckets didnt get activated for ".$vb_id." ".$server);
			}
		
		}
		$this->assertEquals($flag,true,"Replica vbuckets didnt get activated");	
		
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");
	}
	

	//Test to verify that the disk failure is detected and the failover done after that
	public function test_Bring_Down_Kvstore()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine = $test_machine_list[1];
		$kvstore="kvstore1";
		$disk="data_1";
		$vb_kvstore_before=vba_functions::get_vbuckets_per_disk($machine,$disk);
		$vb_id=$vb_kvstore_before['active'][0];
		$replica_vbucket_for_active=array();
	
                Data_generation::add_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		foreach($vb_kvstore_before['active'] as $vb_id)
		{
			$replica_vbucket_for_active[$vb_id]=vba_functions::get_machine_from_id_replica($vb_id);
			
		}

		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		vba_functions::mark_disk_down_active($vb_id);
		//pump data
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		sleep(180);
		$vb_kvstore_after=vba_functions::get_vbuckets_per_disk($machine,$disk);
		$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
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
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");

	}

	//Testcase to verify that the forward map is used during a reshard.	
	public function test_Forward_Map(){
	 global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
                global $spare_machine_list;
                global $moxi_machines;

                $machine=$spare_machine_list[0];
                Data_generation::pump_keys_to_cluster(3000,NULL,1);
                vbs_functions::add_server_to_cluster($machine);
                for($i=0;$i<300;$i++)
                { Data_generation::pump_keys_to_cluster(300,NULL,1);}

                $command_to_be_executed = "sudo cat /var/log/moxi.log |grep a2b_not_my_vbucket|grep \"sindex \"|sed 's/.*get,//'|sed 's/retries.*//'|sort|uniq|awk {'print $2,$4'}";

                $output=remote_function::remote_execution($moxi_machines[0], $command_to_be_executed);
                $vbucket_index=split("\n",$output);
                $vbucket_index_map=array();
                foreach($vbucket_index as $vbucket_index_separated)
                        {
                        $vbucket_index_array=split(" ",$vbucket_index_separated);
                        array_push($vbucket_index_map,$vbucket_index_array);
                        }
                $Falg=False;
                for($i=0;$i<count($vbucket_index_map)-1;$i++)
                        {
                        for($j=0;$j<count($vbucket_index_map)-1;$j++)
                                {
                                if($vbucket_index_map[$i][1]==$vbucket_index_map[$j][1])
                                {
                                $flag = True;
				break;
                                }
                                }
                        }
                $this->assertEquals($flag,True,"Forward map not used");

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
		sleep(120);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$vbucket_map_after=vbs_functions::get_vb_map();
		$flag=true;
		unset($test_machine_list[0]);

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

	//Testcase to do reshar up.
	public function test_Reshard_Up()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		global $spare_machine_list;
			
		$machine=$spare_machine_list[0];
		$vbucket_map_before=vbs_functions::get_vb_map();
		Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
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
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");
	}


	//Testcase to pump data while vbucketmigrator is killed and verify that key migration is working properly
	public function test_Kill_Vbucketmigrator_Pump_Keys(){
	 		
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$pid = pcntl_fork();
		if ($pid == -1) 
			{
     			die('could not fork');
			} 
			else if ($pid) 
			{
			for($i=0;$i<4;$i++)
			{
                	foreach($test_machine_list as $machine)
               	 		{
		                $command_to_be_executed = "sudo killall vbucketmigrator";
                		remote_function::remote_execution($machine, $command_to_be_executed);
                		sleep(20);
				}
	
			     pcntl_wait($status); //Protect against Zombie children
			}
			} else 
			{
			 Data_generation::add_keys_to_cluster(3000,NULL,1);
			}
		
		sleep(100);
		$flag=True;
		for($i=0;$i<NO_OF_VBUCKETS;$i++)
		{	
		if(vba_functions::get_keycount_from_vbucket($i,'active') == vba_functions::get_keycount_from_vbucket($i,'replica') )
			{
			$flag=True;
			}
		else
			{
			$flag=False;
			}

		}

		$this->assertEquals($flag,True,"Keys didnt get migrated");
		
	}	

	//Testcase to verify reshard down API
	public function test_Reshard_Down()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine=$test_machine_list[0];
		$vbucket_map_before=vbs_functions::get_vb_map();
		Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$result=vbs_functions::remove_server_from_cluster($machine);
		sleep(100);
		$this->assertEquals($result,1,"VBS didnt return success for the API");
		unset($test_machine_list[0]);
		
		//Verify all the vbuckets in the removed server are marked dead
		$result=vba_functions::verify_server_not_present_in_map($machine);
                $this->assertEquals($result,True,"The down machine is still present in the vbucket map");
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
						$secondary_server = general_function::get_secondary_ip($vbucket_map_before[$vb_id]['replica'];
					if ( $vbucket_map_after[$vb_id]['active'] == $vbucket_map_before[$vb_id]['replica'] || $vbucket_map_after[$vb_id]['active'] == $secondary_server )
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
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");
	}


	//Break the persistent connection and verify reestablishment
	public function test_Break_Persistent_Conn_Moxi_VBS()
	{
		global $moxi_machines;
		$machine = $moxi_machines[0];
		log_function::debug_log( "Breaking the connection from VBS to ".$machine);
		$command_to_be_executed = "sudo netstat -anp|grep moxi|grep 1400|awk {'print $4'}";
                $port_ip=remote_function::remote_execution($machine, $command_to_be_executed);
		$port=split(':',$port_ip);
		$port1=trim($port[1]);
		//command to block
		$command_to_be_executed="sudo /sbin/iptables -A OUTPUT -p tcp --dport $port1 -j DROP";
		
		remote_function::remote_execution($machine, $command_to_be_executed);
		sleep(100);
		$command_to_be_executed = "sudo netstat -anp|grep moxi|grep 1400|awk {'print $4'}";
                $port_ip=remote_function::remote_execution($machine, $command_to_be_executed);
                $port2=split(':',$port_ip);
		$this->assertNotEquals($port1,$port2,"New connection was not established");
		
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

	
	//Trestcase to verify membase volatile failure
	public function test_Membase_Failure_Volatile()
	{
		global $moxi_machine;
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine=$test_machine_list[0];
		$vbucket_active_info=vba_functions::get_vbuckets_from_server($machine);
		$vbucket_map_before=vbs_functions::get_vb_map();
		membase_setup::kill_membase_server($machine);
		Data_generation::add_keys_to_cluster(300,NULL,1);
		sleep(60);
		$vbucket_map_after=vbs_functions::get_vb_map();				
	
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
		$output=vba_functions::vbucket_sanity();
                $this->assertEquals($output,True,"Vbucket assignment is wrong");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");

		Data_generation::add_keys_to_cluster(300,NULL,1);	
	}

	

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

	//Both active and replica going down should cause VBS to tell a manual restore

	public function test_Bring_Down_Active_Replica()
	{	
		$vb_id=1;
		vba_functions::mark_disk_down_replica($vb_id);
		vba_functions::mark_disk_down_active($vb_id);
		Data_generation::add_keys_to_cluster(300,NULL,1);
		sleep(60);
		$machine=VBS_IP;
		$command_to_be_executed = "sudo cat /var/log/vbs.log|grep \"Need manual restore\"";
		$result = remote_function::remote_execution($machine, $command_to_be_executed);
		if($result)
			$result=True;
		else
			$result=False;
	
		$this->assertEquals($result,True,"VBS didnt recognise the failure of active and replica at the same time");
		
	}
	
	//Testcase to verify membase connectivity failure is detected or not	
	public function test_Break_Membase_Connectivity()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$machine = $test_machine_list[0];
		Data_generation::pump_keys_to_cluster(300,NULL,1);
		$vbucket_key_count_before_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$command_to_be_executed = "sudo /sbin/iptables -A INPUT -p tcp --dport 11211 -j DROP";
	        remote_function::remote_execution($machine, $command_to_be_executed);
	
		sleep(60);
		$result=vba_functions::verify_server_not_present_in_map($machine);
                $this->assertEquals($result,True,"The down machine is still present in the vbucket map");
		$output=vba_functions::vbucket_sanity();
	        $this->assertEquals($output,True,"Vbucket assignment is wrong");
		
		$output = vba_functions::vbucket_map_migrator_comparison();
                $this->assertEquals($output,True,"Vbucketmigrator distrubtion and vbucketmap are not consistent with each other");
		$vbucket_key_count_after_test=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($vbucket_key_count_before,$vbucket_key_count_after);
		$this->assertEquals($output,True,"Keycount mismatch for vbuckets");

	}
	
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

	//Testcase to verify the cluster out of capacity is detected by VBS or not.
	public function test_Cluster_Capacity()
	{
		global $test_machine_list;
		cluster_setup::setup_membase_cluster($vbuckets = NO_OF_VBUCKETS,$restart_spares = True);
		sleep(100);
		$no_of_machines = count($test_machine_list);
		$total_no_of_kvstore = $no_of_machines*MULTI_KV_STORE;
		$total_no_of_vbuckets = 2*NO_OF_VBUCKETS;
		$no_of_vbucket_per_kvstore = $total_no_of_vbuckets/$total_no_of_kvstore;
		$max_vbucket_capacity = floor($total_no_of_vbuckets*(CAPACITY/100))+$total_no_of_vbuckets;
		$max_no_of_vbucket_per_kvstore=floor($max_vbucket_capacity/$total_no_of_kvstore);
		
		//No of kvstore for out of capacity
		for ($i=0 ;$i <$total_no_of_kvstore ;$i++)
		{
			if($total_no_of_vbuckets-$max_no_of_vbucket_per_kvstore*$i <=0)
				break;

		}
		$no_of_kvstore_out_of_capacity = $i;
		$i=0;
		foreach($test_machine_list as $machine){
			for ($j=0;$j<=MULTI_KV_STORE;$j++)
			{
				if($i>=$no_of_kvstore_out_of_capacity)
					break;

				vba_functions::mark_disk_down('data_'.$j,$machine);
				$i++;
				
			}
		
		}	
		
		Data_generation::add_keys_to_cluster(300,NULL,1);	
		sleep(100);
		$machine=VBS_IP;
                $command_to_be_executed = "sudo cat /var/log/vbs.log|grep \"out of capacity\"";
                $result = remote_function::remote_execution($machine, $command_to_be_executed);
		$flag= False;
		if(strpos($result,"out of capacity"))
		{
			$flag=True
		}
                $this->assertEquals($flag,True,"Out of capacity not detected");	
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

