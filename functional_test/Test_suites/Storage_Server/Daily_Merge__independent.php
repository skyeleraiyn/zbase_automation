<?php

abstract class DailyMerge_TestCase extends ZStore_TestCase {

	public function test_Simple_Merge() { 
		#AIM // Run daily merge with a few incremental files and their corresponding .split files
		#EXPECTED RESULT // Merge takes place properly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		storage_server_functions::run_daily_merge();
		$array = storage_server_functions::list_daily_backups();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[0]);
		$this->assertEquals("4000", $count_merge, "Key_count_mismatch in merged file");
	
	}

	public function test_Single_Incremental_Merge() {
		#AIM // Run daily merge with only one incremental backup
		#EXPECTED RESULT // Merge takes place properly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		Data_generation::add_keys(2000, 1000, 2001, 20);
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		storage_server_functions::run_daily_merge();
		$array = storage_server_functions::list_daily_backups();
		$count_merge = zbase_function::sqlite_cpoint_count(STORAGE_SERVER_1, $array[0]);
		$this->assertEquals("2000", $count_merge, "Key_count_mismatch in merged file");

	}

	public function test_Merged_Files_Deletion() {
		#AIM // Verify that .mbb files are deleted after daily merge runs.
		#EXPECTED RESULT // Incremental .mbb files are deleted

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array = storage_server_functions::list_incremental_backups();
		storage_server_functions::edit_date_folder(-7, "master");
		storage_server_functions::run_daily_merge();
		foreach($array as $path) {
			$status  = file_function::check_file_exists(STORAGE_SERVER_1, $path);
			$this->assertFalse($status, "Backup file are found on SS");
		}
	}

	public function test_Done_File_Creation() { 
		#AIM // Verify that .done-<date> file is put in the incremental directory after daily merge runs
		#EXPECTED RESULT // Done file is created in the required format

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		storage_server_functions::run_daily_merge();
		$array = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, date("m:d"));
		$hostname = general_function::get_hostname(TEST_HOST_2);
		$path = "/var/www/html/zbase_backup/".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/done-".date("m:d");
		$this->assertEquals($path, $array[0], "Done file not created in the required format");
	
	}

	public function est_Upload_Between_Merge() {	// makes exit
		#AIM // Verify that .mbb files that are added to the incremental directory after the script begins to run, are not deleted once the script is done
		#EXPECTED RESULT // They are not deleted and are considered for the next run of daily merge

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		backup_tools_functions::edit_defaultini_file("interval", "1");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		$this->assertTrue(Data_generation::add_keys(1500000, 500000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2000000, 20),"Failed adding keys");
		storage_server_functions::edit_date_folder(-7, "master");
		$pid = pcntl_fork();
		if ($pid) {
			$return = storage_server_functions::run_daily_merge();
		} else {
			zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
			exit;
		}
		storage_server_setup::delete_daily_backups();
		storage_server_functions::delete_done_daily_merge();
		$array = storage_server_functions::list_incremental_backups();
		$status  = file_function::check_file_exists(STORAGE_SERVER_1, $array[0]);
		if($pid)	{
			$this->assertTrue($status, "Backup file not found on SS");
		}
	
		storage_server_functions::run_daily_merge();
		$array = storage_server_functions::list_daily_backups();
		
	}

	public function test_Incremental_File_Order() {
		#AIM // Run daily merge and verify that the incremental files are being accepted in the correct merge order
		#EXPECTED RESULT // Incremental files are being accepted in the correct merge order

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$incr_array = storage_server_functions::list_incremental_backups();
		$incr_array[1] = trim($incr_array[1]);
		$incr_array[0] = trim($incr_array[0]);
		rsort($incr_array);
		storage_server_functions::edit_date_folder(-7, "master");
		$return = storage_server_functions::run_daily_merge();
		$hostname = general_function::get_hostname(TEST_HOST_2);
		$path="/var/www/html/zbase_backup/".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/backup-".date("Y-m-d");
		$command_to_be_executed = "cat ".ZBASE_BACKUP_LOG_FILE." | grep Processing\ file\ -\ $path";
		$daily_array = general_function::execute_command($command_to_be_executed, STORAGE_SERVER_1); 				
		$daily_array = trim($daily_array);
		$daily_array = explode("\n",$daily_array);
		$daily_array1 = explode(" ",$daily_array[0]);
		$daily_array2 = explode(" ",$daily_array[1]);
		$daily_array1 =trim(end($daily_array1));
		$daily_array2 =trim(end($daily_array2));

		$this->assertEquals($incr_array[0], $daily_array1, "Files not taken in the right order for daily merge");
		$this->assertEquals($incr_array[1], $daily_array2, "Files not taken in the right order for daily merge");
		
	}

	public function test_Merge_Done() {
		#AIM //Verify that .done file is put after the daily merge is complete
		#EXPECTED RESULT // .done file is put

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		storage_server_functions::run_daily_merge();
		$array = storage_server_functions::list_daily_backups(STORAGE_SERVER_1, ".done");
		$status  = file_function::check_file_exists(STORAGE_SERVER_1, $array[0]);
		$this->assertTrue($status, "Backup file not found on SS");
	}

	public function test_Merge_Nomenclature() {
		#AIM // Verify that backups generated have the format backup-<date_<time>-<backup_number>.mbb
		#EXPECTED RESULT // Nomenclature is in the expected format

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		storage_server_functions::run_daily_merge();
		$array = storage_server_functions::list_daily_backups();
		$status = preg_match("/backup-[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}:[0-9]{2}:[0-9]{2}-00000.mbb/", $array[0]);
		$this->assertEquals($status, "1", "Backup file not named in the right way");
	
	}		

	public function test_Lock_Test() {
		#AIM // If .lock file is present ensure that the .mbb files are not deleted 
		#EXPECTED RESULT // A manifest file is created containing the files that need to be deleted

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array_1 = storage_server_functions::list_incremental_backups();
		storage_server_functions::create_lock_file();
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$array_2 = storage_server_functions::list_incremental_backups();
		$this->assertEquals($array_1, $array_2, "Incremental files changed despite lock");
		$this->assertTrue(strpos($status, "Merge complete")>=0 and strpos($status, "WARNING: .lock file present. Skipping delete")>=0, "Merge done despite presence of lock file");
		
	}

	public function test_No_Split_File() {
		#AIM // Run daily merge with incremental backups present but no split files for these incremental backups
		#EXPECTED RESULT // Merge does not take place 

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_setup::delete_incremental_backups("*.split");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "no .split files found")>=0 and strpos($status, "Failed to create daily merged backup")>=0, "Merge done despite no split file in incremental directory");
		
	}

	public function test_Empty_Split_File() {
		#AIM // Run daily merge with no entries in the .split file.
		#EXPECTED RESULT // Merge does not take place

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::delete_split_file_entry("1");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "No new files to process")>=0 and strpos($status, "Failed to create daily merged backup")>=0, "Merge done despite empty split file");
		
	}

	public function test_Split_File_Missing_Entry() {
		#AIM // Run daily merge with missing entries in the .split file.
		#EXPECTED RESULT // Merge does not take place

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "10");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500);
		$this->assertTrue(Data_generation::add_keys(1000, 500, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 500, 2001, 10240),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::delete_split_file_entry("2");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Checkpoint mismatch in file")>=0 and strpos($status, "Failed to create daily merged backup")>=0, "Merge done despite missing checkpoints");
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "1024");
		
	}

	public function test_Merge_No_Incremental() {
		#AIM // Run daily merge with no incremental files present.
		#EXPECTED RESULT // Merge does not take place

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "10");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500);
		$this->assertTrue(Data_generation::add_keys(1000, 500, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Invalid location")>=0 and strpos($status, "Failed to create daily merged backup")>=0, "Merge done despite no incremental files");

	}

	public function test_Missing_Incr_Files() {
		#AIM //Run daily merge script with missing incremental backups
		#EXPECTED RESULT // Merge not done

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", "10");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_setup::delete_incremental_backups("*.mbb");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Files missing in split index file ")>=0, "Merge done despite missing files");
		
	}

	public function test_Delete_Output_Between_Merge() {	
		#AIM // Delete the partial daily merged file while the daily merge script is running.
		#EXPECTED RESULT //  Merged file not found

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		backup_tools_functions::edit_defaultini_file("interval", "1");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		$this->assertTrue(Data_generation::add_keys(1500000, 500000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$pid = pcntl_fork();
		if ($pid) {
			$status = storage_server_functions::run_daily_merge();			
		} else {
			sleep(5);
			storage_server_setup::delete_daily_backups();		
			exit;
		}
		$this->assertTrue(strpos($status, "Failed: Cannot find any files in path")>=0 and strpos($status, "Failed to create daily merged backup")>=0 and strpos($status, "Merge complete")>=0, "Merge file found despite deletion");

	}

	public function test_Deletion_If_Manifest_Is_Present() {
		#AIM // Run daily merge script with manifest file present in incremental directory and all the incremental backups listed in the manifest file are present
		#EXPECTED RESULT // All incremental files are deleted

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array_1 = storage_server_functions::list_incremental_backups();
		storage_server_functions::create_lock_file();
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$array_2 = storage_server_functions::list_incremental_backups();
		$this->assertEquals($array_1, $array_2, "Incremental files deleted despite lock");
		$this->assertTrue(strpos($status, "Merge complete")>=0 and strpos($status, "WARNING: .lock file present. Skipping delete")>=0, "Merge done despite presence of lock file");
		storage_server_functions::delete_lock_file();
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "no .split files found")>=0 and strpos($status, "Failed to create daily merged backup")>=0 , "Merge file found despite deletion");
		
	}

	public function test_Manifest_File_With_Additional_Incr() {
		#AIM // Run daily merge script with manifest file present in incremental directory and all the incremental backups listed in the manifest file are present. Besides this, there should also be some extra incremental backup files present in the incremental directory.
		#EXPECTED RESULT //  Merge is done properly

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array_1 = storage_server_functions::list_incremental_backups();
		storage_server_functions::create_lock_file();
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$array_2 = storage_server_functions::list_incremental_backups();
		$this->assertEquals($array_1, $array_2, "Incremental files deleted despite lock");
		$this->assertTrue(strpos($status, "Merge complete")>=0 and strpos($status, "WARNING: .lock file present. Skipping delete")>=0, "Merge done despite presence of lock file");
		storage_server_functions::delete_lock_file();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::delete_done_daily_merge();
		storage_server_setup::delete_daily_backups();
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>=0, "Merge not done despite new incr files");
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
	}	

	public function test_Manifest_Lock_Merge() {
		#AIM // With an existing manifest and lock file present in the incremental directory, run the daily merge script
		#EXPECTED RESULT // The additional files that are merged are not deleted but appended to the existing manifest file.

		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$array_1 = storage_server_functions::list_incremental_backups();
		storage_server_functions::create_lock_file();
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$array_2 = storage_server_functions::list_incremental_backups();
		$this->assertEquals($array_1, $array_2, "Incremental files deleted despite lock");
		$this->assertTrue(strpos($status, "Merge complete")>=0 and strpos($status, "WARNING: .lock file present. Skipping delete")>=0, "Merge done despite presence of lock file");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::delete_done_daily_merge();
		storage_server_setup::delete_daily_backups();
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>=0 and strpos($status, "WARNING: .lock file present. Skipping delete")>=0, "Merge not done despite new incr files");
		$array_size = count(storage_server_functions::list_incremental_backups());
		$this->assertEquals($array_size, "2", "Incr files deleted despite .lock file");

	}


}

class DailyMerge_TestCase_Full extends DailyMerge_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
