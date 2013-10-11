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

abstract class IBR_Rep_TestCase extends ZStore_TestCase {

	public function est_Key_Count_Checkpoint_Verification() {

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
/*
		// testcases for backup cursor mirroring
	
	public function test_slave_cursor_doesnt_move(){
		// with backup cursor on master, master should close cursor only if backups are taken on master 
		// else new checkpoint shouldn't created if backup is not taken
	
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);		
	
	}
	
check with invalid ip address
check with invalid name
check when slave reconnects back with invalid checkpoint info

check slave never gets more than two checkpoint
check with backfill
check with backfill backup 	
	*/
}

class IBR_Rep_TestCase_Full extends IBR_Rep_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
