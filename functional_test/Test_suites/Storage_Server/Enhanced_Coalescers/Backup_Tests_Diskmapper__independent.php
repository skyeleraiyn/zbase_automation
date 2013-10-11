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
//For backup testcases for enhanced coalescers.

abstract class Backup_Tests_Diskmapper  extends ZStore_TestCase {                             

	public function test_first_upload() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		$flag = enhanced_backup_functions::get_recent_local_backup_name(1);	
		$this->assertFalse($flag, "recent backup found ");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		$this->assertTrue(zbase_backup_setup::start_backup_daemon(TEST_HOST_2), "failed to start daemon");
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
	}


	public function test_delete_local_backup_folder() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf ".ZBASE_DB_LOCAL_BACKUP_FOLDER);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the full backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the first backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the first backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the first backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the first backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the first backup files to Storage Server");
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf ".ZBASE_DB_LOCAL_BACKUP_FOLDER);			
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the second backup files to Storage Server");
		$failure = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED");	
		$error = backup_tools_functions::upload_stat_from_zbasebackup_log("ERROR");	
		$this->assertFalse(strpos($failure,"FAILED") > 0, "Failure message found");
		$this->assertFalse(strpos($error, "ERROR") > 0, "Error message found");

	}





	public function test_delete_local_backup_folder_full_backup() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf ".ZBASE_DB_LOCAL_BACKUP_FOLDER);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		sleep(10);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the full backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the first backup files to Storage Server");
		sleep(10);
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf ".ZBASE_DB_LOCAL_BACKUP_FOLDER);			
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the second backup files to Storage Server");
		$failure = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED");	
		$error = backup_tools_functions::upload_stat_from_zbasebackup_log("ERROR");	
		$this->assertFalse(strpos($failure,"FAILED") > 0, "Failure message found");
		$this->assertFalse(strpos($error, "ERROR") > 0, "Error message found");

	}


	public function test_mapping_first_upload() {
		$count_primary = 0;
		$count_secondary = 0;
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		$flag = enhanced_backup_functions::get_recent_local_backup_name(1);
		$this->assertFalse($flag, "recent backup found ");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");

		$Mapping_SS_1 = storage_server_api::get_config_api(STORAGE_SERVER_1);
		$index = "health_reports";
		unset($Mapping_SS_1[$index]);
		foreach($Mapping_SS_1 as $disk => $values) {
			if($values['primary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_primary++;
			if($values['secondary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_secondary++;
		}

		$Mapping_SS_2 = storage_server_api::get_config_api(STORAGE_SERVER_2);
                $index = "health_reports";
                unset($Mapping_SS_2[$index]);

		foreach($Mapping_SS_2 as $disk => $values) {
			if($values['primary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_primary++;
			if($values['secondary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_secondary++;
		}

		$Mapping_SS_3 = storage_server_api::get_config_api(STORAGE_SERVER_3);
                $index = "health_reports";
                unset($Mapping_SS_3[$index]);

		foreach($Mapping_SS_3 as $disk => $values) {
			if($values['primary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_primary++;
			if($values['secondary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_secondary++;
		}			

		$this->assertEquals($count_primary, 1, "not exactly 1 primary partition assigned");
		$this->assertEquals($count_secondary, 1, "not exactly1 secondary partition assigned");
	}



	public function test_upload_with_full_backup() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		$flag = enhanced_backup_functions::get_recent_local_backup_name(1);	
		$this->assertFalse($flag, "recent backup found ");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
	}


	public function test_mapping_first_upload_with_full_backup() {
		$count_primary = 0;
		$count_secondary = 0;
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		$flag = enhanced_backup_functions::get_recent_local_backup_name(1);
		$this->assertFalse($flag, "recent backup found ");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");

		$Mapping_SS_1 = storage_server_api::get_config_api(STORAGE_SERVER_1);
                $index = "health_reports";
                unset($Mapping_SS_1[$index]);

		foreach($Mapping_SS_1 as $disk => $values) {
			if($values['primary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_primary++;
			if($values['secondary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_secondary++;
		}
		$Mapping_SS_2 = storage_server_api::get_config_api(STORAGE_SERVER_2);
                $index = "health_reports";
                unset($Mapping_SS_2[$index]);

		foreach($Mapping_SS_2 as $disk => $values) {
			if($values['primary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_primary++;
			if($values['secondary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_secondary++;
		}

		$Mapping_SS_3 = storage_server_api::get_config_api(STORAGE_SERVER_3);
                $index = "health_reports";
                unset($Mapping_SS_3[$index]);

		foreach($Mapping_SS_3 as $disk => $values) {
			if($values['primary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_primary++;
			if($values['secondary'] == enhanced_backup_functions::trim_slave_host_name())
			$count_secondary++;
		}			

		$this->assertEquals($count_primary, 1, "not exactly 1 primary partition assigned");
		$this->assertEquals($count_secondary, 1, "not exactly1 secondary partition assigned");
	}




	public function test_valid_mapping() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$local_map = enhanced_backup_functions::get_host_mapping_local();
		$storage_map = diskmapper_functions::get_primary_partition_mapping(enhanced_backup_functions::trim_slave_host_name());
		$this->assertEquals($storage_map['disk'],$local_map['disk'], "mismatch in disk configuration");
		$this->assertEquals($storage_map['storage_server'], $local_map['storage_server'], "mismatch in storage_server config");
	}

	public function test_least_recent_backup_deleted() {
		$max_backup = 5;
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");		
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertLessThanOrEqual(($max_backup + 1), count(enhanced_backup_functions::get_recent_local_backup_name()), "not deleted");
		$this->assertTrue(Data_generation::add_keys(100, 100, 701),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
                $this->assertLessThanOrEqual(($max_backup + 1), count(enhanced_backup_functions::get_recent_local_backup_name()), "not deleted");
	}

	public function test_start_backup_invalid_params() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "ZRT_MAPPER_KEY", '\'KEY_JUNK\'');
		backup_tools_functions::set_backup_const(TEST_HOST_2, "ZRT_RETRIES", 10);
		$this->assertTrue(zbase_backup_setup::start_backup_daemon(TEST_HOST_2),"daemon started with invalid parameters");
		sleep(30);
		$failure = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED");
		$this->assertTrue(strpos($failure,"FAILED: Unable to read from zRuntime") > 0, "Failure message not found in zbasebackup log");
	}

	public function test_upload_retry() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 10000);
		$this->assertTrue(Data_generation::add_keys(160000, 10000, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		sleep(60);
		$log_retry = backup_tools_functions::upload_stat_from_zbasebackup_log("Retrying", 100);
		$log_failed = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED", 10);
		$array_retry = explode("\n",$log_retry);
		$this->assertTrue(strpos($log_failed,"FAILED: Upload to S3 failed for backup file") > 0, "Failure mesage not found");
		$this->assertGreaterThanorEqual(19, count($array_retry), "mismatch in retry count");

	}

	public function test_invalid_DM_between_backups() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "ZRT_MAPPER_KEY", '\'KEY_JUNK\'');	
                backup_tools_functions::set_backup_const(TEST_HOST_2, "ZRT_RETRIES", 10);	
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		$this->assertTrue(zbase_backup_setup::restart_backup_daemon(TEST_HOST_2));
		sleep(30);
		$failure = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED");
		$this->assertTrue(strpos($failure,"FAILED: Unable to read from zRuntime") > 0, "Failure message not found in zbasebackup log");
		remote_function::remote_execution(TEST_HOST_2, "sudo cp ".ZBASE_BACKUP_CONSTANTS_FILE.".org ".ZBASE_BACKUP_CONSTANTS_FILE);
		$this->assertTrue(zbase_backup_setup::restart_backup_daemon(TEST_HOST_2));		

	}

	public function test_upload_to_new_primary() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$storage_map = diskmapper_functions::get_primary_partition_mapping(enhanced_backup_functions::trim_slave_host_name());
		$map_old = $storage_map['storage_server'].":".$storage_map['disk'];
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry"); 
		sleep(60);
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to new disk");
		$storage_map = diskmapper_functions::get_primary_partition_mapping(enhanced_backup_functions::trim_slave_host_name());
		$map_new = $storage_map['storage_server'].":".$storage_map['disk'];
		$this->assertNotEquals($map_old, $map_new, "same mapping");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$log_change = backup_tools_functions::upload_stat_from_zbasebackup_log("Diskmapper hostconfig has changed");
		$log_upload = backup_tools_functions::upload_stat_from_zbasebackup_log("Uploading missing backups");
		$this->assertTrue(strpos($log_change,"Executing localbackup based resolution") > 0, "config change not detected");
		$this->assertTrue(strpos($log_upload, "Uploading missing backups to storage server") > 0, "didn't upload local backups");
	}

	public function test_upload_when_primary_rehydrating() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$storage_map = diskmapper_functions::get_primary_partition_mapping(enhanced_backup_functions::trim_slave_host_name());
		$map_old = $storage_map['storage_server'].":".$storage_map['disk'];
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");
		sleep(15);
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$log_change = backup_tools_functions::upload_stat_from_zbasebackup_log("Diskmapper hostconfig has changed",10);
		$this->assertTrue(strpos($log_change,"Executing localbackup based resolution") > 0, "config change not detected");	
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to new disk");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$log_change_2 = backup_tools_functions::upload_stat_from_zbasebackup_log("Diskmapper hostconfig has changed",10);
		$this->assertTrue(strpos($log_change,"Executing localbackup based resolution") > 0, "config change not detected");
		$change_arr = explode("\n", $log_change_2);
		$this->assertEquals(2, count($change_arr), "local resolution wasn't done after rehydration");
	}


	public function test_delete_from_new_primary() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");
		sleep(15);
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$log_change = backup_tools_functions::upload_stat_from_zbasebackup_log("Diskmapper hostconfig has changed",10);
		$this->assertTrue(strpos($log_change,"Executing localbackup based resolution") > 0, "config change not detected");	
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to new disk");
		$array = enhanced_backup_functions::get_recent_local_backup_name();
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$list_original = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$latest_backup = $array[0];
		$storage_map = diskmapper_functions::get_primary_partition_mapping(enhanced_backup_functions::trim_slave_host_name());
		$path = "/".$storage_map['disk']."/primary/".enhanced_backup_functions::trim_slave_host_name()."/".ZBASE_CLOUD."/incremental/";
		remote_function::remote_execution($storage_map['storage_server'], "sudo rm -rf ".$path.$latest_backup."*");
		$list_deleted = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$list_deleted = implode(" ", $list_deleted);
		$this->assertFalse(stristr($list_deleted, $latest_backup), "backup not deleted");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(60);
		$list_new = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$list_new = implode(" ",$list_new);
		$this->assertFalse(!stristr($list_new, $latest_backup), "deleted backup not re-uploaded");
	}



	public function test_secondary_lagging_recoverable_backups() {
		$max_backups = 5;
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 701),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$list_original = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);		
		$list_local = enhanced_backup_functions::get_recent_local_backup_name();
		$SecMap = diskmapper_functions::get_secondary_partition_mapping(enhanced_backup_functions::trim_slave_host_name()); 
		$path = "/".$SecMap['disk']."/secondary/".enhanced_backup_functions::trim_slave_host_name()."/".ZBASE_CLOUD."/incremental/";
		$command_string = "sudo rm -rf ";
		for($count=0;$count<($max_backups - 2);$count++)
			$command_string.=$path.$list_local[$count]."* ";
		remote_function::remote_execution($SecMap['storage_server'], $command_string);
		$list_deleted = directory_function::list_files_recursive($path, $SecMap['storage_server']);
		$list_deleted = implode(" ",$list_deleted);
		$flag = False;
		for($count = 0; $count<($max_backups - 2);$count++) {
			if(stristr($list_deleted, $list_local[$count])) {
				$flag = True;
				break;
			}
		}
		$this->assertFalse($flag, "backups not deleted");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");
		sleep(15);
		$this->assertTrue(Data_generation::add_keys(100, 100, 801),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),300) , "Failed to copy file to secondary disk");
		$list_new = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$list_new = implode(" ", $list_new);
		$flag = False;
		for($count = 0; $count<($max_backups - 3);$count++) {
			if(!stristr($list_new, $list_local[$count])) {
				$flag = True;
				break;
			}
		}
		$this->assertFalse($flag, "Deleted backups not re uploaded");
	}



	public function test_secondary_lagging_maximum_possible_recovery() {
		$max_backups = 5;
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 701),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$list_original = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);		
		$list_local = enhanced_backup_functions::get_recent_local_backup_name();
		$SecMap = diskmapper_functions::get_secondary_partition_mapping(enhanced_backup_functions::trim_slave_host_name()); 
		$path = "/".$SecMap['disk']."/secondary/".enhanced_backup_functions::trim_slave_host_name()."/".ZBASE_CLOUD."/incremental/";
		$command_string = "sudo rm -rf ";
		for($count=0;$count<($max_backups - 1);$count++)
		$command_string.=$path.$list_local[$count]."* ";
		remote_function::remote_execution($SecMap['storage_server'], $command_string);
		$list_deleted = directory_function::list_files_recursive($path, $SecMap['storage_server']);
		$list_deleted = implode(" ",$list_deleted);
		$flag = False;
		for($count = 0; $count<($max_backups - 1);$count++) {
			if(stristr($list_deleted, $list_local[$count])) {
				$flag = True;
				break;
			}
		}
		$this->assertFalse($flag, "backups not deleted");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");
		sleep(15);
		$this->assertTrue(Data_generation::add_keys(100, 100, 801),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),300) , "Failed to copy file to secondary disk");
		$list_new = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$list_new = implode(" ", $list_new);
		$flag = False;
		for($count = 0; $count<($max_backups - 1);$count++) {
			if(!stristr($list_new, $list_local[$count])) {
				$flag = True;
				break;
			}
		}
		$this->assertFalse($flag, "Deleted backups not re uploaded");
	}



	public function test_secondary_lagging_unretrievable_backups() {
		$max_backups = 5;
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 701),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$list_original = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$list_local = enhanced_backup_functions::get_recent_local_backup_name();
		$SecMap = diskmapper_functions::get_secondary_partition_mapping(enhanced_backup_functions::trim_slave_host_name()); 
		$path = "/".$SecMap['disk']."/secondary/".enhanced_backup_functions::trim_slave_host_name()."/".ZBASE_CLOUD."/incremental/";
		$command_string = "sudo rm -rf ";
		for($count=0;$count<($max_backups);$count++)
		$command_string.=$path.$list_local[$count]."* ";
		remote_function::remote_execution($SecMap['storage_server'], $command_string);
		$list_deleted = directory_function::list_files_recursive($path, $SecMap['storage_server']);
		$list_deleted = implode(" ",$list_deleted);
		$flag = False;
		for($count = 0; $count<($max_backups - 1);$count++) {
			if(stristr($list_deleted, $list_local[$count])) {
				$flag = True;
				break;
			}
		}
		$this->assertFalse($flag, "backups not deleted");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),300) , "Failed to copy file to secondary disk");
		sleep(15);
		$this->assertTrue(Data_generation::add_keys(100, 100, 801),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$list_new = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$list_new = implode(" ", $list_new);
		$flag = False;
		for($count = 0; $count<($max_backups - 1);$count++) {
			if(stristr($list_new, $list_local[$count])) {
				$flag = True;
				break;
			}
		}
		$this->assertFalse($flag, "Deleted backups re uploaded when lagging irrecoverable number of backups");
		sleep(60);
		$log = backup_tools_functions::upload_stat_from_zbasebackup_log($list_local[$max_backups], 4);		
		$this->assertTrue(strpos($log,"SUCCESS: Uploading ") > 0, "Upload success message not found in zbasebackup log");
		$this->assertTrue(strpos($log,"Removing local backup ".$list_local[$max_backups]) > 0, "Local backup removal message not found in zbasebackup log");
		$this->assertTrue(strpos($log,"ERROR: Unable to find local copy of backup : ".$list_local[$max_backups]) > 0, "Local backup not present message not found in zbasebackup log");
	}


	public function test_primary_down_while_uploading() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");		
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$latest_backup = enhanced_backup_functions::get_recent_local_backup_name(1);
		$missing = backup_tools_functions::upload_stat_from_zbasebackup_log("Uploading missing backups");
		$this->assertTrue(strpos($missing,"Uploading missing backups") > 0, "previous backups not re uploaded");
		$log = backup_tools_functions::upload_stat_from_zbasebackup_log($latest_backup, 2);
		$this->assertTrue(strpos($log,"SUCCESS: Uploading") > 0, "latest backups not uploaded");
	}

	public function test_upload_when_secondary_down() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 301),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 401),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 501),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(enhanced_backup_functions::trim_slave_host_name(),180) , "Failed to copy file to secondary disk");
		$this->assertTrue(Data_generation::add_keys(100, 100, 601),"Failed adding keys");
		$this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'secondary'),"Failed adding bad disk entry");
		sleep(15);
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$log_change = backup_tools_functions::upload_stat_from_zbasebackup_log("Diskmapper hostconfig has changed");
		$log_upload = backup_tools_functions::upload_stat_from_zbasebackup_log("Uploading missing backups");
		$this->assertFalse(strpos($log_change,"Diskmapper hostconfig") > 0, "Config change noted by daemon");
		$this->assertFalse(strpos($log_upload,"Uploading missing") > 0, "Uploaded local backups");
	}

	public function test_upload_when_primary_and_secondary_down() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
                $this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
                zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                sleep(10);
                $this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'primary'),"Failed adding bad disk entry");
                $this->assertTrue(diskmapper_functions::add_bad_disk(enhanced_backup_functions::trim_slave_host_name(),'secondary'),"Failed adding bad disk entry");
		sleep(15);
                $this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		sleep(60);
		$log_failure = backup_tools_functions::upload_stat_from_zbasebackup_log("Both primary and secondary disks are bad");
		$this->assertContains("FAILED: Upload to S3", $log_failure, "Failure message not found when primary and secondary went down");
		
	}
	
	public function test_full_backup_non_empty_storage_server() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
                $this->assertTrue(Data_generation::add_keys(100, 100),"Failed adding keys");
                zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $this->assertTrue(Data_generation::add_keys(100, 100, 101),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $this->assertTrue(Data_generation::add_keys(100, 100, 201),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "stop");
                tap_commands::deregister_replication_tap_name(TEST_HOST_1);
                zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
                tap_commands::deregister_replication_tap_name(TEST_HOST_2);
		zbase_setup::reset_zbase_servers(array(TEST_HOST_2));
		zbase_backup_setup::clear_zbase_backup_log_file(TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "inconsistent_slave_chk", "true");
		tap_commands::register_backup_tap_name(TEST_HOST_2);
                vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(10);
                zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		sleep(10);
		$failure = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED: Location ");
		$this->assertFalse(stristr($failure,"not empty"), "failure message found");
		$full_backup = backup_tools_functions::upload_stat_from_zbasebackup_log("BACKUP SUMMARY: type:full");
		$this->assertContains("BACKUP SUMMARY: type:f", $full_backup, "full backup summary message not found");
		$success = backup_tools_functions::upload_stat_from_zbasebackup_log("SUCCESS: Uploading");
		$this->assertContains("done", $success, "done file upload not found in log");

	}
}

class Backup_Tests_Diskmapper_Full extends Backup_Tests_Diskmapper {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}


