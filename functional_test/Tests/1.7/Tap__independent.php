<?php

abstract class IBR_Tap_TestCase extends ZStore_TestCase {

	public function test_Register_Tap() {
		// Verify Tap Registration

		// First time tap registration
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration");
		// Registration of tap for the second time
		tap_commands::register_replication_tap_name(TEST_HOST_1);

		// Checking if the tap name changes after the second registration
		$registered_tapname_repeat = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname_repeat, $registered_tapname, "Tap name change after second registration");
	}

	public function test_Deregister_Tap()   {
		// Verify Tap Deregistration

		tap_commands::register_replication_tap_name(TEST_HOST_1);
		// First time tap deregistration
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals($registered_tapname, "NA", "Tap Deregistration");
		// Deregistration of tap for the second time
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);

		// Checking if the tap name changes after the second deregistration
		$registered_tapname_repeat = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals($registered_tapname_repeat, $registered_tapname, "Tap name change after second Deregistration");
	}

	public function test_Register_Tap_Options() {
		//Verify Tap Registrations with additional -c option

		//Registering tap using -c option
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		tap_commands::register_replication_tap_name(TEST_HOST_1," -c");
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -c option");
	}

	public function test_Multiple_Taps_Stability() {
#AIM //Verify system stability for multiple tap names registration
#EXPECTED RESULT //System Stability and successful tap registration

		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		//Registering multiple taps
		tap_commands::register_multiple_taps(TEST_HOST_1, 10);
		//Checking for system stability
		$status = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertGreaterThanOrEqual(0, $status, "System unstable due to multiple tap registrations");
		//Checking if all tabs have been registered
		$status = stats_functions::get_checkpoint_stats(TEST_HOST_1, "num_tap_cursors");
		$this->assertEquals($status, "10", "Multiple tap registration failure");
	}

	public function test_Registration_Deregistration_Stability() {
#AIM //Verify system stability for constant registration and deregistration
#EXPECTED RESULT //System Stability and successful tap registration

		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		//Register and deregister tap names for 50 times
		for($i=0;$i<=50;$i++) {
			tap_commands::register_replication_tap_name(TEST_HOST_1);
			tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		}		
		$status = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertGreaterThanOrEqual(0, $status, "System unstable due to constant registration and deregistration");
	}

	public function test_Tap_Register_l0_b() {
#AIM //Tap registration with -b and -l option set to 0
#EXPECTED RESULT //Starts repliaction from the first checkpoint and successful tap registration

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		//tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(1000, 100));
		sleep(10);
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l 0 -b");
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -l 0 -b option");
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count is same across master and slave
		$master_key_count = stats_functions::get_all_stats(TEST_HOST_1,"curr_items");
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,$master_key_count,"IBR_Key_Count_Mismatch while tap registration using -l 0 -b");
		//Checking if the last closed checkpoint of the master and slave are the same
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id");
		$slave_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$this->assertEquals($slave_closed_chkpoint, $master_closed_chkpoint, "IBR_Closed_Checkpoint_Mismatch while tap registration using -l 0 -b");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Tap_Register_l1() {
#AIM  //Tap registration with -l option set to 1 less than the last closed checkpoint
#EXPECTED RESULT //Replication starts from the last closed checkpoint and successful tap registration
		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		//tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(350, 100,1));
		sleep(15);
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id");
		$master_closed_chkpoint = $master_closed_chkpoint - 1;
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l ".$master_closed_chkpoint);
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -l $master_closed_chkpoint option");
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count in slave is correct
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count, "150","IBR_Key_Count_Mismatch while tap registration using -l $master_closed_chkpoint ");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Tap_Register_l2() {
#AIM  //Tap registration with -l option set to 2 less than the last closed checkpoint
#EXPECTED RESULT //Replication starts from the last but one closed checkpoint and successful tap registration

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		//tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(350, 100, 1));
		sleep(10);
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id");
		$master_closed_chkpoint = $master_closed_chkpoint - 2;
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l ".$master_closed_chkpoint);
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -l $master_closed_chkpoint option");
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count in slave is correct
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,"50","IBR_Key_Count_Mismatch while tap registration using -l $master_closed_chkpoint ");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Tap_Register_l2_b() {
#AIM  //Tap registration with -l option set to 2 less than the last closed checkpoint and using -b
#EXPECTED RESULT //Replication starts from the last but one closed checkpoint and successful tap registration

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		//tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(350, 100, 1));
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id");
		$master_closed_chkpoint = $master_closed_chkpoint - 2;
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l ".$master_closed_chkpoint." -b");
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -l $master_closed_chkpoint -b option");
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count is same across master and slave
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,"250","IBR_Key_Count_Mismatch while tap registration using -l $master_closed_chkpoint -b");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Tap_Register_l1_b() {
#AIM  //Tap registration with -l option set to 1 less than the last closed checkpoint and using -b
#EXPECTED RESULT //Replication starts from the last closed checkpoint and successful tap registration

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		//tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(350, 100));
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id");
		$master_closed_chkpoint = $master_closed_chkpoint - 1;
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l ".$master_closed_chkpoint." -b");
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -l $master_closed_chkpoint -b option");
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count is same across master and slave
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,"150","IBR_Key_Count_Mismatch while tap registration using -l $master_closed_chkpoint -b");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Tap_Register_l0() {
#AIM  //Tap registration with -l option set to 0
#EXPECTED RESULT //Replication starts from the beginning and successful tap registration

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		//tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(350, 100));
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1," -l 0");
		$registered_tapname = stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id");
		$this->assertEquals( $registered_tapname, "replication", "Tap registration using -l 0 option");
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count in slave is correct
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,"50","IBR_Key_Count_Mismatch while tap registration using -l 0 ");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Replication_After_Memcache_Restart() {
#AIM //Register a tap name on the master and then pump in some keys. Restart memcache and verify if it is possible to still replicate items to the slave.
#EXPECTED RESULT //Replication performed successfully

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Registering tap name
#tap_commands::register_replication_tap_name(TEST_HOST_1);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(200, 100));
		sleep(5);
		//Restart memcache
		membase_function::kill_membase_server(TEST_HOST_1);
		membase_function::start_memcached_service(TEST_HOST_1);
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count is same across master and slave
		$master_key_count = stats_functions::get_all_stats(TEST_HOST_1,"curr_items");
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,$master_key_count,"IBR_Key_Count_Mismatch while restarting memcache after membase_function::key_pump");
		//Checking if the last closed checkpoint of the master and slave are the same
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id"); 
		$slave_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$this->assertEquals($slave_closed_chkpoint, $master_closed_chkpoint, "IBR_Closed_Checkpoint_Mismatch while restarting memcache after membase_function::key_pump");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_RestartvBucketmigrator_After_Dbdelete() {
#AIM //Register a tap. Pump in data and replicate to the slave. Kill vbucketmigrator between the master and the slave. Delete db on master. Reattach vbucketmigrator again.
#EXPECTED RESULT //Data is not wiped out from slave

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(199, 100, 1));
		sleep(5);
		//Starting vbucketmigrator
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		//Deleting the db
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		sleep(5);
		//Restarting vbucket migrator
		tap_commands::register_replication_tap_name(TEST_HOST_1, " -l 0 -b");
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count in slave is correct
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,"199","IBR_Key_Count_Mismatch while tap registration while db is deleted ");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Restartvbukcet_Keypump_Simultaneuosuly() {
#AIM //Constantly restart vbucketmigrator when data is being pumped\
#EXPECTED RESULT //Data on master and slave are the same

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Registering tap name
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		$pid = pcntl_fork();
		if($pid == 0) {
			//Pumping in keys as child process
			Data_generation::add_keys(100000);
			exit;
		} else {
			$start_time = time();
			//Restart vbucketmigrator as parent process
			while(true){
				vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
				sleep(rand(3, 8));
				vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
				sleep(rand(3, 8));
				$end_time = time() - $start_time;
				if($end_time > 480) break;
			}
		}
		$this->assertNotEquals($pid, -1, "Could not create key pump as child process in test_Restartvbukcet_Keypump_Simultaneuosuly");
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		//Checking if the key count is same across master and slave
		$master_key_count = (int) (trim(stats_functions::get_all_stats(TEST_HOST_1,"curr_items")));
		$slave_key_count = (int) (trim(stats_functions::get_all_stats(TEST_HOST_2,"curr_items")));
		$this->assertEquals($slave_key_count,$master_key_count,"IBR_Key_Count_Mismatch in test_Restartvbukcet_Keypump_Simultaneuosuly");
	}

	public function test_Vbucket_Without_Tap() {
#AIM //Start vbucketmigrator between the master and the slave without registering a tap name
#EXPECTED RESULT //Replication takes place normally

		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(350, 100));
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(10);
		//Checking if the key count is same across master and slave
		$master_key_count = stats_functions::get_all_stats(TEST_HOST_1,"curr_items");
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,$master_key_count,"IBR_Key_Count_Mismatch in test_Vbucket_Without_Tap");
		//Checking if the last closed checkpoint of the master and slave are the same
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id"); 
		$slave_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$this->assertEquals($slave_closed_chkpoint, $master_closed_chkpoint, "IBR_Closed_Checkpoint_Mismatch in test_Vbucket_Without_Tap");
		//Killing vbucketmigrator
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Tap_Restart_Memcache() {
#AIM //Register tap, restart memcache and verify that tap information exists
#EXPECTED RESULT //Tap information is lost

		membase_function::reset_membase_servers(array(TEST_HOST_1));
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		//Restart memcache
		membase_function::kill_membase_server(TEST_HOST_1);
		membase_function::start_memcached_service(TEST_HOST_1);		
		$registered_tapname = trim(stats_functions::get_checkpoint_stats(TEST_HOST_1, "cursor_checkpoint_id"));
		$this->assertNotEquals( $registered_tapname, "replication", "Tap name exists in test_Tap_Restart_Memcache");
	}

}

class IBR_Tap_TestCase_Full extends IBR_Tap_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

