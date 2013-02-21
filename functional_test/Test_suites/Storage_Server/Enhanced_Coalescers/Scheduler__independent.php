<?php

abstract class Scheduler extends ZStore_TestCase {
	//Need to add storage_server_functions::modify_scheduler() to the pre execution phase for this test suite.

	public function test_Modify()	{
		storage_server_functions::modify_scheduler();
	}
	public function test_Daily_Merge_With_Scheduler()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		//Starting scheduler
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(30);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "DailyMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Daily Merge failed");
		//Asserting the presence of complete file after daily merge.
		$hostname = explode(".", TEST_HOST_2);
		$this->assertTrue(file_function::check_file_exists($primary_mapping_ss, "/$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD."/daily/*/complete"), "Complete file not put by scheduler after daily merge");
		//Asserting that the permissions of the newly created daily file are changed to storageserver.
		$ownership_user = file_function::file_attributes($primary_mapping_ss, "/$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD."/daily/", "ownership_user");
		$ownership_group = file_function::file_attributes($primary_mapping_ss, "/$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD."/daily/", "ownership_group");
		$this->assertEquals("storageserver", $ownership_user);
		$this->assertEquals("storageserver", $ownership_group);
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2), "Unable to start scheduler");
		$this->assertFalse(strpos(file_function::query_log_files("/var/log/membasebackup.log", "DailyMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Daily merge ran through scheduler though it was already done for the day");
	}

	public function test_Master_Merge_With_Scheduler()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");	
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$hostname = explode(".", TEST_HOST_2);
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(30);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Master Merge failed");  
		//Asserting the presence of complete file after master  merge.
		$tomorrow_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
		$last_sunday = date("Y-m-d", strtotime(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")))." last Sunday "));
		$this->assertTrue(file_function::check_file_exists($primary_mapping_ss, "/$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD."/master/$last_sunday/complete"), "Complete file not put by scheduler after master merge");
		//Asserting that the permissions of newly created master file are changed to that of storageserver.
		$ownership_user = file_function::file_attributes($primary_mapping_ss, "/$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD."/master/", "ownership_user");
		$ownership_group = file_function::file_attributes($primary_mapping_ss, "/$primary_mapping_disk/primary/$hostname[0]/".MEMBASE_CLOUD."/master/", "ownership_group");
		$this->assertEquals("storageserver", $ownership_user);
		$this->assertEquals("storageserver", $ownership_group);
		//Running scheduler again to ensure that if the presence of .complete file exists, the scheduler should not run the daily/master merge for that day.
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2), "Unable to start scheduler");
		$this->assertFalse(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Master merge ran through scheduler though it was already done for the day");
	}	

	public function test_Scheduler_With_Bad_Disk_Daily()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_2,'primary'),"Failed adding bad disk entry");
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2, $primary_mapping_ss), "Unable to start scheduler");
		$this->assertFalse(strpos(file_function::query_log_files("/var/log/membasebackup.log", "DailyMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete"), "Daily Merge ran despite disk being marked as bad");

	}

	public function test_Scheduler_With_Bad_Disk_Master()  {
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_2,'primary'),"Failed adding bad disk entry");
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2, $primary_mapping_ss), "Unable to start scheduler");
		$this->assertFalse(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete"), "Master Merge ran despite disk being marked as bad");
	}

	public function test_Scheduler_Updates_Dirty_File_After_Daily_Merge()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(30);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "DailyMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Daily Merge failed");
		//Asserting that the dirty file is updated with relevent files.
		//remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);		
		$this->assertTrue(enhanced_coalescers::compare_dirty_file_after_daily_merge(TEST_HOST_2), "Dirty file not updated or is updated in the wrong order after daily merge");
		//Verifying if dirty file is updated accordingly after a master merge.
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
	}


	public function test_Scheduler_Updates_Dirty_File_After_Master_Merge()	{

		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(30);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Master Merge failed");		
		$this->assertTrue(enhanced_coalescers::compare_dirty_file_after_master_merge(TEST_HOST_2), "Dirty file not updated or is updated in the wrong order after master  merge");
	}

	public function test_Scheduler_Updates_To_Be_Deleted_File()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		//Getting list of .split files for later verification.
		$split_file_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2, "split");
		asort($split_file_list);
		//Starting scheduler
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(30);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "DailyMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Daily Merge failed");
		//Verify that the to_be_deleted file is updated.
		remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/to_be_deleted", "/tmp/to_be_deleted", True);
		$to_be_deleted = explode("\n", trim(file_function::read_from_file("/tmp/to_be_deleted")));
		asort($to_be_deleted);
		$this->assertEquals(count(array_diff_assoc(array_values($to_be_deleted), array_values($split_file_list))), 0, "to_be_deleted file not updated with the deleted .split file after daily merge");	

	}

	public function test_Scheduler_Updates_To_Be_Deleted_File_After_Processing_Manifest_File()	{

		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		//remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		//Pumping keys and uploading backups
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		//Preparing a list of the incremental backups just uploaded for later comparison.
		$incremental_backup_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		//Starting scheduler
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(15);
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$sunday_day_difference = storage_server_functions::get_sunday_date_difference();
		storage_server_functions::edit_date_folder(-$sunday_day_difference-1, "daily", TEST_HOST_2);
		storage_server_functions::delete_done_daily_merge(TEST_HOST_2);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 8001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 10001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$host = explode(".", TEST_HOST_2);
		remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/incremental/manifest.del", "/tmp/manifest.del", True);
		$manifest_file = explode("\n", trim(file_function::read_from_file("/tmp/manifest.del")));
		asort($manifest_file);
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		storage_server_setup::clear_to_be_deleted_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::restart_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(10);
		asort($incremental_backup_list);
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(count(array_diff_assoc(array_values($incremental_backup_list), array_values($manifest_file))), 0, "to_be_deleted file not updated with contents of manifest.del  file after daily merge");
	}


	public function test_Daily_Merge_Not_Started_Due_To_Entry_In_Dirty()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		//Uploading a large file to ensure that dirty entry remains for a while causing scheduler to not run daily merge since disk is busy.
		//Creating dummy file	
		file_function::create_dummy_file(TEST_HOST_2, "/tmp/dummy_1", 1073741824, False, "zero");
		$host = explode(".", TEST_HOST_2);
		diskmapper_api::zstore_put("/tmp/dummy_1", $host[0]);
		//Verify entry in dirty file	
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);
		$dirty_file_array = explode("\n", trim(file_function::read_from_file("/tmp/dirty")));
		$this->assertTrue(in_array("/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/test/dummy_1", $dirty_file_array), "Uploaded file not present in dirty file");
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");	
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(5);		
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "(Daily Merge Scheduler).* Disk busy", $primary_mapping_ss), "Disk busy")>0, "Daily merge was run despite disk being busy");

	}


	public function test_Master_Merge_Not_Started_Due_To_Entry_In_Dirty()  {
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		//Uploading a large file to ensure that dirty entry remains for a while causing scheduler to not run daily merge since disk is busy.
		//Creating dummy file   
		file_function::create_dummy_file(TEST_HOST_2, "/tmp/dummy_1", 1073741824, False, "zero");
		$host = explode(".", TEST_HOST_2);
		diskmapper_api::zstore_put("/tmp/dummy_1", $host[0]);
		//Verify entry in dirty file    
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);
		$dirty_file_array = explode("\n", trim(file_function::read_from_file("/tmp/dirty")));
		$this->assertTrue(in_array("/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/test/dummy_1", $dirty_file_array), "Uploaded file not present in dirty file");
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(5);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "(Maser Merge Scheduler).* Disk busy", $primary_mapping_ss), "Disk busy")>0, "Master merge was run despite disk being busy");

	}

	public function test_Daily_Paused_When_Dirty_Entry_Is_Added()  {
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		//Creating dummy file for upload.
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$host = explode(".", TEST_HOST_2);
		file_function::create_dummy_file(TEST_HOST_2, "/data_1/primary/$host/".MEMBASE_CLOUD."/dummy_1", 10485760, False, "zero");
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		$host = explode(".", TEST_HOST_2);
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(1);
			diskmapper_api::zstore_put("/tmp/dummy_1", $host[0]);		
			sleep(1);
			remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);
			$dirty_file_array = explode("\n", trim(file_function::read_from_file("/tmp/dirty")));                $this->assertTrue(in_array("/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/test/dummy_1", $dirty_file_array), "Uploaded file not present in dirty file");
			$this->assertTrue(storage_server_functions::verify_merge_paused(TEST_HOST_2, "daily"), "Daily merge not paused");
			sleep(30);
			$this->assertTrue(storage_server_functions::verify_merge_resumed(TEST_HOST_2,  "daily"), "Daily merge not resumed");
		}
		else    {
			$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
			exit(0);
		}
	}


	public function test_Master_Merge_Paused_When_Dirty_Entry_Is_Added()  {
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		//Creating dummy file for upload.
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$host = explode(".", TEST_HOST_2);
		file_function::create_dummy_file(TEST_HOST_2, "/data_1/primary/$host/".MEMBASE_CLOUD."/dummy_1", 10485760, False, "zero");
		remote_function::remote_execution($primary_mapping_ss, "cat /dev/null | sudo tee /var/log/membasebackup.log");
		$host = explode(".", TEST_HOST_2);
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(1);
			diskmapper_api::zstore_put("/tmp/dummy_1", $host[0]);
			sleep(1);
			remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);
			$dirty_file_array = explode("\n", trim(file_function::read_from_file("/tmp/dirty")));                $this->assertTrue(in_array("/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/test/dummy_1", $dirty_file_array), "Uploaded file not present in dirty file");
			$this->assertTrue(storage_server_functions::verify_merge_paused(TEST_HOST_2, "master"), "Master merge not paused");
			sleep(30);
			$this->assertTrue(storage_server_functions::verify_merge_resumed(TEST_HOST_2,  "master"), "Master merge not resumed");
		}
		else    {
			$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
			exit(0);
		}
	}
	public function test_Daily_Merge_Not_Started_When_Free_Memory_Is_Not_Available()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		//file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "DAILYJOB_MEM_THRESHOLD", 1000000, "modify");
		backup_tools_functions::set_backup_const($primary_mapping_ss, "DAILYJOB_MEM_THRESHOLD", 1000000);
		sleep(2);
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(20);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "(Daily Merge Scheduler).* Not enough memory available", $primary_mapping_ss), "Not enough memory available")>0, "Daily merge was run despite SS being low on memory");
	}

	public function test_Master_Merge_Not_Started_When_Free_Memory_Is_Not_Available()        {
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		backup_tools_functions::set_backup_const($primary_mapping_ss, "MASTERJOB_MEM_THRESHOLD", 1000000);
		sleep(2);
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(20);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "(Master Merge Scheduler).* Not enough memory available", $primary_mapping_ss), "Not enough memory available")>0, "Master merge was run despite SS being low on memory");
	}	

	public function test_Scheduler_Does_Not_Take_Daily_Backups_After_The_Previous_Sunday()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		//In order to ensure that the backups after the last sunday are not taken into account, we will copy an existing daily backup to another date after the last sunday and verify that it is not considered for merge
		$date_of_daily_backup_to_copy = @date("Y-m-d", strtotime(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")))." last Monday "));
		$date_to_copy_daily_backup_to = @date("Y-m-d", strtotime(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")))." last Monday "));
		$host = explode(".", TEST_HOST_2);
		$path = "/$primary_mapping_disk/primary/$host[0]/".MEMBASE_CLOUD."/daily/";
		remote_function::remote_execution($primary_mapping_ss, "sudo cp -R /$path/$date_of_daily_backup_to_copy $path/$date_to_copy_daily_backup_to; sudo chown -R storageserver.storageserver $path/$date_to_copy_daily_backup_to");
		//Run scheduler
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(30);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Master Merge failed");
	}


	public function test_Scheduler_Runs_Daily_Merge_At_12AM()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		//Deleting the temp_backup directory to make way for creation of new backup files.
		$host = explode(".", TEST_HOST_2);
		directory_function::delete_directory("/tmp/temp_backup_storage_daily", "10.36.166.46");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(20);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "Daily Merge Scheduler.* STATUS:NOT-ENOUGH-FILES", $primary_mapping_ss), "STATUS:NOT-ENOUGH-FILES")>0, "Daily Merge ran despite no date change");
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		storage_server_setup::clear_backup_log($primary_mapping_ss);
		//Modify the time to make it go to 11:59:00PM of the same day.
		general_function::set_system_time($primary_mapping_ss, "23:59:30");
		sleep(120);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "DailyMerge.* Info. Merge complete", $primary_mapping_ss), "Merge complete")>0, "Daily Merge failed");
		general_function::reset_system_time($primary_mapping_ss);

	}


	public function test_Scheduler_Runs_Master_Merge_At_12AM_On_Sunday()      {
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$this->assertTrue(storage_server_functions::stop_scheduler(), "Unable to stop scheduler");
		//Deleting the temp_backup directory to make way for creation of new backup files.
		//directory_function::delete_directory("/tmp/temp_backup_storage_master", "10.36.166.46");
		$host = explode(".", TEST_HOST_2);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		general_function::reset_system_time($primary_mapping_ss);
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		general_function::set_system_date($primary_mapping_ss, "last Sunday - 1 days");
		general_function::set_system_time($primary_mapping_ss, "23:59:00");
		$this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
		sleep(20);
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		file_function::add_modified_file_to_list(TEST_HOST_2, DEFAULT_INI_FILE);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* has already been completed", $primary_mapping_ss), "has already been completed")>0, "Master Merge ran despite no date change");
		storage_server_setup::clear_dirty_entry($primary_mapping_ss);
		storage_server_setup::clear_backup_log($primary_mapping_ss);
		sleep(120);
		$this->assertTrue(strpos(file_function::query_log_files("/var/log/membasebackup.log", "MasterMerge.* Merge complete", $primary_mapping_ss), "Merge complete")>0, "Master Merge failed");
		general_function::reset_system_time($primary_mapping_ss);

	}
}


class Scheduler_Full extends Scheduler {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

