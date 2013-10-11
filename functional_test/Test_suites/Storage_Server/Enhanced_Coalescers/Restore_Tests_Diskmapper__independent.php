/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php
//For backup testcases for enhanced coalescers.

abstract class Restore_Tests_Diskmapper  extends ZStore_TestCase {                             

	public function test_simple_restore() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
		$this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"),1,"backups not prepared");
                $status = mb_restore_commands::restore_server(TEST_HOST_2);
		$this->assertContains("Restore completed successfully", $status, "Restore not successful");
	}

	public function test_restore_pause_daily_merge() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
			//Parent
                        $this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
                        $this->assertTrue(storage_server_functions::verify_merge_paused(TEST_HOST_2, "daily"), "daily merge not paused");
                }
                else    {
			//Child
	                $status = mb_restore_commands::restore_server(TEST_HOST_2);			
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }

        public function test_restore_resume_daily_merge() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
                        //Parent
                        $this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
                        $this->assertTrue(storage_server_functions::verify_merge_paused(TEST_HOST_2, "daily"), "daily merge not paused");
                }
                else    {
                        //Child
                        $status = mb_restore_commands::restore_server(TEST_HOST_2);
                        $this->assertTrue(storage_server_functions::verify_merge_resumed(TEST_HOST_2, "daily"), "daily merge not resumed");
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }



        public function test_restore_scheduler_stopped_daily() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
		$this->assertTrue(storage_server_functions::stop_scheduler(TEST_HOST_2), "Unable to start scheduler");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
                        //Parent
			sleep(3);
                        $this->assertFalse(storage_server_functions::verify_merge_paused(TEST_HOST_2, "daily"), "daily merge not paused");
                }
                else    {
                        //Child
                        $status = mb_restore_commands::restore_server(TEST_HOST_2);
                        $this->assertFalse(storage_server_functions::verify_merge_resumed(TEST_HOST_2, "daily"), "daily merge not resumed");
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }


	public function test_restore_pause_master_merge() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
			//Parent
                        $this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
			sleep(10);
                        $this->assertTrue(storage_server_functions::verify_merge_paused(TEST_HOST_2, "master"), "Master merge not paused");
                }
                else    {
			//Child
	                $status = mb_restore_commands::restore_server(TEST_HOST_2);			
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }


        public function test_restore_resume_master_merge() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
                        //Parent
                        $this->assertTrue(storage_server_functions::start_scheduler(TEST_HOST_2), "Unable to start scheduler");
                        $this->assertTrue(storage_server_functions::verify_merge_paused(TEST_HOST_2, "master"), "master merge not paused");
                }
                else    {
                        //Child
                        $status = mb_restore_commands::restore_server(TEST_HOST_2);
                        $this->assertTrue(storage_server_functions::verify_merge_resumed(TEST_HOST_2, "master"), "master merge not resumed");
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }


        public function test_restore_scheduler_stopped_master() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$this->assertTrue(storage_server_functions::stop_scheduler(TEST_HOST_2), "Unable to start scheduler");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
                        //Parent
			sleep(3);
                        $this->assertFalse(storage_server_functions::verify_merge_paused(TEST_HOST_2, "master"), "master merge not paused");
                }
                else    {
                        //Child
                        $status = mb_restore_commands::restore_server(TEST_HOST_2);
                        $this->assertFalse(storage_server_functions::verify_merge_resumed(TEST_HOST_2, "master"), "master merge not resumed");
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }


	public function test_start_restore_invalid_params() {
		zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
		backup_tools_functions::set_backup_const(TEST_HOST_2, "ZRT_MAPPER_KEY", '\'KEY_JUNK\'');
                backup_tools_functions::set_backup_const(TEST_HOST_2, "ZRT_RETRIES", 10);
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "master"), 1, "Preparing data for merge failed");
		$status = mb_restore_commands::restore_server(TEST_HOST_2);
		$failure = backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED");
		$this->assertContains("FAILED: Unable to read from zRuntime",$failure, "Failure message not found in zbasebackup log");
		$restore_fail = backup_tools_functions::upload_stat_from_zbasebackup_log("Restore process terminated");
		$this->assertContains("Restore process terminated", $restore_fail, "Restore failure not found");
	}

	public function test_restore_retry() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
                $pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
                        //Parent
			sleep(4);
			diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
                }
                else    {
                        //Child
                        $status = mb_restore_commands::restore_server(TEST_HOST_2);
	                $download_retry = backup_tools_functions::upload_stat_from_zbasebackup_log("Retrying download ");
			$this->assertContains("Retrying download for s3://", $download_retry, "Download retry message not found");
			$lock_retry = backup_tools_functions::upload_stat_from_zbasebackup_log("Retrying to remove incremental");
			$this->assertContains("Retrying to remove incremental backup directory lock in s3", $lock_retry, "lock removal retry message not found");
			$failure =  backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED", 2);
			$this->assertContains("Unable to remove s3 incremental directory lock", $failure, "Failure in lock removal not found");
                        $this->assertContains("Downloading file s3://", $failure, "Failure in Download not found");
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }

	public function test_restore_primary_going_down() {
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1,TEST_HOST_2);
                backup_tools_functions::set_backup_const(STORAGE_SERVER_1, "MIN_INCR_BACKUPS_COUNT", "2");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily"), 1, "Preparing data for merge failed");
                $this->assertEquals(synthetic_backup_generator::prepare_merge_backup(TEST_HOST_2, "daily", "secondary"), 1, "Preparing data for merge failed");
		$pid = pcntl_fork();
                if($pid == -1)  { die("Could not fork");}
                else if($pid)   {
                        //Parent
                        sleep(4);
	                $this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_2,'primary'),"Failed adding bad disk entry");
                }
                else    {
                        //Child
                        $status = mb_restore_commands::restore_server(TEST_HOST_2);
                        $download_retry = backup_tools_functions::upload_stat_from_zbasebackup_log("Retrying download ");
                        $this->assertContains("Retrying download for s3://", $download_retry, "Download retry message not found");
                        $lock_retry = backup_tools_functions::upload_stat_from_zbasebackup_log("Retrying to remove incremental");
                        $this->assertContains("Retrying to remove incremental backup directory lock in s3", $lock_retry, "lock removal retry message not found");
                        $failure =  backup_tools_functions::upload_stat_from_zbasebackup_log("FAILED", 2);
                        $this->assertContains("Unable to remove s3 incremental directory lock", $failure, "Failure in lock removal not found");
                        $this->assertContains("Downloading file s3://", $failure, "Failure in Download not found");
                        exit(0);
                }
                while (pcntl_waitpid(0, $status) != -1) {
                        pcntl_wexitstatus($status);
                }
        }

        public function test_checkpoint_after_restore() {
                //AIM : To verify restore checkpoints are properly set after a restore
                // EXPECTED RESULT : Restore is successful
                zbase_setup::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
                flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
                $this->assertTrue(Data_generation::add_keys(200, 100, 1, 10),"Failed adding keys");
                zbase_backup_setup::start_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $this->assertTrue(Data_generation::add_keys(100, 100, 201, 10),"Failed adding keys");
                $this->assertTrue(Data_generation::add_keys(200, 100, 301, 10),"Failed adding keys");
                $this->assertTrue(Data_generation::add_keys(200, 100, 501, 10),"Failed adding keys");
                zbase_backup_setup::restart_backup_daemon(TEST_HOST_2);
                $this->assertTrue(backup_tools_functions::verify_zbase_backup_upload(), "Failed to upload the backup files to Storage Server");
                $checkpoint_stats = stats_functions::get_checkpoint_stats(TEST_HOST_2);
                zbase_setup::reset_zbase_servers(array(TEST_HOST_1, TEST_HOST_2));
                $status = mb_restore_commands::restore_server(TEST_HOST_2);
                $this->assertTrue(strpos($status,"Restore completed successfully")>0,"Restore not completed");
                $raw_stats= stats_functions::get_stats_array(TEST_HOST_2, "restore");
		$restore_stats = $raw_stats["ep_restore"];
		$this->assertEquals($checkpoint_stats["last_closed_checkpoint_id"], $restore_stats["restore_checkpoint"], "Restore checkpoint not updated after restore");
        }


}

class Restore_Tests_Diskmapper_Full extends Restore_Tests_Diskmapper {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
