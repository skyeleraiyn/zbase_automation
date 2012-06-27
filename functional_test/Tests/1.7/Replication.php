<?php

abstract class IBR_Rep_TestCase extends ZStore_TestCase {
	public function test_Register_Tap() {

		//	Verify Tap Registration
		register_replication_tap_name(TEST_HOST_1);
		$registered_tapname = get_registered_tapname(TEST_HOST_1);
		$this->assertEquals( $registered_tapname, "replication", "Tap registration");
	}
	
	public function test_Deregister_Tap()   {		   
		//	Verify Tap Deregistration
		tap_commands::register_replication_tap_name(TEST_HOST_1);
		tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		$registered_tapname = stats_functions::get_registered_tapname(TEST_HOST_1);
		$this->assertFalse($registered_tapname, "Tap Deregistration");

	}

	public function test_Key-Count_Checkpoint_Verification() {
	
		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		attach_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		
		// Verify initial checkpoint is 1 
		
		// Add keys and verify checkpoint
		#Get master and slave server IPs.
		$master = $GLOBALS['testHost'];
		$slave = $GLOBALS['slaveHost'];

		shell_exec("/opt/membase/bin/mbflushctl $master set chk_max_items 100");
		#Pump in 5k keys.
		shell_exec("php Misc/key_pump.php $master 5000 1024");
		#Let the system sleep to allow replication to successfully complete.
		sleep(60);
		#Verify that key counts are same across the master and the slave.
		$master_key_count = shell_exec("echo stats | nc $master 11211 | grep -w curr_items | cut -d' ' -f3");
		$master_key_count = substr($master_key_count, 0, strlen($master_key_count)-1);
		$slave_key_count = shell_exec("echo stats | nc $master 11211 | grep -w curr_items | cut -d' ' -f3");
		$slave_key_count = substr($slave_key_count, 0, strlen($slave_key_count)-1);

		#Verify that closed checkpoints across the master and the slave are the same.
		$master_closed_chkpoint = shell_exec("/opt/membase/bin/mbstats $master:11211 checkpoint | grep -w last_closed_checkpoint_id | tr -s ' ' | cut -d' ' -f3");
		$master_closed_chkpoint = substr($master_closed_chkpoint, 0, strlen($master_closed_chkpoint)-1);
		$slave_closed_chkpoint = shell_exec("/opt/membase/bin/mbstats $slave:11211 checkpoint | grep -w last_closed_checkpoint_id | tr -s ' ' | cut -d' ' -f3");
		$slave_closed_chkpoint = substr($slave_closed_chkpoint, 0, strlen($slave_closed_chkpoint)-1);
		$this->assertEquals($slave_closed_chkpoint, $master_closed_chkpoint, "IBR_Closed_Checkpoint_Mismatch");

		#Verify that number of checkpoints across the master and the slave are the same.
		$master_num_chkpoint = shell_exec("/opt/membase/bin/mbstats $master:11211 checkpoint | grep -w num_checkpoints | tr -s ' ' | cut -d' ' -f3");
		$master_num_chkpoint = substr($master_num_chkpoint, 0, strlen($master_num_chkpoint)-1);
		$slave_num_chkpoint = shell_exec("/opt/membase/bin/mbstats $slave:11211 checkpoint | grep -w num_checkpoints | tr -s ' ' | cut -d' ' -f3");
		$slave_num_chkpoint = substr($slave_num_chkpoint, 0, strlen($slave_num_chkpoint)-1);
		$this->assertEquals($slave_num_chkpoint, $master_num_chkpoint, "IBR_Num_Checkpoint_Mismatch");

		#Verify that open checkpoints across the master and the slave are the same.
		$master_open_chkpoint = shell_exec("/opt/membase/bin/mbstats $master:11211 checkpoint | grep -w open_checkpoint_id | tr -s ' ' | cut -d' ' -f3");
		$master_open_chkpoint = substr($master_open_chkpoint, 0, strlen($master_open_chkpoint)-1);
		$slave_open_chkpoint = shell_exec("/opt/membase/bin/mbstats $slave:11211 checkpoint | grep -w open_checkpoint_id | tr -s ' ' | cut -d' ' -f3");
		$slave_open_chkpoint = substr($slave_open_chkpoint, 0, strlen($slave_open_chkpoint)-1);
		$this->assertEquals($slave_open_chkpoint, $master_open_chkpoint, "IBR_Open_Checkpoint_Mismatch");

	}	
}

class IBR_Rep_TestCase_Full extends IBR_Rep_TestCase{

        public function keyProvider() {
                return Data_generation::provideKeys();
        }

        public function keyValueProvider() {
                return Data_generation::provideKeyValues();
        }

        public function keyValueFlagsProvider() {
                return Data_generation::provideKeyValueFlags();
        }

        public function flagsProvider() {
                return Data_generation::provideFlags();
        }
}
