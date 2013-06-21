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
		#cluster_setup::setup_membase_cluster();
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
                #membase_setup::clear_cluster_membase_database($test_machine_list);
                #membase_setup::restart_membase_cluster($test_machine_list);
                #update_vbs_config(VBS_IP);
		$config = moxi_functions::get_moxi_stats("netops-dgm-ibr-test-1-chef-production-dm.ca2.zynga.com");
		print $config['vbsagent']['config']['config_received'];
        }

        #This test case sets up the membase cluster with the servers specified in the config and verifies the following - 
	#	that the number of vbuckets created are same as that specified in the config
	#	that the number of vbucketmigrators started are the same as that expected in the config (equal to NO_OF_VBUCKETS)
	#	that for each vbucket the active and the replica exist on 2 different servers
        public function test_Basic_Cluster_Setup()
        {
		global $test_machine_list;
		global $moxi_machines;
		cluster_setup::setup_membase_cluster();
		sleep(60);
		/*
		#Assert the number of active, replica and dead vbuckets in the cluster.
		$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("active")), NO_OF_VBUCKETS, "The number of vbuckets in cluster does not match NO_OF_VBUCKETS param in config");
		$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("replica")), NO_OF_REPLICAS*NO_OF_VBUCKETS, "The number of vbuckets in cluster does not match the expected number of replicas in config");
		$this->assertEquals(count(vba_functions::get_vbuckets_from_cluster("dead")), 0, "The number of dead vbuckets is seen to be greater than 0");
		#Assert the number of vbuckets that are started across the cluster.
		$vbucketmigrator_info = vba_functions::get_cluster_vbucket_information();
		$this->assertEquals(count($vbucketmigrator_info), NO_OF_VBUCKETS, "The number of vbuckets started across the cluster is not the same as the number of vbuckets initially configured");
		#Asserting from the vbucketmigrator info that the source and destination are on different servers for each vbucketmigrator and therefore for each vbucket..
		foreach($vbucketmigrator_info as $vb_id => $data)	
			$this->assertNotEquals($data['source'], $data['dest'], "For $vb_id, both source and destination IPs are the same");
		#Asserting that the vbuckets are distributed equally across the all nodes
		$active_array = array();
		$replica_array = array();
		foreach($test_machine_list as $id=>$server)	{
			$active_array[$id] = count(vba_functions::get_vbuckets_from_server($server, "active"));
			$replica_array[$id] = count(vba_functions::get_vbuckets_from_server($server, "replica"));
		}
		#Asserting for 3 since the deviation of distribution is expected to be a maximum of 1 for most basic cases
		$this->assertGreaterThan(count(array_unique($active_array)), 3, "Imbalanced distribution of active vbuckets");
		$this->assertGreaterThan(count(array_unique($replica_array)), 3, "Imbalanced distribution of replica vbuckets");
		*/
		print "here";
		foreach($moxi_machines as $id=>$moxi)	{
			$config = moxi_functions::get_moxi_stats($moxi, "proxy");
			print $config['vbsagent']['config']['config_received']."\n";
			$this->assertEquals($config['vbsagent']['config']['config_received'], 1, "Config not received by the moxi on $moxi");
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

