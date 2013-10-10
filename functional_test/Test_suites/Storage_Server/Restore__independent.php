<?php

abstract class Restore_TestCase extends ZStore_TestCase {

	public function test_Restore_Invalid_HostName(){
		// AIM : Run restore with invalid hostname specified with the -h parameter
		//EXPECTED RESULT : Restore fails
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$command_to_be_executed = "sudo python26 ".ZBASE_RESTORE_SCRIPT." -h rand_serv";
		//Not using the defined function for running restore..since the function tries to run restore on the specified host itself..
		$status = remote_function::remote_execution_popen(TEST_HOST_2, $command_to_be_executed);
		$this->assertTrue(strpos($status,"Could not find any incremental backups")>0 , "Missing incremental backups not reported");
		$this->assertTrue(strpos($status,"Could not find master backup. Maximum tries exceeded")>0, "Missing master backups not reported");
		$this->assertTrue(strpos($status,"No backup files found.")>0, "Missing backups not reported");
		$this->assertTrue(strpos($status,"Restore process terminated")>0 , "Restore process not stopped ");
	}

	public function test_Restore_valid_HostName(){
		// AIM :Run restore with valid hostname specified for the -h parameter...with 1 incr, 1 daily and 2 master backups
		// EXPECTED RESULT : Restore is successful
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		storage_server_functions::edit_date_folder(-1);
		storage_server_functions::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(200, 100, 401, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "stop");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Processing backup file") > 0 ,"Backup files not prcoessed");
		$this->assertTrue(strpos($status,"Executing command python26 /opt/zbase/lib/python/mbadm-online-restore -h 127.0.0.1:11211 ") > 0, "Restore command not executed");
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 600, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Restore_without_HostName_Specified(){ 
		// AIM : Run restore without any -h parameter specified
		//EXPECTED RESULT : the hostname of the box on which the script is being run is considered
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$command_to_be_executed = "sudo python26 ".ZBASE_RESTORE_SCRIPT;
		$status = remote_function::remote_execution_popen(TEST_HOST_2, $command_to_be_executed);
		$hostname = general_function::get_hostname(TEST_HOST_2);
		$this->assertContains("Executing command /usr/bin/zstore_cmd ls s3://".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/", $status, "Host not considered for restore");
	}

	public function test_Restore_Invalid_Cloud_and_GameID(){ 
		// AIM : Run restore with invalid values set for cloud_id and game_id in the default.ini file
		//EXPECTED RESULT : Restore fails
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::edit_defaultini_file('cloud' , 'random_cld');
		backup_tools_functions::edit_defaultini_file('game_id' , 'random_game');
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Could not find any incremental backups")>0 , "Missing incremental backups not reported");
		$this->assertTrue(strpos($status,"Could not find master backup. Maximum tries exceeded")>0, "Missing master backups not reported");
		$this->assertTrue(strpos($status,"No backup files found.")>0, "Missing backups not reported");
		$this->assertTrue(strpos($status,"Restore process terminated")>0 , "Restore process not stopped ");
	}

	public function test_Verify_Lock_File(){ 
		// AIM :Verify that .lock file is put in incremental directory and is removed when restore completes
		// EXPECTED RESULT : 	The file is put and then deleted when restore completes
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(1000, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(1000, 100, 1001, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$pid = pcntl_fork();
		if($pid == -1){
			die('could not fork');
		}
		else if($pid){		//parent
			// Run the restore script and verify restore completed successfully
			log_function::debug_log("Parent running restore script");
			$status = mb_restore_commands::restore_server(TEST_HOST_2);
			$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
			$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
			$this->assertEquals($count, 2000, "Number of restored keys not equal to number of keys in backups");
			$lock_file = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, "lock*");
			$this->assertEquals(strcmp($lock_file[0] , "") ,0 ,"Lock file not deleted and still exists");
		}
		else{			//child
			// Verify that the lock file is put in the directory
			sleep(4);
			log_function::debug_log("Child verifying that lock file is put in the incr directory");
			$lock_file = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, "lock*");
			$hostname = general_function::get_hostname(TEST_HOST_2);
			$this->assertEquals(strcmp($lock_file[0] , "/var/www/html/zbase_backup/".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/.lock-".$hostname) , 0 , "Lock file put, but has a different name" );
			exit();
		}
	}

	public function est_Verify_Lock_File_Delted_after_Killing(){ 
		// AIM : Run restore and ensure that the .lock-<hostname> file is put in the SS. Forcefull kill the restore process by passing different kill signals to it.
		// EXEPCTED RESULT : In each case, verify that the .lock file is removed
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(1000, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(1000, 100, 1001, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$pid = pcntl_fork();
		if($pid == -1){
			die('could not fork');
		}
		else if($pid){		//parent
			// Run the restore script and verify lock file is deleted\
			log_function::debug_log("Parent running restore script");
			$status = mb_restore_commands::restore_server(TEST_HOST_2);
			$lock_file = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, "lock*");
			$this->assertEquals(strcmp($lock_file[0] , "") ,0 ,"Lock file not deleted and still exists");
		}
		else{				//child
			//kill the restore process
			sleep(4);
			log_function::debug_log("Child killing restore script");
			$lock_file = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, "lock*");
			$hostname = general_function::get_hostname(TEST_HOST_2);
			$this->assertEquals(strcmp($lock_file[0] , "/var/www/html/zbase_backup/".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/.lock-".$hostname) , 0 , "Lock file put, but has a different name" );
			$cmd = "sudo killall -9 python26";
			remote_function::remote_execution(TEST_HOST_2 , $cmd);
			exit();
		}

	}

	public function test_Restore_Without_backups_on_SS(){ 
		// AIM : Run restore with no backups present in the storage server for the host specified with the -h option
		//EXPECTED RESULT : Restore doesnot take place
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Could not find any incremental backups")>0 , "Missing incremental backups not reported");
		$this->assertTrue(strpos($status,"Could not find master backup. Maximum tries exceeded")>0, "Missing master backups not reported");
		$this->assertTrue(strpos($status,"No backup files found.")>0, "Missing backups not reported");
		$this->assertTrue(strpos($status,"Restore process terminated")>0 , "Restore process not stopped ");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 0, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Restore_Zbase_stopped(){
		// AIM : Run restore without zbase running on the box
		//EXPECTED RESULT : the restore process starts zbase before proceeding with the restore
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		zbase_setup::memcached_service(TEST_HOST_2, "stop");
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$memcached_status = remote_function::remote_execution(TEST_HOST_2, "sudo /etc/init.d/memcached status");
		$this->assertTrue(strpos($memcached_status, "is running")>0 , "Zbase not started");
		$this->assertTrue(strpos($status,"Starting zbase")>0,"Zbase not started");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 100, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Restore_Only_Incremental_Backups(){
		// AIM : Run restore with only incremental backups
		//EXPECTED RESULT : Restore completes successfully
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 101, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		//remove master backup directory
		storage_server_setup::delete_master_backups();
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
	}

	public function test_Restore_Only_Master_Backups(){
		// AIM : Run restore with only incremental backups
		//EXPECTED RESULT : Restore completes successfully
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		storage_server_functions::edit_date_folder(-1);
		storage_server_functions::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(200, 100, 401, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		storage_server_setup::delete_daily_backups();
		storage_server_setup::delete_incremental_backups();
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 400, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Restore_Missing_Split_Files_in_MasterDir(){
		// AIM : Run restore with missing split files in the master directory
		//EXPECTED RESULT : Restore completes successfully
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		storage_server_functions::edit_date_folder(-1);
		storage_server_functions::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(200, 100, 401, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		$status = storage_server_functions::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		$split_files[0] = storage_server_functions::list_master_backups(STORAGE_SERVER_1, ".split", 0);
		$split_files[1] = storage_server_functions::list_master_backups(STORAGE_SERVER_1, ".split", -7);
		foreach($split_files as $file){
			$command_to_be_executed = "sudo rm -f ".$file;
			remote_function::remote_execution(STORAGE_SERVER_1,$command_to_be_executed);
		}
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 600, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Restore_One_Buffer_Defined(){
		// AIM : Run restore process with only one buffer defined in the /etc/zbase-backup/default.ini file
		// EXPECTED RESULT : Restore completes successfully
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100, 100, 101, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		backup_tools_functions::edit_defaultini_file('buffer_list' , '\/db_backup\/0');
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0);
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 200, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Missing_Daily_Directory(){
		// Restore with missing directory for daily backup
		//EXPECTED RESULT : Restore completes successfully
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		storage_server_functions::edit_date_folder(-2);
		storage_server_functions::delete_done_daily_merge();
		$this->assertTrue(Data_generation::add_keys(200, 100, 401, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		$status = storage_server_functions::run_master_merge();
		$this->assertTrue(strpos($status, "Success: Master merge completed")>0, "Master Merge not done");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 600, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Restore_Corrupt_backups(){
		// AIM : Restore with corrupt backups
		// EXPECTED RESULT : Error is thrown and restore fails
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 20),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 20),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		storage_server_functions::edit_date_folder(-7, "master");
		$status = storage_server_functions::run_daily_merge();
		$this->assertTrue(strpos($status, "Merge complete")>0, "Daily merge not completed");
		$backup = storage_server_functions::list_master_backups(STORAGE_SERVER_1, ".mbb", -7);
		sqlite_functions::corrupt_sqlite_file(STORAGE_SERVER_1, $backup[0]);
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"file is encrypted or is not a database")>0,"Dataabse corrupt error not caught");
		$this->assertTrue(strpos($status,"Restore process terminated")>0 , "Restore process not stopped ");
	}

	public function est_Two_Instances_for_Same_Host(){
		//AIM : Run 2 instances of restore for the same host
		// EXPECTED : the second instance exits with an appropriate error message.
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		zbase_backup_setup::clear_zbase_backup_log_file(TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(2000, 100, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$pid = pcntl_fork();
		if($pid){		//parent
			// Run the restore script on slave
			log_function::debug_log("Parent running restore script");
			$status = mb_restore_commands::restore_server(TEST_HOST_2);
			var_dump($status);
			$this->assertContains("Restore completed successfully", $status, "Restore not completed");// Fails
			$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
			$this->assertEquals($count, 2000, "Number of restored keys not equal to number of keys in backups");
		} else {			//child
			// Try to run the restore script for slave again
			sleep(1);
			log_function::debug_log("Child running restore script again on same host");
			$status = mb_restore_commands::restore_server(TEST_HOST_2);
			exit();
		}
		$command_to_be_executed = "cat ".ZBASE_BACKUP_LOG_FILE;
		$status = remote_function::remote_execution(TEST_HOST_2 , $command_to_be_executed);
		$this->assertTrue(strpos($status,"ERROR: Memcached error" ) > 0 , "Restore was completed again");
		$this->assertTrue(strpos($status, "restorer isn't idle!" ) >0 ,"Restore was completed again");
	}

	public function test_Kill_Restore(){
		// AIM : Kill restore process while it is in progress. Restart it and verify that restore begins right from the start.
		// EXPECTED RESULT : The process is killed and it starts from begining on restarting
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "SPLIT_SIZE", 10);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 10240),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(3000, 1000, 2001, 10240),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$pid = pcntl_fork();
		if ($pid) {		//parent
			// Run the restore script
			log_function::debug_log("Parent running restore script");
			mb_restore_commands::restore_server(TEST_HOST_2); 

		} else {		//child
			// Kill the restore script
			sleep(1);
			log_function::debug_log("Child killing restore script");
			$cmd = "sudo killall -9 python26";
			remote_function::remote_execution(TEST_HOST_2 , $cmd);
			exit();
		}
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status , "[1, 2]")>0 , "Restore didn't start from begining");
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 5000, "Number of restored keys not equal to number of keys in backups");
	}

	public function est_Restore_Two_Slaves_Both_Reading_Same_Dir(){ //check
		// AIM : Restore two test hosts when both of them try to read from the same directory on SS at the same time
		// EXPECTED RESULT : The restore completes successfully for both and lock files are put for both hosts in the SS directory
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		// Restore both
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		storage_server_setup::install_zstore_and_configure_storage_server(TEST_HOST_1 , STORAGE_SERVER_1);
		$pid = pcntl_fork();
		if($pid){		//parent
			// run the restore script on master
			log_function::debug_log("Parent running restore script on master");
			$command_to_be_executed = "sudo python26 ".ZBASE_RESTORE_SCRIPT." -h ".TEST_HOST_2;
			$status1 = remote_function::remote_execution_popen(TEST_HOST_1, $command_to_be_executed); // need to install backup tools on master
			$this->assertTrue(strpos($status1,"Restore completed successfully")>0,"Restore not completed for master");
			sleep(5);
		} else {			//child
			sleep(2);
			// run restore script for slave
			log_function::debug_log("child running restore script on slave");
			$status2 = mb_restore_commands::restore_server(TEST_HOST_2);
			$this->assertTrue(strpos($status2,"Restore completed successfully")>0,"Restore not completed for slave");
			exit();
		}
		
		// verify that lock files are put for both
		sleep(3);
		$lock_file = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, "lock*");// lock file is not created
		$this->assertEquals(count($lock_file) ,2 ,"Lock file not put for both");
		sort($lock_file);
		$lock_file[0] = trim($lock_file[0]);
		$lock_file[1] = trim($lock_file[1]);
		$hostname = general_function::get_hostname(TEST_HOST_2);
		$this->assertEquals(strcmp($lock_file[0] , "/var/www/html/zbase_backup/".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/.lock-".$hostname) , 0 , "Lock file put, but has a different name" );
		$this->assertEquals(strcmp($lock_file[1] , "/var/www/html/zbase_backup/".GAME_ID."/".$hostname."/".ZBASE_CLOUD."/incremental/.lock-".$hostname) , 0 , "Lock file put, but has a different name" );
			
		sleep(5);
		$count = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($count, 6000, "Number of restored keys on master not equal to number of keys in backups");
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 6000, "Number of restored keys on slave not equal to number of keys in backups");
		$lock_file = storage_server_functions::list_incremental_backups(STORAGE_SERVER_1, "lock*");
		$this->assertEquals(strcmp($lock_file[0] , "") ,0 ,"Lock file not deleted and still exists");
		

	}

	public function test_Restore_from_Backfill_based_backup(){
		// AIM : Carry out a backfill based backup on the slave and then restore the box with the same backup just taken
		// EXPECTED RESULT : Restore is successful
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 401, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		// Backfill based backup
		storage_server_setup::clear_storage_server();
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		sleep(10);			
		// Restore 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 600, "Number of restored keys not equal to number of keys in backups");	
	}

	public function test_Backfill_from_Restore(){
		// AIM : Carry out restore and once the restore completes successfully trigger a backfill based backup. Again restore the system
		// EXPECTED RESULT : Restore is successful
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 201, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(200, 100, 401, 10),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		// Restore
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0);
		// Backfill based backup
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(100, 100,601, 10),"Failed adding keys");
		vbucketmigrator_function::attach_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		//		tap_commands::register_backup_tap_name(TEST_HOST_2);
		storage_server_setup::clear_storage_server();
		zbase_backup_setup::stop_backup_daemon(TEST_HOST_2);
		zbase_setup::memcached_service(TEST_HOST_2, "restart"); 
		tap_commands::register_backup_tap_name(TEST_HOST_2); 
		zbase_backup_setup::start_backup_daemon_full(TEST_HOST_2);
		sleep(10);
		// Restore
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 700, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_Four_Backups_1G_Each(){
		//AIM : Carry out restore with four backups of 1G Each
		// EXPECTED RESULT : Restore is successful
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 512000),"Failed adding keys");
		zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 512000),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 512000),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 512000),"Failed adding keys");
		zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
		// Restore
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		//Verifying count of keys
		$count = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$this->assertEquals($count, 8000, "Number of restored keys not equal to number of keys in backups");
	}

	public function test_checkpoint_after_restore() {
		//AIM : To verify restore checkpoints are properly set after a restore
                // EXPECTED RESULT : Restore is successful
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
                flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
                $this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
                zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $this->assertTrue(Data_generation::add_keys(200, 100, 201, 10),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $this->assertTrue(Data_generation::add_keys(200, 100, 401, 10),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$checkpoint_stats = stats_functions::get_checkpoint_stats(TEST_HOST_2);
                zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
                $status = mb_restore_commands::restore_server(TEST_HOST_2);
                $this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
		$raw_stats= stats_functions::get_raw_stats(TEST_HOST_2, "restore");

	}

}

class Restore_TestCase_Full extends Restore_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}


