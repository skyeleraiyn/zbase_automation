<?php


abstract class IBR_CoreMerge_TestCase extends ZStore_TestCase {

	public function test_One_Incremental() {
			#AIM // Run core merge with only 1 incremental backup containing unique mutations 
			#EXPECTED RESULT // Ensure that output backup file is the same as input

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);	
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, 20), "Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");		
		$array = storage_server_functions::list_master_backups();
		storage_server_functions::set_input_file_merge($array);
		storage_server_functions::run_core_merge_script();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count_slave, $count_merge, "Key_count_mismatch in merged file");

		$instance = Connection::getMaster();
		$machine_value = $instance->get("testkey_1");

		$merge_value = sqlite_functions::sqlite_select_uniq(STORAGE_SERVER_1, "val", "cpoint_op", TEMP_OUTPUT_FILE_0);
		$this->assertEquals($machine_value, $merge_value, "Key values does not match");
		
	}

	public function test_Latest_Vals() {
			#AIM // Run core merge script on 3 backup files. The first backup file contains 5k unique set operations. 
				//The second and third incremental backups contain set mutations for the same 5k keys
			#EXPECTED RESULT // The merged file contains only the values of the latest backup

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(5000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");				
		$this->assertTrue(Data_generation::add_keys(5000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");		
		$this->assertTrue(Data_generation::add_keys(5000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");		
		$array = storage_server_functions::list_incremental_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		storage_server_functions::run_core_merge_script();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertEquals("5000", $count_merge, "Key_count_mismatch in merged file");
		$instance = Connection::getMaster();
		$machine_value = $instance->get("testkey_1");
		$merge_value = sqlite_functions::sqlite_select_uniq(STORAGE_SERVER_1, "val", "cpoint_op", TEMP_OUTPUT_FILE_0);
		$this->assertEquals($machine_value, $merge_value, "Key values does not match");
	}

	public function test_Three_Incr() {
			#AIM // Run core merge script on 3 backup files, each of size 1 GB, containing all unique mutations
			#EXPECTED RESULT // Ensure that the merged file is of size ~3GB and contains all they keys present in the input incremental files.

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);	
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 1, 102400),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload());	
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 10001, 102400),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload());		
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 20001, 102400),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload());	
		$array = storage_server_functions::list_incremental_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		storage_server_functions::run_core_merge_script();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count_slave, $count_merge, "Key_count_mismatch in merged file");
		$size = backup_tools_functions::get_backup_size(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertGreaterThanOrEqual(3060164199, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(3274912564, $size, "Backup database with greater size than expected");		
	}

	
	public function test_Set_Delete() {
			#AIM // Run core merge script on 2 backup files, the first one containing set mutations for about 5k keys and the second incremental backup containing 5k deletes for the same keys
			#EXPECTED RESULT // Merged Backup contains only delete mutations

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(5000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");		
		$this->assertTrue(Data_generation::delete_keys(5000, 1, 1000));
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");		
		$array = storage_server_functions::list_incremental_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		storage_server_functions::run_core_merge_script();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertEquals("5000", $count_merge, "Key_count_mismatch in merged file");
		$del_mutations = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"count(*)", "cpoint_op where op='d' ", TEMP_OUTPUT_FILE_0);
		$set_mutations = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"count(*)", "cpoint_op where op='m' ", TEMP_OUTPUT_FILE_0);
		$this->assertEquals($del_mutations, 5000, "Delete mutations not available in backup");
		$this->assertEquals($set_mutations, 0, "Deletion of keys not done properly");

	}

	public function test_Delete_Set() {
			#AIM // Run core merge script on 2 backup files, the first one containing 5k deletes and the second incremental backup containing set mutations for about 5k keys for the same keys
			#EXPECTED RESULT // Output backup file contains 5k mutations with values as set in the second backup

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(5000, 1000, 1, 20),"Failed adding keys");
		$this->assertTrue(Data_generation::delete_keys( 5000, 1, 1000));
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");				
		$this->assertTrue(Data_generation::add_keys(5000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Uploading backups failed");		
		$array = storage_server_functions::list_incremental_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		storage_server_functions::run_core_merge_script();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertEquals("5000", $count_merge, "Key_count_mismatch in merged file");
		$del_mutations = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"count(*)", "cpoint_op where op='d' ", TEMP_OUTPUT_FILE_0);
		$set_mutations = sqlite_functions::sqlite_select(STORAGE_SERVER_1,"count(*)", "cpoint_op where op='m' ", TEMP_OUTPUT_FILE_0);
		$this->assertEquals($del_mutations, 0, "Delete mutations not available in backup");
		$this->assertEquals($set_mutations, 5000, "Deletion of keys not done properly");

		$instance = Connection::getMaster();
		$machine_value = $instance->get("testkey_1");

		$merge_value = sqlite_functions::sqlite_select_uniq(STORAGE_SERVER_1, "val", "cpoint_op", TEMP_OUTPUT_FILE_0);

	}

	public function est_Two_Backups_Single_Chkpoint() { // need to be investigated gets stuck
			#AIM // Run core merge on 2 incremental backup files that have only a single checkpoint
			#EXPECTED RESULT // Only one chekpoint in the output merge file

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 3600);
		$this->assertTrue(Data_generation::add_keys( 1500, 500000, 1, 10240),"Failed adding keys");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 10);
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		storage_server_functions::run_core_merge_script();
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_slave, $count_merge, "Key_count_mismatch in backup");
		//Check if last closed checkpoint on slave and backup are the same
		$chk_point_master = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$chk_point_merge = zbase_function::sqlite_chkpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_master, $chk_point_merge, "Checkpoint_mismatch in backup");
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 1024);

	}

	public function test_Split_Size_Check() {
			#AIM // Verify that split files being generated according to the split_size parameter specified.
			#EXPECTED RESULT // Splitting is done properly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2500);
		$this->assertTrue(Data_generation::add_keys( 2500, 2500, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		storage_server_functions::run_core_merge_script(True, "10");
		$size =(integer)backup_tools_functions::get_backup_size(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertGreaterThanOrEqual(9437184, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(110100480, $size, "Backup database with greater size than expected");
	}

	public function test_Unordered_Input() {
			#AIM // Provide the input backup files in the input.txt file in the wrong order and verify behavior of the script.
			#EXPECTED RESULT // Error message while the merge script is run

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 3001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 6001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_incremental_backups();
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		storage_server_functions::set_input_file_merge($array, 'a');
		$status = storage_server_functions::run_core_merge_script();
		$this->assertTrue(strpos($status, "ERROR: Checkpoint mismatch in file")>=0, "Merge done despite input files being in wrong order");

	}

	public function test_Validate_Missing_Chkpoints() {
		#AIM // Run core merge script with the validate option and ensure that any missing checkpoints in the input files are caught.
		#EXPECTED RESULT // Error message while the merge script is run

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 3001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 6001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_incremental_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		storage_server_functions::delete_input_file_entry(2);
		$status = storage_server_functions::run_core_merge_script();		
		$this->assertTrue(strpos($status, "ERROR: Checkpoint mismatch in file")>=0, "Merge done despite input files being in wrong order");

	}

	public function test_Missing_Chkpoints_Without_Validate() {
			#AIM // Run core merge script without the validate option and ensure that any missing checkpoints in the input files are not caught.
			#EXPECTED RESULT // Merge happens despite missing checkpoints

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 3001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 6001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array);

		$array = storage_server_functions::list_incremental_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		storage_server_functions::delete_input_file_entry(2);
		$status = storage_server_functions::run_core_merge_script(False);
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, TEMP_OUTPUT_FILE_0);
		$this->assertEquals("6000", $count_merge, "Key_count_mismatch in backup");
		$this->assertTrue(strpos($status, "Creating backup file - ".TEMP_OUTPUT_FILE_0."")>=0, "Merge not done despite disabling validate option");

	}

	public function est_Corrupt_Sqlite_File() {
			#AIM // Run core merge with corrupt incremental files. The sqlite file can be corrupted by introducing foreign characters in the SQLite header
			#EXEPECTED RESULT // Introducing foreign characters in the SQLite header renders the file unreadable by sqlite

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(STORAGE_SERVER_1);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 3001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_incremental_backups();
		sqlite_functions::corrupt_sqlite_file(STORAGE_SERVER_1, $array[0]);
		rsort($array);
		storage_server_functions::set_input_file_merge($array);
		$array = storage_server_functions::list_master_backups();
		rsort($array);
		storage_server_functions::set_input_file_merge($array, 'a');
		$status = storage_server_functions::run_core_merge_script();
		$this->assertTrue(strpos($status, "ERROR: Unable to open file")>=0, "Merge not done despite disabling validate option"); // check: should it check for errors or not ?

	}

}

class IBR_CoreMerge_TestCase_Full extends IBR_CoreMerge_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
