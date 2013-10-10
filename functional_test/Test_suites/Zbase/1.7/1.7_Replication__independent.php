<?php

abstract class IBR_Rep_TestCase extends ZStore_TestCase {

	public function test_Key_Count_Checkpoint_Verification() {

		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		tap_commands::deregister_backup_tap_name(TEST_HOST_2);
		// Verify initial checkpoint is 1 

		// Add keys and verify checkpoint
#Get master and slave server IPs.
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", CHK_PERIOD_MIN);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500);
#Pump in 5k keys.
		$this->assertTrue(Data_generation::add_keys(5000, 500),"Failed adding keys");

#Let the system sleep to allow replication to successfully complete.
		sleep(10);

#Verify that key counts are same across the master and the slave.
		$master_key_count = stats_functions::get_all_stats(TEST_HOST_1,"curr_items");
		$slave_key_count = stats_functions::get_all_stats(TEST_HOST_2,"curr_items");
		$this->assertEquals($slave_key_count,$master_key_count,"IBR_Key_Count_Mismatch");

#Verify that closed checkpoints across the master and the slave are the same.
		$master_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "last_closed_checkpoint_id");
		$slave_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$this->assertEquals($slave_closed_chkpoint, $master_closed_chkpoint, "IBR_Closed_Checkpoint_Mismatch"); 

#Verify that number of checkpoints across the master and the slave are the same.
		$master_num_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "num_checkpoints");
		$slave_num_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "num_checkpoints");
		$this->assertEquals($slave_num_chkpoint, $master_num_chkpoint, "IBR_Num_Checkpoint_Mismatch");

#Verify that open checkpoints across the master and the slave are the same.
		$master_open_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		$slave_open_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "open_checkpoint_id");
		$this->assertEquals($slave_open_chkpoint, $master_open_chkpoint, "IBR_Open_Checkpoint_Mismatch");

	}

}

class IBR_Rep_TestCase_Full extends IBR_Rep_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
