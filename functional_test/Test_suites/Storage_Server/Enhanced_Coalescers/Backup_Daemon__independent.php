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

abstract class IBR_BackupDaemon_TestCase extends ZStore_TestCase {	

	public function test_Daemon_Start_Stop() {
		#AIM // To check if the backup daemon starts and stops in a proper fashion
		#EXPECTED RESULT // Output that says backup daemon has started and stopped successfully

		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		backup_tools_functions::clear_backup_data(TEST_HOST_2);
		tap_commands::register_backup_tap_name(TEST_HOST_2);
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);		
		$this->assertTrue(zbase_backup_setup::start_backup_daemon(TEST_HOST_2), "Failed to start backup daemon");
		$this->assertTrue(zbase_backup_setup::stop_backup_daemon(TEST_HOST_2), "Failed to stop backup daemon");
	}

	public function test_Restart_While_Stopped() {
		#AIM // To check if the daemon restarts properly if restarted while stopped
		#EXPECTED RESULT // Output that says that backup daemon has restarted successfully

		backup_tools_functions::clear_backup_data(TEST_HOST_2);
		tap_commands::register_backup_tap_name(TEST_HOST_2);
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		$this->assertTrue(zbase_backup_setup::restart_backup_daemon(TEST_HOST_2), "Failed to restart backup daemon while stopped");
	}

	public function test_Restart_While_Running() {
		#AIM // To check if the daemon restarts properly if restarted while running
		#EXPECTED RESULT // Output that says that backup daemon has restarted successfully

		backup_tools_functions::clear_backup_data(TEST_HOST_2);
		tap_commands::register_backup_tap_name(TEST_HOST_2);
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue( zbase_backup_setup::restart_backup_daemon(TEST_HOST_2), "Failed to restart backup daemon while running");
	}

	public function test_Daemon_Without_Backup_Tap() {
		#AIM // Start backup daemon without registering the backup tap name
		#EXPECTED RESULT // The backup daemon does not start

		backup_tools_functions::clear_backup_data(TEST_HOST_2);
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		tap_commands::deregister_backup_tap_name(TEST_HOST_2); 
		$status = zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertFalse($status, "Started backup daemon without tap");
	}

	public function test_Check_Last_Closed_Chkpoint_File() {
		#AIM // start backupd and take a backup and  ensure that the /db/last_closed_checkpoint file is updated with the last closed checkpoint id
		#EXPECTED RESULT // The file has the same value as the last closed checkpoint on the slave

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		sleep(10);
		$last_closed_chkpoint = backup_tools_functions::last_closed_checkpoint_file(TEST_HOST_2);
		$chk_point_slave = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$this->assertEquals($last_closed_chkpoint, $chk_point_slave, "Last closed checkpoint in the file differs from the slave");
	}

	public function test_Last_Closed_Chekpoint_File_Edit() {
		#AIM // On a fresh system set the checkpoint id in the /db/last_closed_checkpoint file to a higher value (say 100). Attempt to take a backup. 
		#EXPECTED RESULT //Ensure that the backup taken is of size 4096B and the backup daemon terminates.

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_last_closed_chkpoint_file("100");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		sleep(5);
		$status = backup_tools_functions::upload_stat_from_zbasebackup_log("Invalid backup");
		$this->assertTrue(strpos($status, "Last backup checkpoint = 100") >= 0, "Backup taken despite last closed checkpoint having a bigger value");

	}

	public function est_Vary_GameID() {	// need to be investigated - gets stuck after zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		#AIM // Change the game_id entry in the default.ini file
		#EXPECTED RESULT // No backups uploaded

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::edit_defaultini_file("game_id", "test");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		sleep(10);
		$status = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED: Upload");
		$this->assertTrue(strpos($status, "test/".TEST_HOST_2."/".ZBASE_CLOUD) >= 0, "Backup taken despite game_id being invalid");
		backup_tools_functions::edit_defaultini_file("game_id", GAME_ID);

	}

	public function test_Backup_Chkpoint_Open() {
		#AIM // Start backupd and ensure that the checkpoint does not close. Verify that the logs 
		#EXPECTED RESULT// Backup is not taken since the last closed checkpoint hasn't moved.

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 3600);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		$this->assertTrue(Data_generation::add_keys(100, 1000),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = backup_tools_functions::upload_stat_from_zbasebackup_log("Last closed checkpoint");
		$this->assertTrue(strpos($status, "Last closed checkpoint ID: 0, Current closed checkpoint ID: 0") >= 0, "Backup taken despite open checkpoint");
		
	}

	public function est_BackupTime_GreaterThan_BackupInterval() {	
		#AIM // Set the backup interval to be really small. Pump in a large amount of data and take the backup such that 
			//the backup takes more time that that specified as the backup interval
		#EXPECTED RESULT // Ensures that the first backup overlaps the next and that the second backup starts successfully.
		
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
                sleep(5);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		backup_tools_functions::edit_defaultini_file("interval", "1");
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "1024");
		$this->assertTrue(Data_generation::add_keys(50000, 1000, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
        $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 10240),"Failed adding keys");
		$array_end = backup_tools_functions::get_backup_time_from_log("END", 2);
                sleep(5);
		$array_start = backup_tools_functions::get_backup_time_from_log("START", 1);
                sleep(5);
		$array_end[1]++;
		if($array_end[1] == 60) {
			$array_end[1] = "00";
			if($array_end[0] == 23){
				$array_end[0] = "00";
			} else {
				$array_end[0]++;
			}
		}
		$this->assertEquals($array_start, $array_end, "The second backup does not start after the backup interval, once the first backup is completed");
	
	}


}

class IBR_BackupDaemon_TestCase_Full extends IBR_BackupDaemon_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

