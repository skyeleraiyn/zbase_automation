<?php
/* This suite is supported for CentOS 6*/

abstract class Multi_KVStore_Functional_Master_Slave_Test extends ZStore_TestCase {

	public function test_Incremental_Backup_Restore(){				
		// Incremental backups and restore on a server running multi kv store
		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(2000, 100, 1, "testvalue"), "adding keys failed");
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		// Take incremental backup using backup daemon
		backup_tools_functions::clear_backup_data(TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		membase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		// Kill vbucket migrator between master and slave
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		membase_setup::memcached_service(TEST_HOST_2, "stop");
		remote_function::remote_execution(TEST_HOST_2,"sudo rm -rf /data_*/membase/*");
		membase_setup::memcached_service(TEST_HOST_2, "start");
		// Perform restore
		mb_restore_commands::restore_server(TEST_HOST_2);
		//Attempt get on all keys
		$this->assertTrue(Data_generation::verify_added_keys(TEST_HOST_2, 2000, "testvalue", 1), "verifying keys failed");
		// The below step causes SERVER ERROR
		//$this->assertTrue(Data_generation::verify_added_keys(TEST_HOST_2, 1, "testvalue", 2001), "verifying keys failed");
	}

	public function test_Backfill_Based_Replication_Master_to_Slave(){
		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 1, "testvalue"), "adding keys failed");
		//Kill vbucket migrator between master and slave
		$status = service_function::control_service(TEST_HOST_1, VBUCKETMIGRATOR_SERVICE, "stop");
		//Deregister replication tap
		$status = tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		membase_setup::reset_membase_servers(array(TEST_HOST_2));
		//Register backfill based replication tap
		sleep(15);
		$status = tap_commands::register_replication_tap_name(TEST_HOST_1," -l 0 -b");
		$status = vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(30);
		$this->assertTrue(Data_generation::verify_added_keys(TEST_HOST_2, 3000, "testvalue", 1), "verifying keys failed");
	}

	public function test_Backfill_Based_Backup_on_Slave(){
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, "testvalue"), "adding keys failed");
		sleep(30);
# Ensure that keys are replicated to the slave
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		// Take incremental backup using backup daemon
		membase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		membase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		storage_server_setup::clear_storage_server();
		membase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		// Verfiy correctness of backups uploaded
		//Comparing values across backup and actual db
		$array = storage_server_functions::list_master_backups();
		$backup_checksum_array = array();
		$backup_checksum_array =  explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER_1, "cksum", "cpoint_op", trim($array[0])));
		$db_checksum_array = membase_function::db_sqlite_select(TEST_HOST_2, "cksum", "kv");
		for($i = 0; $i< count($backup_checksum_array) ; $i++)
		$backup_checksum_array[$i] = trim($backup_checksum_array[$i]);
		for($i=0;$i< count ($db_checksum_array) ; $i++)
		$db_checksum_array[$i] = trim($db_checksum_array[$i]);
		$diff_array = array_diff($backup_checksum_array, $db_checksum_array);
		$this->assertEquals(count($diff_array), 0, "Checksums across DB and Backups do not match");

	}

}

class Multi_KVStore_Functional_Master_Slave_Test_Full extends Multi_KVStore_Functional_Master_Slave_Test{
	public function keyProvider() {
		return Utility::provideKeys();
	}

}

