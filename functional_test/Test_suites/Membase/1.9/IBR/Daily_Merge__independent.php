<?php
//For these test cases, in the pre init part we need to set the value of MIN_INCR_BACKUPS_COUNT to 1 and also delete all the pyc files.

abstract class Daily_Merge  extends ZStore_TestCase {

	public function test_Daily_Merge_Invalid_Path_Disk()	{
		$flag = False;
        cluster_setup::setup_membase_cluster_with_ibr(False,True);
		$date = date("Y-m-d", time()-86400);
		$command_to_be_executed = "sudo python26 /opt/membase/membase-backup/daily-merge -p /tmp/primary/host/zc2   -d $date";
		$status = remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		if(stristr($status, "Usage"))	{ $flag = True;	}
		$this->assertTrue($flag, "Wrong error thrown for invalid inputs to daily merge");
	}

	public function test_Daily_Merge_Missing_Parameters()	{
		$flag = False;
		$date = date("Y-m-d", time()-86400);
		$command_to_be_executed = "sudo python26 /opt/membase/membase-backup/daily-merge";
		$status = remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		if(stristr($status, "Usage"))    { $flag = True; }
		$this->assertTrue($flag, "Wrong error thrown for invalid inputs to daily merge");
	}

	public function test_Daily_Merge()	{
        global $storage_server_pool;
        cluster_setup::setup_membase_cluster_with_ibr(False);
        $vb_group = diskmapper_functions::get_vbucket_group("vb_0");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
		sleep(10);
	    foreach($storage_server_pool as $ss) {
            backup_tools_functions::set_backup_const($ss, "MIN_INCR_BACKUPS_COUNT", 2, False);
        }
		$status = storage_server_functions::run_daily_merge_multivb(0,1);
		$this->assertTrue($status, "Daily Merge Failed");
		//Verification
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(diskmapper_functions::get_vbucket_group("vb_0"));
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$this->assertTrue(file_function::check_file_exists($primary_mapping_ss, "/$primary_mapping_disk/primary/$vb_group/vb_0/daily/*/done"), "Done file not put after daily merge");
		$count_daily = enhanced_coalescers::sqlite_total_count_multivb(0, "daily");
		$count_incremental = enhanced_coalescers::sqlite_total_count_multivb(0, "incremental");
		$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");
	}

	public function test_MIN_INCR_BACKUPS_COUNT()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(diskmapper_functions::get_vbucket_group("vb_0"));
		$primary_mapping_ss = $primary_mapping['storage_server'];
		backup_tools_functions::set_backup_const($primary_mapping_ss, "MIN_INCR_BACKUPS_COUNT", 15);
		directory_function::delete_directory("/opt/membase/membase-backup/*.pyc", $primary_mapping_ss);
		$status = storage_server_functions::run_daily_merge_multivb(0,1);
		$this->assertFalse($status, "Daily Merge Passed");
		file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 1, "modify");
		//This cleanup is necessary because CentOS6 seems to have a kernel bug which does not take in any changes made to the .py files.
		//Instead it considers data from the .pyc files which does not have these changes. Hence deleing all pyc files is necesssary
		directory_function::delete_directory("/opt/membase/membase-backup/*.pyc", $primary_mapping_ss);
		directory_function::delete_directory("/usr/lib64/python2.4/compiler/consts.pyc", $primary_mapping_ss);
		directory_function::delete_directory("/usr/lib64/python2.6/compiler/consts.pyc", $primary_mapping_ss);
        backup_tools_functions::set_backup_const($primary_mapping_ss, "MIN_INCR_BACKUPS_COUNT", 2);
		$status = storage_server_functions::run_daily_merge_multivb(0, 1);
		$this->assertTrue($status, "Daily Merge Failed");
	}

	public function test_MIN_INCR_BACKUPS_COUNT_0()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(diskmapper_functions::get_vbucket_group("vb_0"));
		$primary_mapping_ss = $primary_mapping['storage_server'];
                directory_function::delete_directory("/opt/membase/membase-backup/*.pyc", $primary_mapping_ss);
		backup_tools_functions::set_backup_const($primary_mapping_ss, "MIN_INCR_BACKUPS_COUNT", 0);
                directory_function::delete_directory("/opt/membase/membase-backup/*.pyc", $primary_mapping_ss);
                directory_function::delete_directory("/usr/lib64/python2.4/compiler/consts.pyc", $primary_mapping_ss);
                directory_function::delete_directory("/usr/lib64/python2.6/compiler/consts.pyc", $primary_mapping_ss);
		$status = storage_server_functions::run_daily_merge_multivb(0, 1);
		$this->assertTrue($status, "Daily Merge Failed");
	}

	public function test_Daily_Merge_Run_Twice()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(diskmapper_functions::get_vbucket_group("vb_0"));
		$primary_mapping_ss = $primary_mapping['storage_server'];
                backup_tools_functions::set_backup_const($primary_mapping_ss, "MIN_INCR_BACKUPS_COUNT", 1);
                directory_function::delete_directory("/opt/membase/membase-backup/*.pyc", $primary_mapping_ss);
                directory_function::delete_directory("/usr/lib64/python2.4/compiler/consts.pyc", $primary_mapping_ss);
                directory_function::delete_directory("/usr/lib64/python2.6/compiler/consts.pyc", $primary_mapping_ss);
		$status = storage_server_functions::run_daily_merge_multivb(0, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$status = storage_server_functions::run_daily_merge_multivb(0, 1);
		$this->assertFalse($status, "Daily Merge Ran Again");
	}

	/*
This test case is the same as the one that is below. Hence removing it.
public function test_Daily_Merge_For_Newer_Backup_Files()	{
cluster_setup::setup_membase_cluster_with_ibr(False);
$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
$primary_mapping_ss = $primary_mapping['storage_server'];
//This code will not be required if the pre init script is taking care of setting the MIN_INCR_BACKUPS_COUNT to 1
file_function::edit_config_file($primary_mapping_ss, "/opt/membase/membase-backup/consts.py", "MIN_INCR_BACKUPS_COUNT", 1, "modify");
directory_function::delete_directory($primary_mapping_ss, "/opt/membase/membase-backup/*.pyc");
directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.4/compiler/consts.pyc");
directory_function::delete_directory($primary_mapping_ss, "/usr/lib64/python2.6/compiler/consts.pyc");
$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
$this->assertFalse($status, "Daily Merge Ran Successfully");
}
*/
	public function test_Daily_Merge_Incrementals_Not_Deleted_Manifest_File()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
		$incremental_backup_list = enhanced_coalescers::list_incremental_backups_multivb(0);
		$number_of_initial_incremental_backups = count($incremental_backup_list);
		$status = storage_server_functions::run_daily_merge_multivb(0, 1);
        $group = diskmapper_functions::get_vbucket_group("vb_0");
		$this->assertTrue($status, "Daily Merge Failed");
		//Read contents of manifest file
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($group);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$manifest_del = array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, "cat /$primary_mapping_disk/primary/$group/vb_0/incremental/manifest.del"))));
		foreach($incremental_backup_list as &$backup)
			$backup = basename($backup);
		$this->assertEquals(count(array_diff(array_values($incremental_backup_list), array_values($manifest_del))), 0 , "Difference in count of incremental backups and files present in manifest.del file");
		$no_of_incremental_backups_after_merge = count(enhanced_coalescers::list_incremental_backups_multivb(0));
		$this->assertEquals($no_of_incremental_backups_after_merge, $number_of_initial_incremental_backups, "Count of incremental backups do not match before and after merge");
	}

	//The following 2 test cases can't be run as is with multivb.
    /*
	public function est_Daily_Merge_With_Existing_Manifest_File()	{
#		cluster_setup::setup_membase_cluster_with_ibr(False);
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
		$hostname = general_function::get_hostname(TEST_HOST_2);
		$manifest_del = array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, "cat /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/incremental/manifest.del"))));
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 8001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 10001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$new_incremental_backup_list = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 2);
		$this->assertTrue($status, "Daily Merge Failed");
		$manifest_del_new = array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, "cat /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/incremental/manifest.del"))));
                foreach($incremental_backup_list as &$backup)
                        $backup = basename($backup);
                foreach($new_incremental_backup_list as &$backup)
                        $backup = basename($backup);
		$this->assertEquals(count(array_diff($manifest_del_new, array_diff($new_incremental_backup_list, $incremental_backup_list))), 0, "Older backups retained even after second run of daily merge");
		$incremental_list_after_merge = enhanced_coalescers::list_incremental_backups(TEST_HOST_2);
                foreach($incremental_list_after_merge as &$backup)
                        $backup = basename($backup);
		$this->assertEquals(count(array_diff($incremental_list_after_merge, array_diff($new_incremental_backup_list, $incremental_backup_list))), 0, "Mismatch in incremental backups after 2nd merge");

	}


	public function est_Daily_Merge_No_Backups()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		membase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertFalse($status, "Daily Merge with no backups ran successfully");
	}
*/
	public function test_Daily_Merge_Missing_Incremental_Backups()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
		$incremental_backup_list = enhanced_coalescers::list_incremental_backups_multivb(0);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(diskmapper_functions::get_vbucket_group("vb_0"));
		$primary_mapping_ss = $primary_mapping['storage_server'];
		directory_function::delete_directory(substr($incremental_backup_list[1], 0, -10)."*", $primary_mapping_ss);
		$status = storage_server_functions::run_daily_merge_multivb(0, 1);
		$this->assertFalse($status, "Daily Merge Failed");
	}

	public function test_Daily_Merge_With_Pause()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
        $group = diskmapper_functions::get_vbucket_group("vb_0");
		$pid = pcntl_fork();
		if($pid == -1)	{ die("Could not fork");}
		else if($pid)	{
			sleep(5);
			storage_server_functions::pause_merge($group, "daily");
			sleep(30);
			$this->assertTrue(storage_server_functions::verify_merge_paused($group, "daily"), "Daily merge not paused");
			$this->assertTrue(storage_server_functions::check_merge_pid($group, "daily"), "Daily merge pid file does not exist");
			sleep(30);
			storage_server_functions::resume_merge($group, "daily");
			$this->assertTrue(storage_server_functions::verify_merge_resumed($group,  "daily"), "Daily merge not resumed");
		}
		else	{
			$status = storage_server_functions::run_daily_merge_multivb(0, 1);
			$this->assertTrue($status, "Daily Merge Failed");
			$count_daily = enhanced_coalescers::sqlite_total_count_multivb(0, "daily");
			$count_incremental = enhanced_coalescers::sqlite_total_count_multivb(0, "incremental");
			$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}

	public function test_Daily_Merge_With_Multiple_Pauses()   {
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "daily"), 1, "Preparing data for merge failed");
        $group =  diskmapper_functions::get_vbucket_group("vb_0");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			for($p=0;$p<3;$p++)	{
				storage_server_functions::pause_merge($group, "daily");
				sleep(5);
				$this->assertTrue(storage_server_functions::verify_merge_paused($group, "daily"), "Daily merge not paused");
				$this->assertTrue(storage_server_functions::check_merge_pid($group, "daily"), "Daily merge pid file does not exist");
				storage_server_functions::resume_merge($group, "daily");
				$this->assertTrue(storage_server_functions::verify_merge_resumed($group,  "daily"), "Daily merge not resumed");
				sleep(5);
			}
		}
		else    {
			$status = storage_server_functions::run_daily_merge_multivb(0, 1);
			$this->assertTrue($status, "Daily Merge Failed");
			$count_daily = enhanced_coalescers::sqlite_total_count_multivb(0, "daily");
			$count_incremental = enhanced_coalescers::sqlite_total_count_multivb(0, "incremental");
			$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");
			$this->assertContains("data_", (string)file_function::query_log_files("/var/log/membasebackup.log", "data_",diskmapper_functions::get_vbucket_ss("vb_0")), "Log does not contain disk tag");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}

	public function test_Kill_Daily_Merge()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			$this->assertTrue(storage_server_functions::kill_merge_process(TEST_HOST_2, "daily"), "Merge not killed");
		}
		else	{
			$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
			$this->assertFalse($status, "Daily Merge not killed");
			$this->assertFalse(storage_server_functions::check_merge_pid(TEST_HOST_2, "daily"), "Daily merge pid file still exists after being killed");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}

	//This test case can also use the existing backup strategy
	public function test_Daily_Merge_With_Existing_Files_In_Daily_Directory()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
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
		$hostname = general_function::get_hostname(TEST_HOST_2);
		directory_function::delete_directory("/$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/daily/".$date."/done", $primary_mapping_ss);
		directory_function::delete_directory("/$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/incremental/done-$date", $primary_mapping_ss);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 6001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 8001, 20),"Failed adding keys");
		membase_backup_setup::restart_backup_daemon(TEST_HOST_2);
		$this->assertTrue(backup_tools_functions::verify_membase_backup_upload(), "Failed to upload the backup files to Storage Server");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$count_daily = enhanced_coalescers::sqlite_total_count(TEST_HOST_2, "daily");
		$count_incremental = enhanced_coalescers::sqlite_total_count(TEST_HOST_2, "incremental");
		$this->assertEquals($count_incremental, $count_daily, "Key count mismatch between daily merge and incremental files");
	}

	public function test_Daily_Merge_Logging()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
		$this->assertTrue($status, "Daily Merge Failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_2);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$this->assertContains($primary_mapping_disk, (string)file_function::query_log_files("/var/log/membasebackup.log", $primary_mapping_disk, $primary_mapping_ss), "Log does not contain disk tag $primary_mapping_disk");
	}

	public function test_Disk_Error_While_Daily_Merge()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
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
		}
		else    {
			$status = storage_server_functions::run_daily_merge(0, TEST_HOST_2, 1);
			$this->assertFalse($status, "Daily Merge ran successfully despite disk being down");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}
}

class Daily_Merge_Full extends Daily_Merge {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
