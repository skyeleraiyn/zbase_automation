<?php
//For these test cases, in the pre init part we need to set the value of MIN_INCR_BACKUPS_COUNT to 1 and also delete all the pyc files.

abstract class Master_Merge  extends ZStore_TestCase {

	public function test_Simple_Master_Merge() {
		//AIM // Run master merge with a few daily backups and their corresponding .split files
		//EXPECTED RESULT // Master merge takes place properly
        cluster_setup::setup_membase_cluster_with_ibr(False,True);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "master"), 1, "Preparing data for merge failed");
        $group = diskmapper_functions::get_vbucket_group("vb_0");
        $primary_mapping = diskmapper_functions::get_primary_partition_mapping($group);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$status = storage_server_functions::run_master_merge_multivb(0, 1);
		$this->assertTrue($status, "Master Merge not done");
		//$master_backups = enhanced_coalescers::list_master_backups(TEST_HOST_2, storage_server_functions::get_sunday_date());
		$count_merge = enhanced_coalescers::sqlite_total_count_multivb(0, "master", date("Y-m-d", time()+86400));
		$this->assertEquals($count_merge, "1000000", "Not all data merged in master-merge");
		$this->assertContains($primary_mapping_disk, (string)file_function::query_log_files("/var/log/membasebackup.log", $primary_mapping_disk, $primary_mapping_ss), "Log does not contain disk tag $primary_mapping_disk");
		$status = storage_server_functions::run_master_merge_multivb(0, 1);
		$this->assertFalse($status, "Master Merge ran again successfully despite being already run for the day.");
	}

	public function test_Master_Merge_Invalid_Path_Disk()    {
		$flag = False;
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$date = date("Y-m-d", time()-86400);
		$command_to_be_executed = "sudo python26 /opt/membase/membase-backup/master-merge -p /tmp/primary/host/zc2   -d $date";
		$status = remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		if(stristr($status, "Usage"))   { $flag = True; }
		$this->assertTrue($flag, "Wrong error thrown for invalid inputs to daily merge");
	}


	public function test_Merge_Merge_Missing_Parameters()   {
		$flag = False;
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$date = date("Y-m-d", time()-86400);
		$command_to_be_executed = "sudo python26 /opt/membase/membase-backup/master-merge";
		$status = remote_function::remote_execution(STORAGE_SERVER_1, $command_to_be_executed);
		if(stristr($status, "Usage"))    { $flag = True; }
		$this->assertTrue($flag, "Wrong error thrown for invalid inputs to daily merge");
	}
	public function test_Master_Merge_No_Backups()   {
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$status = storage_server_functions::run_master_merge_multivb(0, 1);
		$this->assertFalse($status, "Master Merge with no backups ran successfully");
	}

	public function test_Master_Merge_With_Pause() {
		//AIM // Run master merge with a few daily backups and their corresponding .split files
		//EXPECTED RESULT // Master merge takes place properly
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "master"), 1, "Preparing data for merge failed");
        $group = diskmapper_functions::get_vbucket_group("vb_0");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			storage_server_functions::pause_merge($group, "master");
			sleep(30);
			$this->assertTrue(storage_server_functions::verify_merge_paused($group, "master"), "Master merge not paused");
			$this->assertTrue(storage_server_functions::check_merge_pid($group, "master"), "Master merge pid file does not exist");
			sleep(30);
			storage_server_functions::resume_merge($group, "master");
			$this->assertTrue(storage_server_functions::verify_merge_resumed($group,  "master"), "Master merge not resumed");
			sleep(10);
		}
		else	{
			$status = storage_server_functions::run_master_merge_multivb(0, 1);
			$this->assertTrue($status, "Master Merge failed");
            $count_master = enhanced_coalescers::sqlite_total_count_multivb(0, "master", date("Y-m-d", time()+86400));
			$this->assertEquals($count_master, 1000000, "Key count mismatch between daily merge and master merge files");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}
// Not working . Needs investigation
	public function test_Master_Merge_With_Multiple_Pauses() {
		//AIM // Run master merge with a few daily backups and their corresponding .split files
		//EXPECTED RESULT // Master merge takes place properly
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "master"), 1, "Preparing data for merge failed");
        $group = diskmapper_functions::get_vbucket_group("vb_0");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			for($i = 0; $i<5; $i++)	{
				storage_server_functions::pause_merge($group, "master");
				sleep(2);
				$this->assertTrue(storage_server_functions::verify_merge_paused($group, "master"), "Master merge not paused");
				$this->assertTrue(storage_server_functions::check_merge_pid($group, "master"), "Master merge pid file does not exist");
				storage_server_functions::resume_merge($group, "master");
				$this->assertTrue(storage_server_functions::verify_merge_resumed($group,  "master"), "Master merge not resumed");
				sleep(2);
			}
		}
		else    {
			$status = storage_server_functions::run_master_merge_multivb(0, 1);
			$this->assertTrue($status, "Master Merge failed");
			$count_master = enhanced_coalescers::sqlite_total_count_multivb(0, "master", date("Y-m-d", time()+86400));
			$this->assertEquals($count_master, 1000000, "Key count mismatch between daily merge and master merge files");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}

	public function test_Kill_Master_Merge() {
		//AIM // Run master merge with a few daily backups and their corresponding .split files
		//EXPECTED RESULT // Master merge takes place properly
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "master"), 1, "Preparing data for merge failed");
        $group = diskmapper_functions::get_vbucket_group("vb_0");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			$this->assertTrue(storage_server_functions::kill_merge_process($group, "master"), "Master merge not killed");
		} else {
			$status = storage_server_functions::run_master_merge_multivb(0, 1);
			$this->assertFalse($status, "Master Merge ran successfully despite being killed");
			$this->assertFalse(storage_server_functions::check_merge_pid($group, "master"), "Master merge pid file still exists after being killed");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}

	public function test_Kill_Master_Merge_While_Paused()	{
		//AIM // Run master merge with a few daily backups and their corresponding .split files
		//EXPECTED RESULT // Master merge takes place properly
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "master"), 1, "Preparing data for merge failed");
        $group = diskmapper_functions::get_vbucket_group("vb_0");
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(5);
			storage_server_functions::pause_merge($group, "master");
			sleep(30);
			$this->assertTrue(storage_server_functions::verify_merge_paused($group, "master"), "Master merge not paused");
			$this->assertTrue(storage_server_functions::kill_merge_process($group, "master", True), "Master merge not killed");

		} else {
			$status = storage_server_functions::run_master_merge_multivb(0, 1);
			$this->assertFalse($status, "Master Merge ran successfully despite being killed");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}

	public function test_Disk_Error_While_Master_Merge()	{
		cluster_setup::setup_membase_cluster_with_ibr(False);
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup_multivb(0, "master"), 1, "Preparing data for merge failed");
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping(diskmapper_functions::get_vbucket_group("vb_0"));
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		remote_function::remote_execution($primary_mapping_ss, "mount"); // need this to log disk mount details to log file
		$mount_partition = trim(remote_function::remote_execution($primary_mapping_ss, "mount | grep $primary_mapping_disk | awk '{print $1}'"));
		$pid = pcntl_fork();
		if($pid == -1)  { die("Could not fork");}
		else if($pid)   {
			sleep(2);
			remote_function::remote_execution($primary_mapping_ss, "sudo umount -l $mount_partition");
			sleep(30);
			$this->assertEquals(trim(remote_function::remote_execution($primary_mapping_ss, "cat /var/tmp/disk_mapper/bad_disk")), $primary_mapping_disk, "Bad disk not updated with unmounted file");
			//Cleaning up after test case.
			remote_function::remote_execution($primary_mapping_ss, "sudo mount $mount_partition /".$primary_mapping_disk);
			remote_function::remote_execution($primary_mapping_ss, "sudo su -c \"echo > /var/tmp/disk_mapper/bad_disk\"");
		} else  {
			$status = storage_server_functions::run_master_merge_multivb(0, 1);
			$this->assertFalse($status, "Master Merge ran successfully despite disk being down");
			exit(0);
		}
		while (pcntl_waitpid(0, $status) != -1) {
			pcntl_wexitstatus($status);
		}
	}
}

class Master_Merge_Full extends Master_Merge {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
