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

	public function test_Check_Upload_To_SS() {
		#AIM // Start backupd and take a backup. Grep the logs and verify that the backup is uploaded to the storage server. 
			// Validate backup on the storage server.
		#EXPECTED RESULT // Uploading is completed properly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		//Primarily done to check if the first backup that is being uploaded goes as the master backup.
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		foreach($array as $path) {
			$status  = file_function::check_file_exists(STORAGE_SERVER_1, $path);
			$this->assertTrue($status, "Backup file not found on SS");
		}
		$array = storage_server_functions::list_master_backups(STORAGE_SERVER_1, ".split");
		$status  = file_function::check_file_exists(STORAGE_SERVER_1, $array[0]);
		$this->assertTrue($status, "Split file not found on SS");
		$array = storage_server_functions::list_master_backups(STORAGE_SERVER_1, ".done");
		$status  = file_function::check_file_exists(STORAGE_SERVER_1, $array[0]);
		$this->assertTrue($status, "Done file not found on SS");
		//Next, to verify that the backup goes up as an incremental backup since the master backup is already been uploaded.
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_incremental_backups();
		foreach($array as $path) {
			$status  = file_function::check_file_exists(STORAGE_SERVER_1, $path);
			$this->assertTrue($status, "Backup file not found on SS");
		}
		$array = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, ".split");
		$status  = file_function::check_file_exists(STORAGE_SERVER_1, $array[0]);
		$this->assertTrue($status, "Split file not found on SS");
	
	}

	public function test_Zbasebackup_Log() {
		#AIM // Check the zbasebackup log for successful upload message, get the filename and check if it exists in the SS
		#EXPECTED RESULT // The zbasebackup log has the successful upload message and the file  exists in the SS

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = backup_tools_functions::upload_stat_from_zbasebackup_log("SUCCESS");
		foreach($array as $file_path) {
			$status  = file_function::check_file_exists(STORAGE_SERVER_1, $file_path);
			$this->assertTrue($status, "Backup file not found on SS");
		}
	}

	public function test_Upload_Different_Size_Backups() {
		#AIM // Modify split size (10MB, 25MB, 50MB) and verify that the backups taken and uploaded are of the corresponding size
		#EXPECTED RESULT // Files uploaded are of the correct  size

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "10");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1500);
		$this->assertTrue(Data_generation::add_keys(1500, 1500, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$size = backup_tools_functions::get_backup_size(STORAGE_SERVER_1, $array[0]);
		$this->assertGreaterThanOrEqual(9961472, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(11010048, $size, "Backup database with greater size than expected");
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "25");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 3000);
		$this->assertTrue(Data_generation::add_keys(3000, 3000, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$size = backup_tools_functions::get_backup_size(STORAGE_SERVER_1, $array[0]);
		$this->assertGreaterThanOrEqual(25690112, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(26738688, $size, "Backup database with greater size than expected");

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "50");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 6000);
		$this->assertTrue(Data_generation::add_keys(6000, 6000, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$size = backup_tools_functions::get_backup_size(STORAGE_SERVER_1, $array[0]);
		$this->assertGreaterThanOrEqual(51904512, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(52953088, $size, "Backup database with greater size than expected");

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

	public function test_Two_Backups_One_Chkpoint_Each() {	
		#AIM // Start backupd on the slave and ensure that atleast 2 backups are taken containing one checkpoint each. 
		//Validate the backups on the storage server.
		#EXPECTED RESULT // Backups are uploaded correctly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 1.2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000),"Failed adding keys");
		$this->assertTrue(zbase_backup_setup::start_backup_daemon(TEST_HOST_2), "Failed uploading the backups");
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$count_backup = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[0]);
		$count_backup += zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[1]);
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");
		sleep(10);
		$chk_point_backup = zbase_function::sqlite_chkpoint_count(STORAGE_SERVER_1, $array[0]);
		$this->assertEquals($chk_point_backup, "2", "Checkpoint_mismatch in backup");
		$chk_point_backup = zbase_function::sqlite_chkpoint_count(STORAGE_SERVER_1, $array[1]);
		$this->assertEquals($chk_point_backup, "1", "Checkpoint_mismatch in backup");
	}

	public function test_Two_Backups_Three_Chkpoint_Each() {	 
		#AIM // Start backupd on the slave and ensure that atleast 2 backups are taken containing three checkpoints each. 
		//Validate the backups on the storage server.
		#EXPECTED RESULT // Backups are uploaded correctly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 4);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(6000, 1000),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$count_backup = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[0]);
		$count_backup += zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[1]);
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");	
		$chk_point_backup = zbase_function::sqlite_chkpoint_count(STORAGE_SERVER_1, $array[0]);
		$this->assertEquals($chk_point_backup, "4", "Checkpoint_mismatch in backup");
		$chk_point_backup = zbase_function::sqlite_chkpoint_count(STORAGE_SERVER_1, $array[1]);
		$this->assertEquals($chk_point_backup, "3", "Checkpoint_mismatch in backup");
	}

}

class IBR_BackupDaemon_TestCase_Full extends IBR_BackupDaemon_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

