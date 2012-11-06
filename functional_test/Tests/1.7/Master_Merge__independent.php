<?php

abstract class IBR_MasterMerge_TestCase extends ZStore_TestCase {

	public function test_Simple_Master_Merge() {
		#AIM // Run master merge with a few daily backups and their corresponding .split files
		#EXPECTED RESULT // Master merge takes place properly

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		$count_merge = sqlite_functions::sqlite_count(STORAGE_SERVER, $master_backups[0]);
		$this->assertEquals($count_merge, "6000", "Not all data merged in master-merge");

	}

	public function test_Merge_Single_Daily() {
		#AIM // Run daily merge with only 1 daily backup
		#EXPECTED RESULT // Master merge takes place properly

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		$count_merge = sqlite_functions::sqlite_count(STORAGE_SERVER, $master_backups[0]);
		$this->assertEquals($count_merge, "4000", "Not all data merged in master-merge");
		
	}

	public function test_Merged_File_Creation() {
		#AIM // Verify that merged-<date> file is put in the master directory after master merge runs for that particular day.
		#EXPECTED RESULT // The file is placed

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"),"merged-".date("Y-m-d"));					//get the merged files
		$this->assertEquals(count($master_backups), 1 , "Merged file is not placed after master merge");			//if the count=1, the file has been placed

	}

	public function test_Split_File_Creation(){
		#AIM // Verify that .split file is put in the master directory after master merge runs for that particular day and the entries of the split file are correct
		#EXPECTED RESULT // The file is placed with correct entries

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1,10240),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001,10240),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
//		$split_files = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"),"backup-".date("Y-m-d")."*"."split"); check: search for .split ?
		$split_files = mb_backup_commands::list_master_backups(".split", date("Y-m-d"),"backup-".date("Y-m-d")."*"."split");
		$this->assertEquals(count($split_files), 1 , "Split file is not placed after master merge");
		$master_backups=mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		sort($master_backups);													//sort the backups' name based on time stamp
		$command_to_be_executed = "tail $split_files[0]";									//taking the split file entries
		$temp_array = trim(remote_function::remote_execution(STORAGE_SERVER,$command_to_be_executed));	
		$split_file_content = explode("\n",$temp_array);									//separating the entries
		sort($split_file_content);												//sort the split file entries based on timestamp
		for($i=0; $i<count($master_backups);$i++ ){
			$split_file_content[$i]=trim($split_file_content[$i]);
			//adding initial string to the split file content since it includes only the backup names
			$split_file_content[$i] = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/".date("Y-m-d")."/".$split_file_content[$i];
			$backup_name=trim($master_backups[$i]);										//the actual backup name
			$this->assertEquals($split_file_content[$i],$backup_name);							//compare the entries with the actual names
		}

	}

	public function test_Done_File_Creation(){
		#AIM // Verify that .done file is put in the master directory after master merge runs for that particular day.
		#EXPECTED RESULT // The file is placed

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$done_files = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"),".done");								//listing the .done files
		$this->assertEquals(count($done_files) , "1", ".done file not put in master directory");

	}

	public function test_Done_File_Absent_From_Daily_Directory(){
		#AIM // Verify that if the .done file does not exist in the daily directory to be processed, that directory is not considered by the master merge.
		#EXPECTED RESULT // The directory is not considered.

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");		
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		$done_files = mb_backup_commands::list_daily_backups(".mbb", date("Y-m-d"),".done");					//get the .done files
		$cmd = "sudo rm -rf $done_files[0]";										//remove the .done file for the latest daily backup
		remote_function::remote_execution(STORAGE_SERVER,$cmd);
		mb_backup_commands::edit_date_folder(-7, "master");
		$status	 = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");	
		$path="/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/";
		$command_to_be_executed = "tail -15 ".MEMBASE_BACKUP_LOG_FILE." | grep Processing\ file\ -\ $path";		//extract the daily backups which were processed during master-merge
		$path_today=$path.date("Y-m-d");
		$temp=remote_function::remote_execution(STORAGE_SERVER,$command_to_be_executed);
		$this->assertFalse(strpos($temp , $path_today) , "The directory without .done file is considered");		//make sure that the backup with deleted .done file is not considered

	}

	public function test_Seven_Daily_Merge(){
		#AIM:Merge 7 daily backups into a master backup
		#EXPECTED RESULT : Master merge takes place properly
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");

		for($j=1;$j<=7;$j++){												//the loop creates 7 daily backups
			$this->assertTrue(Data_generation::add_keys(2000, 1000, ($j*2000)+2001, 20),"Failed adding keys");
			mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
			$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
			$status = mb_backup_commands::run_daily_merge();
			$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
			mb_backup_commands::edit_date_folder(-(7-$j));								//change the date of the daily merge folder by 7-j
			mb_backup_commands::delete_done_daily_merge();
		}
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		$count_merge = sqlite_functions::sqlite_count(STORAGE_SERVER, $master_backups[0]);
		$this->assertEquals($count_merge, "16000", "Not all data merged in master-merge");

	}

	public function test_Same_Day_Daily_Backup(){
		#AIM : Ensure that master merge is run considering the daily backup of the same day also
		#EXPECTED RESULT : The mster merge considers the file.
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		$count_merge = sqlite_functions::sqlite_count(STORAGE_SERVER, $master_backups[0]);
		$this->assertEquals($count_merge, "6000", "Not all data merged in master-merge");
		mb_backup_commands::stop_backup_daemon(TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Second_Master_Merge(){
		#AIM Run master backup for a second time after the first instance of master backup script has completed running.
		#EXPECTED RESULT // Master merge doesn't take place the second time

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$status = mb_backup_commands::run_master_merge();										//attempt a second backup
		$this->assertTrue(strpos($status, "Info: Merged for this location /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master already done today")>0, 
		"Master Merge done again or earlier file deleted");					//make sure that the script throws an error
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		$count_merge = sqlite_functions::sqlite_count(STORAGE_SERVER, $master_backups[0]);
		$this->assertEquals($count_merge, "6000", "Not all data merged in master-merge");
		mb_backup_commands::stop_backup_daemon(TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}

	public function test_Backup_Split_Size(){
		#AIM // Verify that backups are being generated according to the split size parameter specified for 10MB, 15MB
		#EXPECTED RESULT // The size is as specified

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(1500, 500, 1, 10240),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(1500, 500, 1501, 10240),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		foreach($master_backups as &$v){
			$v=trim($v);
		}
		sort($master_backups);
		$size = mb_backup_commands::get_backup_size(STORAGE_SERVER, $master_backups[0]);				//get the size of one of the backups
		$this->assertGreaterThanOrEqual(9437184, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(11534336, $size, "Backup database with greater size than expected");
		
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 15);
		$this->assertTrue(Data_generation::add_keys(1500, 500, 1, 20480),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(1500, 500, 1501, 20480),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
		foreach($master_backups as &$v)
		$v=trim($v);
		sort($master_backups);
		$size = intval(mb_backup_commands::get_backup_size(STORAGE_SERVER, $master_backups[0]));
		$this->assertGreaterThanOrEqual(14680064, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(16777216, $size, "Backup database with greater size than expected");

	}

	public function test_Master_Merge_With_Corrupt_Daily_Files() {
		#AIM //  Run master merge with corrupt incremental files
		#EXPECTED RESULT // The merge takes place without those files

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-2);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		$daily_backups = mb_backup_commands::list_daily_backups(".mbb", date("Y-m-d"));
		sort($daily_backups);
		sqlite_functions::corrupt_sqlite_file(STORAGE_SERVER, $daily_backups[0]);
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Info. ERROR: Unable to open file")>0, "File Not Corrupted");
		$this->assertTrue(strpos($status, "Info. Merge failed")>0, "Master Merge done despite corrupting file");

	}

	public function test_Merge_without_daily_backup(){
		#AIM: Run master merge with no daily backups present
		#Expected : Merge doesn't take place
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Too few files to merge.")>0,"Master Merge done");

	}


	public function test_Kill_Master_Merge(){
		#AIM : //Kill master merge while it is in progress
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 1, 10240),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 10001, 10240),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-4);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 20001,10240),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-3);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 30001, 10240),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-2);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(10000, 1000, 40001, 10240),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		mb_backup_commands::edit_date_folder(-7, "master");
		$pid = pcntl_fork();
		if ($pid) {
			mb_backup_commands::run_master_merge();
			$master_backups = mb_backup_commands::list_master_backups(".mbb", date("Y-m-d"));
			$status  = file_function::check_file_exists(STORAGE_SERVER, $master_backups[0]);
			$this->assertFalse($status, "Process not killed\n");				
		} else {
			sleep(5);
			$cmd = "sudo killall -9 python26";
			remote_function::remote_execution(STORAGE_SERVER , $cmd);
			sleep(5);
			exit();
		}
	}	

	public function test_Daily_File_Order() {
		#AIM // Run master merge and verify that the daily files are being accepted in the correct merge order
		#EXPECTED RESULT // Daily files are being accepted in the correct merge order
		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		mb_backup_commands::set_backup_const(STORAGE_SERVER, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-2);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		$daily_array1 = mb_backup_commands::list_daily_backups(".mbb", date("Y-m-d",mktime(0,0,0,date("m"),date("d")-2,date("Y"))));			//backup name for today's date-2
		$daily_array2 = mb_backup_commands::list_daily_backups(".mbb", date("Y-m-d",mktime(0,0,0,date("m"),date("d")-1,date("Y"))));			//backup name for today's date -1
		$daily_array3 = mb_backup_commands::list_daily_backups(".mbb", date("Y-m-d"));									//backup name for today
		$daily_array1[0] = trim($daily_array1[0]);
		$daily_array2[0] = trim($daily_array2[0]);
		$daily_array3[0] = trim($daily_array3[0]);
		mb_backup_commands::edit_date_folder(-7, "master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$path="/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/";
		$command_to_be_executed = "tail -35 ".MEMBASE_BACKUP_LOG_FILE." | grep Processing\ file\ -\ $path";
		$temp_array = remote_function::remote_execution(STORAGE_SERVER,$command_to_be_executed);
		$master_array = explode("\n",$temp_array);												
		$master_array1 = explode(" ",$master_array[0]);	
		$master_array2 = explode(" ",$master_array[1]);
		$master_array3 = explode(" ",$master_array[2]);
		$master_array1[10]=trim($master_array1[10]);												//the value at index 10 of the array is the backup name
		$master_array2[10]=trim($master_array2[10]);
		$master_array3[10]=trim($master_array3[10]);
		$this->assertEquals($daily_array3[0], $master_array1[10], "Files not taken in the right order for master merge");
		$this->assertEquals($daily_array2[0], $master_array2[10], "Files not taken in the right order for master merge");
		$this->assertEquals($daily_array1[0], $master_array3[10], "Files not taken in the right order for master  merge");

	}

	public function test_Modified_Chk_Point(){
		#AIM // Modify the checkpoint table of the daily backups to contain invalid checkpoints and observe working of the master merge script 

		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 1, 20),"Failed adding keys");
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 3001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::edit_date_folder(-1);
		mb_backup_commands::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 6001, 20),"Failed adding keys");
		mb_backup_commands::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		$status = mb_backup_commands::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		mb_backup_commands::delete_done_daily_merge();
		$daily_backups = mb_backup_commands::list_daily_backups(".mbb", date("Y-m-d",mktime(0,0,0,date("m"),date("d")-1,date("Y"))));                    //backup name for today's date-1
		sqlite_functions::sqlite_update(STORAGE_SERVER,"cpoint_id","cpoint_state",$daily_backups[0],5);
		mb_backup_commands::edit_date_folder(-7,"master");
		$status = mb_backup_commands::run_master_merge();
		$this->assertTrue(strpos($status, "Info. ERROR: Checkpoint mismatch in file")>0, "Checkpoint error not caught");
		mb_backup_commands::stop_backup_daemon(TEST_HOST_2);
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
	}


}

class IBR_MasterMerge_TestCase_Full extends IBR_MasterMerge_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

