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

abstract class IBR_CoreParameters_TestCase extends ZStore_TestCase {

	public function test_Inconsistent_Slave_Chk() {
		#AIM // Set Inconsistent_Slave_Chk to true and false and check for the behaviour of checkpoints
		#EXPECTED RESULT //Checkpoints get closed when set to false, otherwise not closed

		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(550, 100),"Failed adding keys");
		//Getting the open checkpoint id
		$master_open_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		//Checking if checkpoints are being created while inconsistent_slave_chk set to false
		$this->assertEquals($master_open_checkpoint,"6", "IBR_Open_Checkpoint_Mismatch when inconsistent_slave_chk set to false");

		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "true");
		//Pumping in keys
		$this->assertTrue(Data_generation::add_keys(550, 1000),"Failed adding keys");
		//Getting the open checkpoint id
		$master_open_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		//Checking if checkpoints are being created while inconsistent_slave_chk set to true
		$this->assertEquals($master_open_checkpoint, "1", "IBR_Open_Checkpoint_Mismatch when inconsistent_slave_chk set to true");
	}

	public function test_Keep_Closed_Chks() {
		#AIM // Set keep_closed_chks to true and false, with inconsistent_slave_chk set to false, and check for the behaviour of checkpoints
		#EXPECTED RESULT // Checkpoints collapse when set to false, does not collapse otherwise
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "keep_closed_chks", "false");
		$this->assertTrue(Data_generation::add_keys(550, 100),"Failed adding keys");
		//Getting the number of checkpoints
		sleep(10);
		$master_no_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "num_checkpoints");
		//Checking if closed checkpoints are being kept while keep_closed_chks set to false
		$this->assertEquals($master_no_checkpoint,"1", "IBR_Open_Checkpoint_Mismatch when keep_closed_chks set to false");

		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "keep_closed_chks", "true");
		$this->assertTrue(Data_generation::add_keys(550, 100),"Failed adding keys");
		sleep(10);
		$master_no_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "num_checkpoints");
		//Checking if closed checkpoints are being kept while keep_closed_chks set to true
		$this->assertEquals($master_no_checkpoint, "2", "IBR_Open_Checkpoint_Mismatch when keep_closed_chks set to true");
	}


	public function test_Max_Checkpoints() {
		#AIM // Set max_checkpoints to 5(max) and 6 and check for the checkpoints
		#EXPECTED RESULT //When set to 5, create and keep 5 checkpoints and when set to 6, memcache error

		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "max_checkpoints", 5);
		$this->assertTrue(Data_generation::add_keys(550, 100),"Failed adding keys");
		sleep(10);
		//Getting the number of checkpoints
		$master_no_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "num_checkpoints");
		//Checking if checkpoints are being kept while max_checkpoints set to 5
		$this->assertEquals($master_no_checkpoint, "5", "IBR_Open_Checkpoint_Mismatch when keep_closed_chks set to false");


		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		//Checking for error when set to 
		$output = flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "max_checkpoints", 6);		
		$this->assertContains("Value out of range", $output, "IBR_Max_checkpoints value exceeds limit in test_Max_Checkpoints");

	}

	public function test_Restore_Mode() { //need to check
		#AIM // Set restore_mode to true and false and check for proper creation of checkpoints
		#EXPECTED RESULT //Checkpoints are created in proper manner

		remote_function::remote_execution(TEST_HOST_2, "sudo sed -i 's/restore_mode=false/restore_mode=true/g' ".MEMCACHED_SYSCONFIG);
		//remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig_restore_mode", MEMCACHED_SYSCONFIG, False, True, True);
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		remote_function::remote_execution(TEST_HOST_2, "sudo sed -i 's/restore_mode=true/restore_mode=false/g' ".MEMCACHED_SYSCONFIG);
		$this->assertTrue(Data_generation::add_keys(550, 100),"Failed adding keys");
		sleep(5);
		$master_open_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		//Checking if checkpoints are being created while restore_mode set to true
		$this->assertEquals($master_open_checkpoint,"6", "IBR_Open_Checkpoint_Mismatch when restore_mode set to true");
		//remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "false");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(550, 100),"Failed adding keys");
		sleep(5);
		$master_open_checkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		//Checking if checkpoints are being created while restore_mode set to false
		$this->assertEquals($master_open_checkpoint, "6", "IBR_Open_Checkpoint_Mismatch when restore_mode set to false");

	}
}

class IBR_CoreParameters_TestCase_Full extends IBR_CoreParameters_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}
}


