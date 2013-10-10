<?php
abstract class DI_Negative_IBR_TestCase extends Zstore_TestCase {

	// Aim : Corrupt a backup file and ensure error is caught on restore

	public function test_DI_master_restore_negative() {
		
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(600, 100, 1, 10), "Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$backup_array = storage_server_functions::list_master_backups();
		foreach($backup_array as $backup) {
			sqlite_functions::sqlite_update(STORAGE_SERVER_1, "val", "cpoint_op", $backup, "new_val");
		}
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		mb_restore_commands::restore_server(TEST_HOST_2);
		sleep(5);	// need delay to populate zbase.log file
		$out=trim(remote_function::remote_execution(TEST_HOST_2, " grep -c 'Checksum verification failed' ".ZBASE_LOG_FILE));
		$this->assertEquals($out, 600,"Checksum verification failed");	
	}

	public function test_DI_incremental_restore_negative() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(600, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(600, 100, 601, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$incr_array=storage_server_functions::list_incremental_backups();
		rsort($incr_array);
		storage_server_functions::set_input_file_merge($incr_array);
		foreach($incr_array as $backup) {
			sqlite_functions::sqlite_update(STORAGE_SERVER_1, "val", "cpoint_op", $backup, "new_val");
		}
		$out = storage_server_functions::run_core_merge_script();
		$this->assertFalse(stristr($out,"fail"),"core merge failed");
	}

	public function test_DI_negative_replication() {
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "100");
		Data_generation::add_keys(600, 100, 1, 10240);
		$ep_dbshards = stats_functions::get_all_stats(TEST_HOST_1, "ep_dbshards");
		foreach(unserialize(ZBASE_DATABASE_PATH) as $zbase_dbpath){
			for($i=0;$i<$ep_dbshards;$i++) {
				sqlite_functions::sqlite_update(TEST_HOST_1, "v", "kv", $zbase_dbpath."/ep.db-$i.sqlite", "new_val");
			}
		}
		zbase_setup::restart_zbase_servers(TEST_HOST_1);
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		tap_commands::register_replication_tap_name(TEST_HOST_1, " -l 0 -b");
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		$out=trim(remote_function::remote_execution(TEST_HOST_2, " grep -c 'Checksum verification failed' ".ZBASE_LOG_FILE));
		$this->assertEquals($out,600,"Checksum verification failed");
	}

	public function test_DI_negative_replication_with_get() {
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "min_data_age", 0);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "100");
		Data_generation::add_keys(600, 100, 1, 10240);
		$ep_dbshards = stats_functions::get_all_stats(TEST_HOST_1, "ep_dbshards");
		foreach(unserialize(ZBASE_DATABASE_PATH) as $zbase_dbpath){
			for($i=0;$i<$ep_dbshards;$i++) {
				sqlite_functions::sqlite_update(TEST_HOST_1, "v", "kv", $zbase_dbpath."/ep.db-$i.sqlite", "new_val");
			}
		}
		zbase_setup::restart_zbase_servers(TEST_HOST_1);
		$instance = Connection::getMaster();		
		for($key=1;$key<=600;$key++) {
			@$instance->get("testkey_".$key);
		}
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		tap_commands::register_replication_tap_name(TEST_HOST_1, " -l 0 -b");
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		$out=trim(remote_function::remote_execution(TEST_HOST_2, " grep -c 'Checksum verification failed' ".ZBASE_LOG_FILE));
		$this->assertEquals($out,600,"Checksum verification failed");
	}

	// Low Priority test cases involving cross build installations

/*
	public function test_merge_backups_without_checksum(){
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"without_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"without_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER_1,"with_checksum", True, True);
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10, False));
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$i=0;			$log_out = backup_tools_functions::poll_zbase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		$master_backup=storage_server_functions::list_master_backups();
		storage_server_functions::set_input_file_merge($master_backup);
		$out = storage_server_functions::run_core_merge_script();
		$this->assertFalse(stristr($out, "fail"), "merge failed");
		$output_arr = file_function::list_files_in_path(STORAGE_SERVER_1, "/tmp/test", "mbb");
		foreach($output_arr as $output) {
			$string = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"cksum","cpoint_op", $output);
			$this->assertEquals(strlen($string), 0, "merged files checksum not empty");
		}
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"with_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"with_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER_1,"with_checksum", True, True);
	}

	public function test_merge_cross_build_backups(){
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"without_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"without_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER_1,"with_checksum", True, True);
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 100, 0, False));
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$i=0;			$log_out = backup_tools_functions::poll_zbase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		storage_server_functions::copy_master_backups();
		$master_backup=storage_server_functions::list_master_backups_copy();
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"with_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"with_checksum", True, True);
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 100));
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		$i=0;			$log_out = backup_tools_functions::poll_zbase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 100));
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$i=0;			$log_out = backup_tools_functions::poll_zbase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		$incr_backup= storage_server_functions::list_incremental_backups();
		storage_server_functions::set_input_file_merge($incr_backup);
		storage_server_functions::set_input_file_merge($master_backup,'a');
		$out = storage_server_functions::run_core_merge_script();
		$this->assertFalse(stristr($out, "fail"), "merge failed");
		$output_arr = file_function::list_files_in_path(STORAGE_SERVER_1, "/tmp/test", "mbb");
		foreach($output_arr as $output) {
			$string = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"cksum","cpoint_op where key like 'testkey_200'", $output);
			$this->assertEquals(strlen($string), 0, "empty checksum not retained");
			$string = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"cksum","cpoint_op where key like 'testkey_201'", $output);
			$this->assertNotEquals(strlen($string), 0, "checksum not retained");
		}
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"with_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"with_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER_1,"with_checksum", True, True);
	}
*/
}

class DI_Negative_IBR_TestCase_Full extends DI_Negative_IBR_TestCase{
	public function keyProvider() {  
		return Utility::provideKeys();
	}
}
?>
