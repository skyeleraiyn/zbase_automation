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

abstract class Basic_TestCase extends ZStore_TestCase {

        public function test_Tester()   {
	/*
                $test_machine_list = array("netops-dgm-ibr-test-2-chef-production-dm.ca2.zynga.com",
                        "netops-dgm-ibr-test-3-chef-production-dm-spare.ca2.zynga.com",
                        "netops-dgm-ibr-test-11.ca2.zynga.com",
                        "netops-dgm-ibr-test-12.ca2.zynga.com");
	*/
		#$ml = array("10.36.200.32", " 10.36.194.50");
		#print count(vba_functions::get_vbuckets_from_cluster($test_machine_list, "replica"));	
		#print_r(vba_functions::get_keycount_from_vbucket( "vb_8", "replica"));
		#print vba_functions::get_keycount_from_cluster("active");
		#print_r(vba_functions::get_server_vbucket_information("10.36.194.50"));
		#print_r(vba_functions::get_cluster_vbucket_information());
		#vba_functions::kill_vbucketmigrator(8);
		#vba_functions::get_vb_map();
		#cluster_setup::setup_zbase_cluster();
		#vbs_functions::remove_server_from_cluster("10.36.166.52");
		#sleep(20);
		#vbs_functions::get_vb_map();
		#print "Active - ".count(vba_functions::get_vbuckets_from_cluster("active"))."\n";
                #print "Replica - ".count(vba_functions::get_vbuckets_from_cluster("replica"))."\n";
                #print "Dead - ".count(vba_functions::get_vbuckets_from_cluster("dead"))."\n";
		#foreach(vba_functions::get_cluster_vbucket_information() as $key => $value)	{
		#	print "$key\n";
		#}
		#vbs_setup::populate_and_copy_config_file($test_machine_list);
                #zbase_setup::clear_cluster_zbase_database($test_machine_list);
                #zbase_setup::restart_zbase_cluster($test_machine_list);
                #update_vbs_config(VBS_IP);
		#$config = moxi_functions::get_moxi_stats("netops-dgm-ibr-test-1-chef-production-dm.ca2.zynga.com");
		#print $config['vbsagent']['config']['config_received'];
		#print_r(vba_functions::get_vbuckets_in_kvstore("10.80.0.161", "kvstore 0", "active"));
		
        }

        #This test case sets up the zbase cluster with the servers specified in the config and verifies the following - 
	#	that the number of vbuckets created are same as that specified in the config
	#	that the number of vbucketmigrators started are the same as that expected in the config (equal to NO_OF_VBUCKETS)
	#	that for each vbucket the active and the replica exist on 2 different servers
        public function test_Basic_Cluster_Setup()
        {
		global $test_machine_list;
		global $moxi_machines;
		cluster_setup::setup_zbase_cluster();
		sleep(90);
		#Assert the number of active, replica and dead vbuckets in the cluster.
		$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("active")), NO_OF_VBUCKETS, "The number of vbuckets in cluster does not match NO_OF_VBUCKETS param in config");
		$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("replica")), NO_OF_REPLICAS*NO_OF_VBUCKETS, "The number of vbuckets in cluster does not match the expected number of replicas in config");
		$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("dead")), 0, "The number of dead vbuckets is seen to be greater than 0");
		
		#################################################################
		#Assert the number of vbuckets that are started across the cluster.
		#################################################################
		$vbucketmigrator_info = vba_functions::get_cluster_vbucket_information();
		$this->assertEquals(count($vbucketmigrator_info), NO_OF_VBUCKETS, "The number of vbuckets started across the cluster is not the same as the number of vbuckets initially configured");
		#################################################################
		#Asserting from the vbucketmigrator info that the source and destination are on different servers for each vbucketmigrator and therefore for each vbucket..
		#################################################################
		print_r($vbucketmigrator_info);
		foreach($vbucketmigrator_info as $vb_id => $data)	
			$this->assertNotEquals($data['source'], $data['dest'], "For $vb_id, both source and destination IPs are the same");
		#################################################################
		#Asserting that the vbuckets are distributed equally across the all nodes
		#################################################################
		$active_array = array();
		$replica_array = array();
		foreach($test_machine_list as $id=>$server)	{
			$active_array[$id] = count(vba_functions::get_vbuckets_from_server($server, "active"));
			$replica_array[$id] = count(vba_functions::get_vbuckets_from_server($server, "replica"));
		}

		#################################################################
		#Asserting for 3 since the deviation of distribution is expected to be a maximum of 1 for most basic cases
		#################################################################
		$this->assertGreaterThan(count(array_unique($active_array)), 3, "Imbalanced distribution of active vbuckets");
		$this->assertGreaterThan(count(array_unique($replica_array)), 3, "Imbalanced distribution of replica vbuckets");
		foreach($moxi_machines as $id=>$moxi)	{
			$config = moxi_functions::get_moxi_stats($moxi, "proxy");
			$this->assertEquals($config['vbsagent']['config']['config_received'], 1, "Config not received by the moxi on $moxi");
		}
		#################################################################
		#Verify that each kvstore has an almost equal number of vbuckets
		#################################################################
		$optimal_count = floor(NO_OF_VBUCKETS/(count($test_machine_list)*MULTI_KV_STORE));
		$complete_array = array();
		foreach($test_machine_list as $id=>$machine)	{
			for($kvstore=0;$kvstore<3;$kvstore++)	{
				$flag = 0;
				$count_in_kvstore = count(vba_functions::get_vbuckets_in_kvstore($machine, $kvstore, "active"));
				#Here we assume an tolerance of 10% for this test case with 64 vbuckets.
				if((floor($count_in_kvstore*1.1) >= $optimal_count && floor($count_in_kvstore*0.9) <= $optimal_count))	$flag = 1;
				$this->assertEquals(1, $flag, "Mismatched distribution of vbuckets beyond optimal count ($optimal_count) for $machine and kvstore $kvstore");
			}
		}
		#################################################################
		#Verify that vbucketmigrators are distributed equally across both interfaces (eth0 and eth1)
		#################################################################
		$vbucketmigrator_info = vba_functions::get_cluster_vbucket_information();
		$interface1 = 0;
		$interface2 = 0;
		$flag = 0;
		foreach($vbucketmigrator_info as $vb_id=>$details)	{
			if($details['interface'] == "eth0")	$interface1 += 1;
			else if($details['interface'] == "eth1")	$interface2 += 1;
		}
		if(abs($interface1 - $interface2) == 0 || abs($interface1 - $interface2) == 1)	$flag = 1;
		$this->assertEquals(1, $flag, "Vbucketmigrators not divided between the interfaces. Interface 0 - $interface1, Interface 1 - $interface2");
      }

      public function test_Basic_Cluster_With_Varying_No_Of_Vbuckets()	{
		global $test_machine_list;
		$no_of_vbuckets = array(32, 64, 128, 1024, 2048, 4096);
		foreach($no_of_vbuckets as $key=>$vbuckets)	{
			cluster_setup::setup_zbase_cluster($vbuckets);
			sleep(90);
        	        #Assert the number of active, replica and dead vbuckets in the cluster.
        	        $this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("active")), $vbuckets, "The number of vbuckets in cluster does not match NO_OF_VBUCKETS param in config");
	                $this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("replica")), NO_OF_REPLICAS*$vbuckets, "The number of vbuckets in cluster does not match the expected number of replicas in config");
                	$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("dead")), 0, "The number of dead vbuckets is seen to be greater than 0");
		}
      }
	#################################################################
        #Testing that the chk_max_items is honoured and checkpoints are indeed closed once this item limit is hit
        #################################################################
	public function test_Chk_Max_Items_Is_Honoured()	{
		global $test_machine_list;
                global $moxi_machines;
		$chk_max_items = 100;
                cluster_setup::setup_zbase_cluster();
                sleep(90);
		#Setting chk_max_items to 100 across all nodes in the server
		foreach($test_machine_list as $id=>$machine)	{
			flushctl_commands::set_flushctl_parameters($machine, "chk_max_items", $chk_max_items);
			$return_array = stats_functions::get_stats_array($machine);
			$this->assertEquals(100, $return_array['ep_checkpoint_max_items'], "chk_max_items value set is not reflected on the server");
		}
		Data_generation::pump_keys_to_cluster(170*NO_OF_VBUCKETS, $chk_max_items, 1);
		foreach($test_machine_list as $id=>$machine)    {
			$checkpoint_array = stats_functions::get_stats_array($machine, "checkpoint");
        	       	foreach($checkpoint_array as $vb_id=>$info)     {
				if($info['state'] == 'active')	{
		               	       $this->assertEquals(1, (int) $info['last_closed_checkpoint_id'], "Checkpoint hasnt been closed after pumping in keys worth $chk_max_items for vb_id $vb_id in machine $machine");
				}
			}
		}		
      }
        #################################################################
        #Testing that the chk_period is honoured and checkpoints are indeed closed once this time limit is hit
        #################################################################
        public function test_Chk_Period_Is_Honoured()        {
                global $test_machine_list;
                global $moxi_machines;
		$chk_period = 60;
		$chk_max_items = 1000;
                cluster_setup::setup_zbase_cluster();
                sleep(90);
                #Setting chk_max_items to 100 across all nodes in the server
                foreach($test_machine_list as $id=>$machine)    {
                        flushctl_commands::set_flushctl_parameters($machine, "chk_period", $chk_period);
			flushctl_commands::set_flushctl_parameters($machine, "chk_max_items", $chk_max_items);
                        $return_array = stats_functions::get_stats_array($machine);
                        $this->assertEquals(60, $return_array['ep_checkpoint_period'], "chk_period value set is not reflected on the server");
                }
                Data_generation::pump_keys_to_cluster(50*NO_OF_VBUCKETS, $chk_max_items, 1);
		sleep(90);
                foreach($test_machine_list as $id=>$machine)    {
                        $checkpoint_array = stats_functions::get_stats_array($machine, "checkpoint");
                        foreach($checkpoint_array as $vb_id=>$info)     {
                                if($info['state'] == 'active')  {
                                       $this->assertEquals(1, (int) $info['last_closed_checkpoint_id'], "Checkpoint hasnt been closed after waiting for time worth $chk_period for $vb_id in machine $machine");
                                }
                        }
                }
        }

	public function test_Key_Distribution()	{
		global $test_machine_list;
                global $moxi_machines;	
		$total_count = 0;
	        cluster_setup::setup_zbase_cluster();
                sleep(90);
		#Pump in some keys
		#$this->assertTrue(Data_generation::pump_keys_to_cluster(150*NO_OF_VBUCKETS, 100, 1), "Unable to pump in keys");
		Data_generation::pump_keys_to_cluster(150*NO_OF_VBUCKETS, 100, 1);
                #################################################################
                #Testing that the key count in each vbucket and also across the cluster is the same as that pumped into the active vbuckets.
                #################################################################
		#Allowing for a tolerance of 10%
		$optimal_count = 150;
		for($vb=0;$vb<NO_OF_VBUCKETS;$vb++)	{
			$flag = 0;
			$count_of_keys_in_vb = vba_functions::get_keycount_from_vbucket($vb, "active");
			if(floor($count_of_keys_in_vb[$vb]*1.1) > $optimal_count && floor($count_of_keys_in_vb[$vb]*0.9) < $optimal_count)	{
				$flag = 1;
			}
			$this->assertEquals($flag , 1, "Distribution of key on vbucket $vb is skewed for active vbuckets. Expected - $optimal_count, Actual - $count_of_keys_in_vb[$vb]");
			$total_count += $count_of_keys_in_vb[$vb];
		}
		$this->assertEquals($total_count, 150*NO_OF_VBUCKETS, "Keys pumped in do not match the total count in active vbuckets");
		#################################################################
                #Testing that the key count in each vbucket and across the cluster is the same as that pumped into the replica vbuckets.
                #################################################################
		$total_count = 0;
                for($vb=0;$vb<NO_OF_VBUCKETS;$vb++)     {
			$flag = 0;
                        $count_of_keys_in_vb = vba_functions::get_keycount_from_vbucket($vb, "replica");
			if(floor($count_of_keys_in_vb[$vb]*1.1) > $optimal_count && floor($count_of_keys_in_vb[$vb]*0.9) < $optimal_count)      {
                                $flag = 1;
                        }
                        $this->assertEquals($flag , 1, "Distribution of key on vbucket $vb is skewed for replica vbuckets. Expected - $optimal_count, Actual - $count_of_keys_in_vb[$vb]");
                        $total_count += $count_of_keys_in_vb[$vb];
                }
                $this->assertEquals($total_count, 150*NO_OF_VBUCKETS, "Keys pumped in do not match the total count in replica  vbuckets");
                #################################################################
                #Testing that the key count across each node in the cluster is approximately the same for active vbuckets.
                #################################################################
		#Allowing for a tolerance of 10%
		$optimal_count = floor((150*NO_OF_VBUCKETS)/count($test_machine_list));
		foreach($test_machine_list as $id=>$machine)	{
			$count_in_server = vba_functions::get_keycount_from_zbase($machine, "active");
			$flag = 0; 
			if(floor($count_in_server*1.1) > $optimal_count && floor($count_in_server*0.9) < $optimal_count)	{
				$flag = 1;
			}
			$this->assertEquals($flag, 1, "Distribution of key on $machine is skewed for active vbuckets. Expected - $optimal_count, Actual - $count_in_server");
		}
		
		#################################################################
                #Testing that the key count across each node in the cluster is approximately the same for replica vbuckets.
                #################################################################
                #Allowing for a tolerance of 10%
                $optimal_count = floor((150*NO_OF_VBUCKETS)/count($test_machine_list));
                foreach($test_machine_list as $id=>$machine)    {
                        $count_in_server = vba_functions::get_keycount_from_zbase($machine, "replica");
                        $flag = 0;
                        if(floor($count_in_server*1.1) > $optimal_count && floor($count_in_server*0.9) < $optimal_count)        {
                                $flag = 1;
                        }
                        $this->assertEquals($flag, 1, "Distribution of keys on $machine is skewed for replica vbuckets. Expected - $optimal_count, Actual - $count_in_server");
                }
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

