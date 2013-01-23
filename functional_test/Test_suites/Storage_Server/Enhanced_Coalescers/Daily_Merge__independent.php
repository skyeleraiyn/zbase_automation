<?php
//For these test cases, in the pre init part we need to set the value of MIN_INCR_BACKUPS_COUNT to 1 and also delete all the pyc files. 

abstract class Daily_Merge  extends ZStore_TestCase {

	public function test_Daily_Merge_Invalid_Path_Disk()	{
		$flag = False;
		diskmapper_setup::reset_diskmapper_storage_servers(); 
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$date = date("Y-m-d", time()-86400);
		$command_to_be_executed = "sudo python26 /opt/membase/membase-backup/daily-merge -p /tmp/primary/host/zc2   -d $date";
		$status = remote_function::remote_execution($storage_server_pool[0], $command_to_be_executed);
		if(stristr($status, "Usage"))	{ $flag = True;	}
		$this->assertTrue($flag, "Wrong error thrown for invalid inputs to daily merge");
	}

	public function test_Daily_Merge_Missing_Parameters()	{
		$flag = False;
		//diskmapper_setup::reset_diskmapper_storage_servers(); 
		//membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$date = date("Y-m-d", time()-86400);
		$command_to_be_executed = "sudo python26 /opt/membase/membase-backup/daily-merge";
		$status = remote_function::remote_execution($storage_server_pool[0], $command_to_be_executed);
		if(stristr($status, "Usage"))    { $flag = True; }
		$this->assertTrue($flag, "Wrong error thrown for invalid inputs to daily merge");
	}

	public function test_Daily_Merge()	{
		diskmapper_setup::reset_diskmapper_storage_servers(); 
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		//Verification
		$count_daily = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "daily");
		$count_incremental = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "incremental");
		$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");
	}

	public function test_MIN_INCR_BACKUPS_COUNT()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 15, "modify");
		directory_function::delete_directory("/opt/membase/membase-backup/*.pyc", $primary_mapping_ss);
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertFalse($status, "Daily Merge Passed");
		file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 1, "modify");
		//This cleanup is necessary because CentOS6 seems to have a kernel bug which does not take in any changes made to the .py files.
		//Instead it considers data from the .pyc files which does not have these changes. Hence deleing all pyc files is necesssary
		directory_function::delete_directory($primary_mapping_ss, "/opt/membase/membase-backup/*.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.4/compiler/consts.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.6/compiler/consts.pyc");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
	}

	public function test_MIN_INCR_BACKUPS_COUNT_0()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 0, "modify");
		directory_function::delete_directory($primary_mapping_ss, "/opt/membase/membase-backup/*.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.4/compiler/consts.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.6/compiler/consts.pyc");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
	}

	public function test_Daily_Merge_Run_Twice()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 1, "modify");
		directory_function::delete_directory($primary_mapping_ss, "/opt/membase/membase-backup/*.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.4/compiler/consts.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.6/compiler/consts.pyc");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertFalse($status, "Daily Merge Ran Again");
	}

	public function test_Daily_Merge_For_Newer_Backup_Files()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		//This code will not be required if the pre init script is taking care of setting the MIN_INCR_BACKUPS_COUNT to 1
		/*
		file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 1, "modify");
		directory_function::delete_directory($primary_mapping_ss, "/opt/membase/membase-backup/*.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.4/compiler/consts.pyc");
		directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.6/compiler/consts.pyc");
		*/
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertFalse($status, "Daily Merge Ran Successfully");
	}

	public function test_Daily_Merge_Incrementals_Not_Deleted_Manifest_File()	{	
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$incremental_backup_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$number_of_initial_incremental_backups = count($incremental_backup_list);
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		//Read contents of manifest file
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$hostname = TEST_HOST_2; 
		$manifest_del = array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, "cat /$primary_mapping_disk/primary/$hostname/va2/incremental/manifest.del"))));
		$this->assertEquals(count(array_diff(array_values($incremental_backup_list), array_values($manifest_del))), 0 , "Difference in count of incremental backups and files present in manifest.del file");
		$no_of_incremental_backups_after_merge = count(enhanced_coalescers::list_incremental_backups(TEST_HOST_2));
		$this->assertEquals($no_of_incremental_backups_after_merge, $number_of_initial_incremental_backups, "Count of incremental backups do not match before and after merge");	
	}


	public function test_Daily_Merge_With_Existing_Manifest_File()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$incremental_backup_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$hostname = TEST_HOST_2;
		$manifest_del = array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, "cat /$primary_mapping_disk/primary/$hostname/va2/incremental/manifest.del"))));
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 8001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 10001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);	
		$new_incremental_backup_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 2);
		$this->assertTrue($status, "Daily Merge Failed");
		$manifest_del_new = array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, "cat /$primary_mapping_disk/primary/$hostname/va2/incremental/manifest.del"))));
		$this->assertEquals(count(array_diff($manifest_del_new, array_diff($new_incremental_backup_list, $incremental_backup_list))), 0, "Older backups retained even after second run of daily merge");
		$incremental_list_after_merge = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$this->assertEquals(count(array_diff($incremental_list_after_merge, array_diff($new_incremental_backup_list, $incremental_backup_list))), 0, "Mismatch in incremental backups after 2nd merge");

	}


	public function test_Daily_Merge_No_Backups()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertFalse($status, "Daily Merge with no backups ran successfully");
	}

	public function test_Daily_Merge_Missing_Incremental_Backups()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$incremental_backup_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		directory_function::delete_directory(substr($incremental_backup_list[1], 0, -10)."*", $primary_mapping_ss);
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1); 
		$flag = False; 
		if(stristr($status, "Checkpoint mismatch"))	{ $flag=True;}
		$this->assertTrue($flag, "Daily Merge Failed");
	}

	public function test_Daily_Merge_With_Pause()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2000);
		$this->assertTrue(Data_generation::add_keys(2000, 10000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 10000);
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");	
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 102001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 202001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$pid = pcntl_fork(); 	
		if($pid == -1)	{ die("Could not fork");}
		else if($pid)	{
			sleep(5);
			process_functions::pause_merge(TEST_HOST_2, "daily");
			sleep(30);
			$this->assertTrue(process_functions::verify_merge_paused(TEST_HOST_2, "daily"), "Daily merge not paused");
			$this->assertTrue(process_functions::check_merge_pid(TEST_HOST_2, "daily"), "Daily merge pid file does not exist");
			sleep(30);
			process_functions::resume_merge(TEST_HOST_2, "daily");
			$this->assertTrue(process_functions::verify_merge_resumed(TEST_HOST_2,  "daily"), "Daily merge not resumed");
		} else	{
			$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
			$this->assertTrue($status, "Daily Merge Failed");
			$count_daily = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "daily");
			$count_incremental = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "incremental");
			$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");	
			exit(0);
		}
	}

	public function test_Daily_Merge_With_Multiple_Pauses()   {
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		$this->assertContains($primary_mapping_disk, (string)file_function::query_log_files("/var/log/membasebackup.log", $primary_mapping_disk, $primary_mapping_ss), "Log does not contain disk tag $primary_mapping_disk");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2000);
		$this->assertTrue(Data_generation::add_keys(2000, 10000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 10000);
		$this->assertTrue(Data_generation::add_keys(1000000, 10000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(1000000, 10000, 1020001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(1000000, 10000, 2020001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(15);
			for($p=0;$p<3;$p++)	{
				process_functions::pause_merge(TEST_HOST_2, "daily");
				sleep(5);
				$this->assertTrue(process_functions::verify_merge_paused(TEST_HOST_2, "daily"), "Daily merge not paused");
				$this->assertTrue(process_functions::check_merge_pid(TEST_HOST_2, "daily"), "Daily merge pid file does not exist");
				process_functions::resume_merge(TEST_HOST_2, "daily");
				$this->assertTrue(process_functions::verify_merge_resumed(TEST_HOST_2,  "daily"), "Daily merge not resumed");
				sleep(5);
			}
		} else  {
			$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
			$this->assertTrue($status, "Daily Merge Failed");
			$count_daily = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "daily");
			$count_incremental = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "incremental");
			$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");
			exit(0);
		}
	}

	public function test_Kill_Daily_Merge()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2000);
		$this->assertTrue(Data_generation::add_keys(2000, 10000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 10000);
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");       
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 102001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 202001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$pid = pcntl_fork();    
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			$this->assertTrue(process_functions::kill_merge_process(TEST_HOST_2, "daily"), "Merge not killed");	
		} else	{
			$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
			$this->assertFalse($status, "Daily Merge not killed");
			$this->assertFalse(process_functions::check_merge_pid(TEST_HOST_2, "daily"), "Daily merge pid file still exists after being killed");
			exit(0);
		}
	}	

	public function test_Daily_Merge_With_Existing_Files_In_Daily_Directory()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$date = date("Y-m-d", time()+86400);
		directory_function::delete_directory("/$primary_mapping_disk/primary/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/".$date."/done", $primary_mapping_ss);	
		directory_function::delete_directory("/$primary_mapping_disk/primary/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/done-$date", $primary_mapping_ss);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 8001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$count_daily = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "daily");
		$count_incremental = enhanced_coalescers::get_key_count_from_sqlite_files(TEST_HOST_2, "incremental");
		$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");	
	}

	public function test_Daily_Merge_Logging()	{
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 4001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$this->assertContains($primary_mapping_disk, (string)file_function::query_log_files("/var/log/membasebackup.log", $primary_mapping_disk, $primary_mapping_ss), "Log does not contain disk tag $primary_mapping_disk");	
	}

	public function test_Disk_Error_While_Daily_Merge()	{
		/*
		diskmapper_setup::reset_diskmapper_storage_servers();
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2000);
		$this->assertTrue(Data_generation::add_keys(2000, 10000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 10000);
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 2001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 102001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(100000, 10000, 202001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");	
		*/
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];				
		remote_function::remote_execution($primary_mapping_ss, "mount"); // need this to log disk mount details to log file
		$mount_partition = trim(remote_function::remote_execution($primary_mapping_ss, "mount | grep $primary_mapping_disk | awk '{print $1}'"));
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			remote_function::remote_execution($primary_mapping_ss, "sudo umount -l $mount_partition");	
			sleep(30);
			$this->assertEquals(trim(remote_function::remote_execution($primary_mapping_ss, "cat /var/tmp/disk_mapper/bad_disk")), $primary_mapping_disk, "Bad disk not updated with unmounted file");
			//Cleaning up after test case.
			remote_function::remote_execution($primary_mapping_ss, "sudo mount $mount_partition /".$primary_mapping_disk);
			remote_function::remote_execution($primary_mapping_ss, "sudo su -c \"echo > /var/tmp/disk_mapper/bad_disk\"");
		} else  {
			$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
			$this->assertFalse($status, "Daily Merge ran successfully despite disk being down");
			exit(0);
		}		
	}
}

class Daily_Merge_Full extends Daily_Merge {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
