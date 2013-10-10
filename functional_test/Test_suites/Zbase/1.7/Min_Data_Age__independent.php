<?php                                                                                                                                                                         
abstract class Min_Data_Age_TestCase extends ZStore_TestCase {                        

	public function test_Expiry_Pager_Stime() {
                zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$expiry_pager_run_count = stats_functions::get_all_stats(TEST_HOST_1,"ep_num_expiry_pager_runs");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"exp_pager_stime", "10");
		Data_generation::add_keys(10);
		sleep(11);
		$expiry_pager_run_count++;
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"exp_pager_stime", "3600");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_num_expiry_pager_runs"), $expiry_pager_run_count, "exp_pager_stime(positive)");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_min_data_age($testKey)    {
		//AIM : Test basic functionality with min_data_age set
		// EXPECTED RESULT : Key gets persisted on the master and the slave only after min_data_age period 
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Get stats before any key is set in
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		//Assert that the key is not immediately persisted.
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");	
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");
		sleep(70);
		//Verify that the key is persisted after the min_data_age is elapsed.
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after min_data_age period on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted after min_data_age period on slave");
		// Deleting the keys so that they dont create problem in next tests
		$instance->delete($testKey);
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_min_data_age_with_queue_age_cap($testKey)	{
		// AIM : Test basic functionality with min_data_age and queue_age_cap set
		//EXPECTED RESULT : Mutated keys are persisted only after queue_age_cap period and not after min_data_age period
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Setting queue-age_cap
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2 ,  "queue_age_cap" , 90);
		//Get stats before any key is set in
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");
		//Mutate the key
		Utility::mutate_key($instance ,$testKey,"value",60,1);
		//Ensure that mutated keys are not persisted after min_data_age period
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item persisted after min_data_age on master despite mutations");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item persisted after min_data_age on slave despite mutations");
		sleep(45);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after queue_age_cap period  on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted after queue_age_cap period on slave");
		$instance->delete($testKey);
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_queue_age_cap_less_than_min_data_age($testKey)	{
		//AIM : Test functionality when queue_age_cap is set less than min_data_age. 
		//EXPECTED : The key should be persisted once queue_age_cap is hit.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "queue_age_cap" , 60);
		//Get stats before any key is set in
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_master_before_set),"min_data_age not respected on slave");
		sleep(60);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after queue_age_cap period  on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_master_before_set), "Item not persisted after queue_age_cap period  on slave");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_different_mda_and_qac_on_master_and_slave($testKey)	{
		//AIM: Ensuring that nothing breaks when different values of min_data_age and queue_age_cap are set on master and slave.
		//EXPECTED: Key is replicated across to slave succesfully and system is stable.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 75);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "queue_age_cap" , 120);
		//Get stats before any key is set in
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_master_before_set),"min_data_age not respected on slave");
		sleep(60);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after min_data_age period  on master");
		sleep(15);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_master_before_set), "Item not persisted after queue_age_cap period  on slave");	
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_Basic_Persistence_Disabled($testKey){
		// AIM : Test basic functionality with persisitence disabled
		//EXPECTED RESULT : Keys are persisted only after enabling persistence
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		// Stopping persistence
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1 , "stop");
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_2 , "stop");
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		sleep(60);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "key persisted after min_data_age while persistence stopped on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "key persisted after min_data_age while persistence stopped on slave");
		sleep(60);
		// Start persistence 
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1 , "start");
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_2 , "start");
		sleep(2);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "Item not persisted immediately after enabling persistence  on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted immediately after enabling persistence  on slave");
		$instance->delete($testKey);
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_Basic_Persistence_Disabled_with_queue_age_cap($testKey){
		// AIM : Test basic functionality with persisitence disabled and queue_age_cap set
		//EXPECTED RESULT : Mutated keys are persisted only after enabling persistence
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Setting queue-age_cap
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2 ,  "queue_age_cap" , 90);
		// Stopping persistence
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1 , "stop");
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_2 , "stop");
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");	
		//Mutate the key
		Utility::mutate_key($instance,$testKey,"value", 120,1);
		// Start persistence
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1 , "start");
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_2 , "start");
		sleep(2);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "Item not persisted after queue_age_cap period  on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted after queue_age_cap period  on slave");

	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_Basic_Delayed_Vbucketmigrator_Attach($testKey)    {
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(30);
		//Attaching vbucketmigrator
		vbucketmigrator_function::attach_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		sleep(10);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");
		sleep(50);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after min_data_age period on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted after min_data-_age period on slave");                        
	}


	/**
	* @dataProvider keyProvider
	*/
	public function test_Basic_VB_attached_after_Key_Persisted_on_master($testKey){
		// AIM: Test basic functionality with vbucketmigrator attached after the key is persisted on the master
		//EXPECTED RESULT : The key gets persisted immediately on the slave once vbucketmigrator is attached.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age",60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "Item not persisted after min_data_age period on master");
		//Setting vbucketmigrator
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(60);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"Item not persisted after min_data_age slot on slave after attaching vbucket migrator");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_Basic_VB_attached_after_Key_Persisted_on_master_without_registered_Tap($testKey){
		// AIM: Basic functionality with vbucketmigrator attached after key gets persisited on master without a registered tap name
		//EXPECTED RESULT : Even without a registered tap name the key is immediately persisted on the slave
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age",60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "Item not persisted after min_data_age period on master");
		// Start vbucketmigrator
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(60);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"Item not persisted immediately on slave after attaching vbucket migrator");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_Basic_VB_attached_at_start_without_registered_Tap($testKey){
		// AIM: Basic functionality with vbucketmigrator attached on start without a registered tap name
		//EXPECTED RESULT : Even without a registered tap name the key is immediately persisted on the slave
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);	
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age",60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "Item not persisted after min_data_age period on master");
		sleep(4);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"Item not persisted immediately on slave after attaching vbucket migrator");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_min_data_age_on_Slave_only($testKey){
		// AIM: Test Behavior when min_data_age set on slave but not on master
		//EXPECTED RESULT : The key gets persisted on the slave only after the min_data_age period but gets persisted on the master immediately.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set) , "Item not persisted immediately on master despite no min_data_age");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set),"min_data_age not respected on slave");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set), "Item not persisted after min_data_age period on slave");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_min_data_age_on_Master_only($testKey){
		// AIM: Test Behavior when min_data_age set on master but not on slave
		//EXPECTED RESULT : the key gets persisted on the master only after the min_data_age period but gets persisted immediately on the slave
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 0);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted immediately on slave despite no min_data_age");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after min_data_age period on master");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function est_VB_attached_after_min_data_age_Before_queue_age_cap($testKey){ 
		// AIM :Test behaviour when vbucktetmigrator attached after min_data_age has elapsed for a key but before queue_age_cap is reached
		//EXPECTED RESULT : The key gets replicated to the slave succesfully but is persisted only once queue_age_cap is reached.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);	
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Setting queue-age_cap
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2 ,  "queue_age_cap" , 90);
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		//Mutate the key
		Utility::mutate_key($instance,$testKey,"value",60,1);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item persisted after min_data_age on master despite mutations");
		sleep(10);
		// Starting vbucketmigrator
		vbucketmigrator_function::attach_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		sleep(5);
		// check: why should it sink only after queue_age cap in slave Fails at the below step.
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set), "Item persisted after min_data_age on slave despite mutations");
		//Mutate the key till queue_age_cap
		Utility::mutate_key($instance,$testKey,"value", 20, 1);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after queue_age_cap period  on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted after queue_age_cap period on slave");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_VB_attached_after_queue_age_cap($testKey)	{
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Setting queue-age_cap
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2 ,  "queue_age_cap" , 90);
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		//Mutate the key
		Utility::mutate_key($instance,$testKey,"value",90,1);
		sleep(30);
		// Starting vbucketmigrator
		vbucketmigrator_function::attach_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		sleep(5);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after queue_age_cap period  on master");
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item not persisted after queue_age_cap period on slave");
	}

	public function test_Modify_few_Keys_with_min_data_age(){
		// AIM : Test modification of a few keys with min_data_age set
		// EXPECTED RESULT : The nonmodified keys get persisted when min_data_age is reached but the keys that were getting modified get persisted only when the queue_age_cap is reached.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Setting queue-age_cap
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2 ,  "queue_age_cap" , 90);	
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$getData =Data_generation::PrepareHugeData(10);
		$key_list = $getData[0];
		$value_list = $getData[1];
		for($i=0;$i<10;$i++){
			$instance->set($key_list[$i], $value_list[$i]);
		}
		sleep(5);
		$items_persisted_master_after_set = stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$items_persisted_slave_after_set = stats_functions::get_all_stats(TEST_HOST_2, "ep_total_persisted");
		$this->assertEquals($items_persisted_master_before_set-$items_persisted_master_after_set, 0, "min_data_age not respected on master");
		$this->assertEquals($items_persisted_slave_before_set-$items_persisted_slave_after_set, 0, "min_data_age_not respected on slave");

		//Modify the first 5 keys
		for($j=0;$j<12;$j++){				//Mutate 5 keys for 60 sec, so each key gets mutated 12 times
			for($i=0;$i<5;$i++){
				Utility::mutate_key($instance ,$key_list[$i],$value_list[$i], 1 , 1);
			}
		}
		sleep(15);
		// Verify that only last 5 keys are persisted
		$items_persisted_master_after_mutations = stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$items_persisted_slave_after_mutations = stats_functions::get_all_stats(TEST_HOST_2 , "ep_total_persisted");
		$this->assertEquals($items_persisted_master_after_mutations-$items_persisted_master_after_set , 5 , "Items not persisted on master after min_data_age");
		$this->assertEquals($items_persisted_slave_after_mutations-$items_persisted_slave_after_set , 5 ,"Items not persisted on slave after min_data_age");
		sleep(30);
		// Verify that all keys are persisted
		$items_persisted_master_after_queue_age_cap = stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$items_persisted_slave_after_queue_age_cap = stats_functions::get_all_stats(TEST_HOST_2, "ep_total_persisted");
		$this->assertEquals($items_persisted_master_after_queue_age_cap-$items_persisted_master_after_mutations, 5, "Items not persisted after queue_age_cap period  on master");
		$this->assertEquals($items_persisted_slave_after_queue_age_cap-$items_persisted_slave_after_mutations, 5, "Items not persisted after queue_age_cap period on slave");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_Modify_min_data_age($testKey){
		// AIM :Test behaviour on modification of min_data_age		
		// EXPECTED RESULT : When min_data_age is modified to a lower value, the key gets persisted when the revised min_data_age value is reached.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 120);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 120);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");	
		sleep(30);
		//Modify min_data_age on master
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		sleep(30);
		//Ensure that the item is persisted on master after 90 sec but not on slave
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after min_data_age period  on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"Item persisted after min_data_age period of master on slave");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set), "Item not persisted after min_data_age period  on slave");
	}

	/**
	* @dataProvider keyProvider
	*/	
	public function test_Modify_queue_age_cap($testKey){
		// AIM :Test behaviour on modification of queue_age_cap
		// EXPECTED RESULT : When queue_age_cap is modified to a lower value, the key gets persisted when the revised queue_age_cap value is reached.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		//Setting queue-age_cap
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 120);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2 ,  "queue_age_cap" , 120);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");
		//Mutate the key
		Utility::mutate_key($instance,$testKey,"value",60,1);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item persisted after min_data_age on master despite mutations");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) , "Item persisted after min_data_age on slave despite mutations");	
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		sleep(45);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted after new queue_age_cap period  on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set), "Item persisted after new queue_age_cap period of master on slave");
		sleep(40);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set),"Item not persisted after new queue_age_cap period  on slave");
	}

	public function test_multiple_Checkpoints_same_Mutations(){
		// AIM : Test behaviour of  min_data_age with multiple checkpoints  containing the same mutations
		// EXPECTED RESULT : Only the required no of keys are persisted
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 200);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 200);
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$checkpoint_Before_set = stats_functions::get_checkpoint_stats(TEST_HOST_1,"open_checkpoint_id");
		$getData =Data_generation::PrepareHugeData(100);
		$key_list = $getData[0];
		$value_list = $getData[1];
		for($i=0;$i<100;$i++){
			$instance->set($key_list[$i] , $value_list[$i]);
		}
		sleep(5);
		$checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1,"open_checkpoint_id");
		$this->assertEquals($checkpoint - $checkpoint_Before_set , 1 , "Checkpoint not closed ");
		$items_persisted_master_after_set = stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$items_persisted_slave_after_set = stats_functions::get_all_stats(TEST_HOST_2, "ep_total_persisted");
		$this->assertEquals($items_persisted_master_before_set-$items_persisted_master_after_set, 0, "min_data_age not respected on master");
		$this->assertEquals($items_persisted_slave_before_set-$items_persisted_slave_after_set, 0, "min_data_age_not respected on slave");
		//Mutate the keys
		for($count=1;$count<=3;$count++){
			for($i=0;$i<100;$i++){
				Utility::mutate_key($instance ,$key_list[$i],$value_list[$i],1,1);
				if($i%10 == 0)
				sleep(2);
			}
			sleep(10);
		}
		sleep(200);
		// Ensure that only 100 items persisted
		$items_persisted_master_after_queue_age_cap = stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$items_persisted_slave_after_queue_age_cap = stats_functions::get_all_stats(TEST_HOST_2, "ep_total_persisted");
		$this->assertEquals($items_persisted_master_after_queue_age_cap-$items_persisted_master_after_set, 100, "Items not persisted after queue_age_cap period  on master");
		$this->assertEquals($items_persisted_slave_after_queue_age_cap-$items_persisted_slave_after_set, 100, "Items not persisted after queue_age_cap period on slave");
	}

	public function test_Higher_no_of_Keys(){// check: we cant add keys with checkpoints closed with min_data_age 600
		// AIM : Test min_data_age with higher number of keys
		// EXPECTED RESULT : All keys get persisted immediately when the min_data_age parameter is reached
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000 );
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 600);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 600);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);	
		$this->assertTrue(Data_generation::add_keys(1000000, 500000, 1, 20, 200),"Failed adding keys");
		sleep(350);
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_1,1000000, 300),"All items not persisted after min_data_age period  on master");
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_2,1000000, 300),"All items not persisted after min_data_age period on slave");
	}

	public function test_Higher_no_of_Keys_Single_Checkpoint(){
		// AIM : min_data_age with high number of keys and single checkpoint with chk_max_items set
		// EXPECTED RESULT : All keys get persisted immediately when the min_data_age parameter is reached
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000 );
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period" ,1000);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 200);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 200);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$this->assertTrue(Data_generation::add_keys(500000, 500000, 1, 20),"Failed adding keys");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");
		// Ensure all items are replicated to slave
		sleep(50);
		$curr_items_on_slave = stats_functions::get_all_stats(TEST_HOST_1 , "curr_items");
		$this->assertEquals($curr_items_on_slave , 500000 , "All items not replicated to slave");
		sleep(120);
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_1,500000, 200),"All items not persisted after min_data_age period  on master");
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_2,500000, 200),"All items not persisted after min_data_age period on slave");
	}

	public function test_min_data_age_with_Checkperiod(){
		// AIM : Test min_data_age with chk_period set
		// EXPECTED RESULT : The first 100 keys get persisted after 120 seconds and the remaining 400 keys get persisted after 180 seconds.
		// Setting chk_period
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		$instance = Connection::getMaster();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period" ,60);
		//Setting chk_max_items
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items",500);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 120);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 120);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "queue_age_cap", 180);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$checkpoint_before_set = stats_functions::get_checkpoint_stats(TEST_HOST_1,"open_checkpoint_id");
		$this->assertTrue(Data_generation::add_keys(100,500,1,20) ,"Failed adding keys");
		sleep(60);
		$checkpoint_after_set = stats_functions::get_checkpoint_stats(TEST_HOST_1,"open_checkpoint_id");
		$this->assertEquals($checkpoint_after_set-$checkpoint_before_set ,1 ,"Checkpoint not closed");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set),"min_data_age not respected on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set) ,"min_data_age not respected on slave");
		$this->assertTrue(Data_generation::add_keys(400,500,101,20) ,"Failed adding keys");
		sleep(80);
		$items_persisted_master_after_min_data_age = stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$items_persisted_slave_after_min_data_age = stats_functions::get_all_stats(TEST_HOST_2, "ep_total_persisted");
		$this->assertEquals($items_persisted_master_after_min_data_age-$items_persisted_master_before_set, 100, "Item not persisted after min_data_age period  on master");
		//This won't be applicable
#$this->assertEquals($items_persisted_slave_after_min_data_age-$items_persisted_slave_before_set, 100, "Item not persisted after min_data_age period on slave");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1,$items_persisted_master_after_min_data_age),"min_data_age not respected for new keys on master");
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_2,$items_persisted_slave_after_min_data_age) ,"min_data_age not respected for new keys on slave");
		sleep(110);
		$new_items_persisted_master_after_min_data_age = stats_functions::get_all_stats(TEST_HOST_1,  "ep_total_persisted");
		$new_items_persisted_slave_after_min_data_age = stats_functions::get_all_stats(TEST_HOST_2,  "ep_total_persisted");
		$this->assertEquals($new_items_persisted_master_after_min_data_age-$items_persisted_master_after_min_data_age , 400 , "Item not persisted after min_data_age period  on master");
		$this->assertEquals($new_items_persisted_slave_after_min_data_age - $items_persisted_slave_before_set , 500 , "Item not persisted after min_data_age period on slave");

	}

	public function test_Backfill_based_Tap_Registration(){
		// AIM : Test Behaviour of min_data_age with backfill based tap registration
		// EXPECTED RESULT : All the keys replicated to the slave are persisted immediately.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1,TEST_HOST_2));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 0);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$this->assertTrue(Data_generation::add_keys(3000 , 1000 , 1 ,20),"Failed adding keys");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "queue_age_cap", 90);
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l 0 -b");
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(65);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_2, $items_persisted_slave_before_set), "Items not persisted even after hitting queue_age_cap on slave after attaching vbucket");


	}

	public function test_Backfill_based_Tap_Registration_Higher_no_of_keys(){
		// AIM : Test Higher number of keys with backfill based tap registration
		// EXPECTED RESULT : All keys get persisted immediately on being replicated to the slave and no backpush is seen on the master.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1,TEST_HOST_2));
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$this->assertTrue(Data_generation::add_keys(1000000, 500000, 1, 20));	
		sleep(10);
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_1,1000000, 60),"Item not persisted immediately on master despite no min_data_age");	
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l 0 -b");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 60);
		sleep(60);
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(70);
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_2,1000000, 60),"Item not persisted immediately on slave after attaching vbucket migrator for backfill replication");
	}	

	public function test_VB_Disconnects_during_Replication(){
		// AIM : Test behaviour when vbucketmigrator disconnects during replication
		// EXPECTED RESULT : Though vbucketmigrator was being killed and re-established all the keys get persisted on both the master and the slave when the min_data_age time is reached.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age",300);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 300);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Get stats before any key is set
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_slave_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		//Pump in 0.5M keys with sleep of 1 sec for every 100 keys and disconnect and reconnect vbucketmigrator with interval of 10 seconds 
		for($iter = 0;$iter < 10 ; $iter++){
			for($count = 0 ;$count<10;$count++){
				$this->assertTrue(Data_generation::add_keys(1000, 5000, 1+($iter*10000)+($count*1000), 20),"Failed adding keys");
			}
			if(($iter)%2==0){
				vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
			} else {
				log_function::debug_log("Attempting to start the vbucketmig");
				vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
			}
		}
		vbucketmigrator_function::attach_vbucketmigrator(TEST_HOST_1 , TEST_HOST_2);
		sleep(250);
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_1, 100000, 300),"All items not persisted on master after min_data_age period");
		$this->assertTrue(Utility::Check_keys_are_persisted(TEST_HOST_2, 100000, 300),"All items not persisted on slave after min_data_age period");
	}	

	/**
	* @dataProvider keyProvider
	*/
	public function test_min_data_age_on_Persisted_Key($testKey)    {
		// AIM : Test min_data_age behavior when key is already persisted
		// EXPECTED RESULT : Initially the key gets persisited immediately but after min_data_age is changed the key gets persisted only min_data_age seconds after it is modified.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		$instance = Connection::getMaster();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		//Get stats before any key is set in
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$instance->set($testKey, "value");
		sleep(5);
		//Verify that the key is immediately persisted
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted immediately on master");		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		$items_persisted_master_before_modification = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$instance->set($testKey, "Newvalue");	
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_modification) ,"Item persisted immediately on master despite min_data_age set");
		sleep(70);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_modification), "Item not persisted after min_data_age period on master");
	}

	/**
	* @dataProvider keyProvider
	*/
	public function test_min_data_age_on_Persisted_Key_with_queue_age_cap($testKey){
		// AIM : Test min_data_age behavior when key is already persisted and queue_age_cap is set
		// EXPECTED RESULT : Initially the key gets modified immediately but after min_data_age is changed, the key gets persisted only 120 seconds after the modifications are started.
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		$instance = Connection::getMaster();
		//Setting min_data_age
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		//Get stats before any key is set in
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$items_persisted_master_before_set = Utility::Get_ep_total_persisted(TEST_HOST_2);
		$instance->set($testKey, "value");
		sleep(5);		
		//Verify that the key is immediately persisted
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_set), "Item not persisted immediately on master");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 60);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "queue_age_cap" , 90);
		$items_persisted_master_before_modification = Utility::Get_ep_total_persisted(TEST_HOST_1);
		//Mutate the key
		Utility::mutate_key($instance ,$testKey,"value",60,1);
		sleep(10);
		$this->assertFalse(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_modification) ,"Item persisted after min_data_age on master despite modifications");
		sleep(30);
		$this->assertTrue(Utility::Get_ep_total_persisted(TEST_HOST_1, $items_persisted_master_before_modification), "Item not persisted after queue_age_cap period on master");
	}

}                                                                                 

class Min_Data_Age_TestCase_Quick extends Min_Data_Age_TestCase{                       

	public function keyProvider() {
		return Data_generation::provideKeys();
	}

}


