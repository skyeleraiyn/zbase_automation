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
		#sleep(60);
		#vbs_functions::get_vb_map();
		print "Active - ".count(vba_functions::get_vbuckets_from_cluster("active"))."\n";
                print "Replica - ".count(vba_functions::get_vbuckets_from_cluster("replica"))."\n";
                print "Dead - ".count(vba_functions::get_vbuckets_from_cluster("dead"))."\n";
		#foreach(vba_functions::get_cluster_vbucket_information() as $key => $value)	{
		#	print "$key\n";
		#}
		#vbs_setup::populate_and_copy_config_file($test_machine_list);
                #membase_setup::clear_cluster_membase_database($test_machine_list);
                #membase_setup::restart_membase_cluster($test_machine_list);
                #update_vbs_config(VBS_IP);
        }

        #This test case sets up the membase cluster with the servers specified in the config and verifies that the number of vbuckets created are same as that specified in the config
        public function test_Cluster_Setup()
        {
		#cluster_setup::setup_membase_cluster();
		#sleep(20);
		print "Active - ".count(vba_functions::get_vbuckets_from_cluster("active"))."\n";
		print "Replica - ".count(vba_functions::get_vbuckets_from_cluster("replica"))."\n";;
		print "Dead - ".count(vba_functions::get_vbuckets_from_cluster("dead"))."\n";;
        }
	
	public function test_reset() {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
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

