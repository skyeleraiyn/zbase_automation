<?php
abstract class DI_Negative_IBR_TestCase extends Zstore_TestCase {

	// Aim : Corrupt a backup file and ensure error is caught on restore

	public function test_DI_master_restore_negative() {
		
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(600, 100, 1, 10), "Failed adding keys");
		mb_backup_commands::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$backup_array = mb_backup_commands::list_master_backups();
		foreach($backup_array as $backup) {
			sqlite_functions::sqlite_update(STORAGE_SERVER, "val", "cpoint_op", $backup, "new_val");
		}
		membase_function::reset_membase_servers(array(TEST_HOST_1, TEST_HOST_2));
		mb_restore_commands::restore_server(TEST_HOST_2);
		sleep(15);	
		$out=trim(remote_function::remote_execution(TEST_HOST_2, " grep -c 'Checksum verification failed' ".MEMBASE_LOG_FILE));
		$this->assertEquals($out, 600,"Checksum verification failed");	
	}

	public function test_DI_incremental_restore_negative() {
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(600, 100, 1, 10));
		mb_backup_commands::start_backup_daemon_full(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(600, 100, 601, 10));
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$incr_array=mb_backup_commands::list_incremental_backups();
		rsort($incr_array);
		mb_backup_commands::set_input_file_merge($incr_array);
		foreach($incr_array as $backup) {
			sqlite_functions::sqlite_update(STORAGE_SERVER, "val", "cpoint_op", $backup, "new_val");
		}
		$out = mb_backup_commands::run_core_merge_script();
		$this->assertFalse(stristr($out,"fail"),"core merge failed");
	}

	public function test_DI_negative_replication() {
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "100");
		Data_generation::add_keys(600, 100, 1, 10240);
		foreach(unserialze(MEMBASE_DATABASE_PATH) as $membase_dbpath){
			for($i=0;$i<4;$i++) {
				sqlite_functions::sqlite_update(TEST_HOST_1, "v", "kv", $membase_dbpath."/ep.db-$i.sqlite", "new_val");
			}
		}
		membase_function::restart_membase_servers(TEST_HOST_1);
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		tap_commands::register_replication_tap_name(TEST_HOST_1, " -l 0 -b");
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		$out=trim(remote_function::remote_execution(TEST_HOST_2, " grep -c 'Checksum verification failed' ".MEMBASE_LOG_FILE));
		$this->assertEquals($out,600,"Checksum verification failed");
	}

	public function test_DI_negative_replication_with_get() {
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "100");
		Data_generation::add_keys(600, 100, 1, 10240);
		foreach(unserialze(MEMBASE_DATABASE_PATH) as $membase_dbpath){
			for($i=0;$i<4;$i++) {
				sqlite_functions::sqlite_update(TEST_HOST_1, "v", "kv", $membase_dbpath."/ep.db-$i.sqlite", "new_val");
			}
		}
		membase_function::restart_membase_servers(TEST_HOST_1);
		$instance = Connection::getMaster();		
		for($key=1;$key<=600;$key++) {
			@$instance->get("testkey_".$key);
		}
		vbucketmigrator_function::add_slave_machine_sysconfig_file(TEST_HOST_1, TEST_HOST_2);
		tap_commands::register_replication_tap_name(TEST_HOST_1, " -l 0 -b");
		vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		$out=trim(remote_function::remote_execution(TEST_HOST_2, " grep -c 'Checksum verification failed' ".MEMBASE_LOG_FILE));
		$this->assertEquals($out,600,"Checksum verification failed");
	}

	// Low Priority test cases involving cross build installations

/*
	public function test_merge_backups_without_checksum(){
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"without_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"without_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER,"with_checksum", True, True);
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10, False));
		mb_backup_commands::start_backup_daemon_full(TEST_HOST_2);
		$i=0; while(1) {
			$log_out = mb_backup_commands::poll_membase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		$master_backup=mb_backup_commands::list_master_backups();
		mb_backup_commands::set_input_file_merge($master_backup);
		$out = mb_backup_commands::run_core_merge_script();
		$this->assertFalse(stristr($out, "fail"), "merge failed");
		$output_arr = file_function::list_files_in_path(STORAGE_SERVER, "/tmp/test", "mbb");
		foreach($output_arr as $output) {
			$string = sqlite_functions::sqlite_select(STORAGE_SERVER,"cksum","cpoint_op", $output);
			$this->assertEquals(strlen($string), 0, "merged files checksum not empty");
		}
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"with_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"with_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER,"with_checksum", True, True);
	}

	public function test_merge_cross_build_backups(){
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"without_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"without_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER,"with_checksum", True, True);
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 100, 0, False));
		mb_backup_commands::start_backup_daemon_full(TEST_HOST_2);
		$i=0; while(1) {
			$log_out = mb_backup_commands::poll_membase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		mb_backup_commands::copy_master_backups();
		$master_backup=mb_backup_commands::list_master_backups_copy();
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"with_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"with_checksum", True, True);
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 100));
		mb_backup_commands::start_backup_daemon_full(TEST_HOST_2);
		$i=0; while(1) {
			$log_out = mb_backup_commands::poll_membase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 100));
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$i=0; while(1) {
			$log_out = mb_backup_commands::poll_membase_backup_log_file();
			if(stristr($log_out, "Removing file") and stristr($log_out, ".done")) 
				break;
			sleep(2);
		}
		$incr_backup= mb_backup_commands::list_incremental_backups();
		mb_backup_commands::set_input_file_merge($incr_backup);
		mb_backup_commands::set_input_file_merge($master_backup,'a');
		$out = mb_backup_commands::run_core_merge_script();
		$this->assertFalse(stristr($out, "fail"), "merge failed");
		$output_arr = file_function::list_files_in_path(STORAGE_SERVER, "/tmp/test", "mbb");
		foreach($output_arr as $output) {
			$string = sqlite_functions::sqlite_select(STORAGE_SERVER,"cksum","cpoint_op where key like 'testkey_200'", $output);
			$this->assertEquals(strlen($string), 0, "empty checksum not retained");
			$string = sqlite_functions::sqlite_select(STORAGE_SERVER,"cksum","cpoint_op where key like 'testkey_201'", $output);
			$this->assertNotEquals(strlen($string), 0, "checksum not retained");
		}
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_1,"with_checksum", True, False);
		Functional_test::install_rpms_for_di_cross_build_test(TEST_HOST_2,"with_checksum", True, True);
		Functional_test::install_rpms_for_di_cross_build_test(STORAGE_SERVER,"with_checksum", True, True);
	}
*/
}

class DI_Negative_IBR_TestCase_Full extends DI_Negative_IBR_TestCase{
	public function keyProvider() {  
		return Utility::provideKeys();
	}
}
?>
