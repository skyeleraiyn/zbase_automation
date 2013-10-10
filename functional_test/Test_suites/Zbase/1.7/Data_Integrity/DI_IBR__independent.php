<?php

abstract class Data_Integrity_IBR extends ZStore_TestCase {

	public function test_Verify_Backup_With_Checksum()      {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$schema = sqlite_functions::sqlite_schema(STORAGE_SERVER_1, $array[0]);
		$status = stristr($schema,"cksum varchar(100)") ;
		$this->assertNotNull($status, "Checksum column not available in the backup taken");

	}	

	public function est_Verify_Cksum_Backup_Vs_DB()	{
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$backup_checksum_array = array();
		$backup_checksum_array = explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op", $array[0])); // check: Fails at this step. Master file has not cksum column
		$db_checksum_array = zbase_function::db_sqlite_select(TEST_HOST_2, "cksum", "kv");
		$diff_array = array_diff($backup_checksum_array, $db_checksum_array);
		$this->assertEquals(count($diff_array), 0, "Checksums across DB and Backups do not match");
		
	}

	public function test_Verify_Backup_With_Checksum_Full_Backup()	{
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		$schema = sqlite_functions::sqlite_schema(STORAGE_SERVER_1, $array[0]);
		$status = stristr($schema,"cksum varchar(100)") ;
		$this->assertNotNull($status, "Checksum column not available in the backup taken");
		$count = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[0]);
		$this->assertEquals($count, 100, "No of mutations in the backups don't add up");
		
	}

	public function test_Restore_With_Checksum_Enabled_Backup()	{
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		backup_tools_functions::clear_backup_data(TEST_HOST_2);
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		sleep(10);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "min_data_age", 0);
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 100, "Number of restored keys not equal to number of keys in backups");	
		//Comparing values across backup and actual db
		$array = storage_server_functions::list_master_backups();
		$backup_checksum_array = array();
		$backup_checksum_array = explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op", trim($array[0])));
		$db_checksum_array = zbase_function::db_sqlite_select(TEST_HOST_2, "cksum", "kv");
		$diff_array = array_diff($backup_checksum_array, $db_checksum_array);
		$this->assertEquals(count($diff_array), 0, "Checksums across DB and Backups do not match");	
	}

	public function test_Daily_Merge_Backups_With_Checksum()	{
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Taking 3 backups to have 1 master and 2 incremental backups.
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 101, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::create_lock_file();
		sleep(30);
                storage_server_functions::edit_date_folder(-7, "master");
		$status=storage_server_functions::run_daily_merge();
		sleep(60);
		$this->assertTrue(strpos($status, "Merge complete")>=0, "Daily Merge failed");
		$list_daily_backups = storage_server_functions::list_daily_backups();
		sleep(10);
		$list_incremental_backup = storage_server_functions::list_incremental_backups();
		sleep(10);
		$temp_array = array();
		foreach($list_daily_backups as $k => $v){
			$daily_checksums = array_merge($temp_array, explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op",  trim($v))));
			$temp_array = $daily_checksums;	
		}
		$temp_array = array();
		foreach($list_incremental_backup as $k => $v){
			$incremental_checksums = array_merge($temp_array, explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op",  trim($v)))); 
			$temp_array = $incremental_checksums;
		}
		sort($incremental_checksums);
		sort($daily_checksums);
		$diff_array = array_diff($incremental_checksums, $daily_checksums);
		$this->assertEquals(count($diff_array), 0, "Checksums across incremental and daily backups do not match");
		
	}

	public function test_Master_Merge_Backups_With_Checksum()	{ 
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		//Taking 3 backups to have 1 master and 2 incremental backups.
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 101, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$list_master_backups =  storage_server_functions::list_master_backups();
		$list_incremental_backups = storage_server_functions::list_incremental_backups();
		$temp_array = array();
		foreach($list_master_backups as $k => $v)        {
			$input_checksums = array_merge($temp_array, explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op",  trim($v))));
			$temp_array = $input_checksums;
		}
		foreach($list_incremental_backups as $k => $v)        {
			$input_checksums = array_merge($temp_array, explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op",  trim($v))));
			$temp_array = $input_checksums;
		}
		sort($input_checksums);
		//Renaming the existing master dir to an older date to aviod confusions.
		$new_date = storage_server_functions::edit_date_folder(-7,"master");
		$status=storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>=0, "Daily Merge failed");
		sleep(30);
		$status = storage_server_functions::run_master_merge();
		sleep(50);
		$this->assertTrue(strpos($status, "Success: Master merge completed")>=0, "Master Merge not done");
		directory_function::delete_directory($new_date, STORAGE_SERVER_1);
		$merged_master_list = storage_server_functions::list_master_backups();
		$temp_array = array();
		foreach($merged_master_list as $k => $v)        {
			$output_checksums = array_merge($temp_array, explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op",  trim($v))));
			$temp_array = $output_checksums;
		}
		sort($output_checksums);
		$diff_array = array_diff($output_checksums, $input_checksums);
		$this->assertEquals(count($diff_array), 0, "Checksums across input and master_merge files do not match");
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

}

class Data_Integrity_Full extends Data_Integrity_IBR{

	public function keyProvider() {
		return Data_generation::provideKeys();
	}

}

?>
