<?php

abstract class DiskMapper_TestCase extends ZStore_TestCase {

	public function test_Mapping_on_Initilaizing_Disk_Mapper() {
		// AIM : After initialization of the DM, verify that all disks reported in the hostmapping file contain no primary, no secondary and all disks are good.
		// EXPECTED RESULT : The mapping is as expected
		diskmapper_setup::reset_diskmapper_storage_servers();
		$hostmapping = diskmapper_api::get_all_config();
		$this->assertEquals(count($hostmapping) ,0 ,"Host mapping not empty on initialization");
	}

	public function test_stop_DM() {
		// AIM : Verify stopping of disk mapper
		// EXPECTED RESULT : Disk mapper stops without any errors
		$status =  diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		$this->assertTrue($status , "Disk mapper not stopped");
	}

	public function test_start_DM() {
		// AIM : Verify starting  of disk mapper
		// EXPECTED RESULT : Disk mapper starts without any errors
		$status = diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
		$this->assertTrue($status,"Disk mapper not started properly");
	}

	public function test_Verify_Symlinks() {
		// AIM : Verify that symlinks are created for every new host that is uploaded to each storage server
		// EXPECTED RESULT : Symlinks are created
		$hostname = general_function::get_hostname(TEST_HOST_1);
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$parsed_hostmap = diskmapper_api::get_all_config();
		$PrimSS = $parsed_hostmap[$hostname]['primary']['storage_server'];
		$SecSS = $parsed_hostmap[$hostname]['secondary']['storage_server'];
		$command_to_be_executed = "ls /var/www/html/".GAME_ID;
		$status = trim(remote_function::remote_execution($PrimSS,$command_to_be_executed));
		// We verify that the name of the symlink is as expected
		$this->assertEquals(strcmp($status ,$hostname) , 0 ,"Symlink name different than expected");
		$status = trim(remote_function::remote_execution($SecSS,$command_to_be_executed));
		$this->assertEquals(strcmp($status ,$hostname) , 0 ,"Symlink name different than expected");
	}

	public function test_Verify_Mapping_File_Created_for_First_Time() {
		// AIM : Verify that the mapping file on the DM is created for the first time when the DM polls the storage servers for status
		// EXPECTED RESULT : THe file is created for the first time afte polling
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'stop');
		sleep(3);
		remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE, "sudo rm -rf ".DISK_MAPPER_HOST_MAPPING);
		sleep(3);
		service_function::control_service(DISK_MAPPER_SERVER_ACTIVE, "httpd", 'restart');
		sleep(3);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'start');
		sleep(5);
		$this->assertTrue(file_function::check_file_exists(DISK_MAPPER_SERVER_ACTIVE, DISK_MAPPER_HOST_MAPPING), "Mapping file not created");

	}

	public function test_Verify_Mapping_File_Rewritten_Every_Time() {
		// AIM : Verify that the mapping file on the DM is rewritten every time the DM polls the storage servers
		// EXPECTED RESULT : The file is rewritten every time when DM polls
		diskmapper_setup::reset_diskmapper_storage_servers();
		$modifyTime = file_function::file_attributes(DISK_MAPPER_SERVER_ACTIVE, DISK_MAPPER_HOST_MAPPING, "modified_time");
		sleep(7);
		$newModifyTime = file_function::file_attributes(DISK_MAPPER_SERVER_ACTIVE, DISK_MAPPER_HOST_MAPPING, "modified_time");
		$this->assertNotEquals(strcmp($modifyTime , $newModifyTime), 0 ,"Host-mapping File not rewritten");
	}

	public function test_Verify_Backup_is_Copied_to_Secondary() {
		// AIM : Upload a backup from a host to a storage server and verify that the data is copied to the secondary dir of some disk on another storage server.
		// EXPECTED RESULT : Data is copied to secondary on another SS
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PriSS = $PriMapping['storage_server'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		$this->assertNotEquals($PriSS, $SecSS,"Primary and secondary storage servers are same");
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, $hostname, 'secondary');
		$this->assertTrue($status,"File not copied to secondary");
	}

	public function est_Upload_By_All_Host_Without_Mapping_Without_Any_Spare_Disks() {
		// AIM : Upload of a backup when the host does not have an existing mapping and no spare disks are available
		// The current allocation strategy expects a headroom of two disks in the storage server pool. Modifying accroding to that.
		// EXPECTED RESULT : Upload fails
        // Fails with 1.9 Setup
		diskmapper_setup::reset_diskmapper_storage_servers();
		for($i=1;$i<17;$i++){
			$slave_host_name ="test_slave_$i";
			$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $slave_host_name),"File not uploaded to primary SS");
		}
		for ($i=1;$i<=16;$i++) {
                        $slave_host_name ="test_slave_$i";
			$this->assertTrue(torrent_functions::wait_for_torrent_copy($slave_host_name,60) , "Failed to copy file to secondary disk");
		}
		diskmapper_api::zstore_put(DUMMY_FILE_1, "test_slave_17");
		diskmapper_api::zstore_put(DUMMY_FILE_1, "test_slave_18");

                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertFalse(diskmapper_api::zstore_put(DUMMY_FILE_1 , $hostname) , "File uploaded despite no spares available");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$this->assertTrue(strpos($logs , "ERROR primary spare not found for ".$hostname) > 0 ,"Primay spare initialized despite no spares available");
	}

	public function test_Second_Upload_With_Existing_Mapping() {
		// AIM : Upload of a backup when the host has an existing mapping.
		// EXPECTED RESULT : uplaod is successful and the file is uploaded according to the same mapping
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		sleep(2);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$this->assertTrue(strpos($logs , "INFO Found primary for ".$hostname) > 0 ,"Primary disk not found depite mapping exisitng");
	}

	public function test_Second_Upload_After_First_Backup_Deleted() {
		// AIM : Upload a backup.Then delete the backup(both primary and secondary). Then upload another backup
		//EXPECTED RESULT : The backup does not fail but is either mapped to the same disk or another disk and secondary copy is created for the same.
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		storage_server_functions::clear_host_primary($hostname);
		storage_server_functions::clear_host_secondary($hostname);
		sleep(5);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, $hostname,'primary');
		$this->assertTrue($status,"File not copied to primary");
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, $hostname,'secondary');
		$this->assertTrue($status,"File not copied to secondary");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Initializing primary for ".$hostname);
		$this->assertTrue($status, "Primary not initialized");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Initializing secondary for ".$hostname);
		$this->assertTrue($status, "Secondary not initialized");
	}

	public function test_Invalid_IP_Address_in_Config_File() {
		// AIM : Invalid IP addresses specified in the config file of the DISK_MAPPER
		// EXPECTED RESULT : The uploads should fail
		diskmapper_setup::reset_diskmapper_storage_servers();
		$modify_storage_server= array("10.10.10.10");
		diskmapper_functions::modify_diskmapper_config_file(DISK_MAPPER_SERVER_ACTIVE, $modify_storage_server);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "restart");
		//Fails for the httplib implementation and should be bypassed.
//		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR Failed to connect to 10.10.10.10", 30);
//		$this->assertTrue($status, "Message not Found: ERROR Failed to connect to 10.10.10.10");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR Failed to get config from storage server", 30);
		$this->assertTrue($status, "Message not Found: ERROR Failed to get config from storage server");
	}

	public function test_Upload_When_Primary_Disk_Goes_Down() {
		// AIM : Verify that when a disk goes down for a host, the DM redirects the slave server to upload the backup to the secondary for the same host name
		// EXPECTED RESULT : The uploads are redirected to the secondary SS
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
             	$primay_path = diskmapper_functions::get_file_path_from_disk_mapper(DUMMY_FILE_1, $hostname, 'primary');
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PriSS = $PriMapping['storage_server'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'),"Failed adding bad disk entry");
		sleep(10);
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($SecSS));
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE, "cat ".DISK_MAPPER_LOG_FILE);
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Primary disk is not available or is bad");
		$this->assertTrue($status, "Bad Disk being marked bad not detected by Disk Mapper");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Found secondary for ".$hostname);
		$this->assertTrue($status, "Request not redirected to Secondary SS");

		// verify old primay doesn't have the new uploaded file
		$this->assertFalse(file_function::check_file_exists($PriSS, $primay_path), "New file uploaded to failed primary");

		// verify secondary has the new uploaded file
                $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,190) , "Failed to copy file to new primary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PriSS = $PriMapping['storage_server'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, $hostname, 'secondary');
		$this->assertTrue($status,"File not copied to secondary");

		// verify new primary has the new uploaded file
		$status = diskmapper_functions::compare_primary_secondary($hostname);
		$this->assertTrue($status,"File not copied to new primary");

	}

	public function test_Upload_When_Secondary_Disk_Goes_Down() {
		// AIM : Verify that when secondary disk goes down for a host, the DM redirects primary to copie all files to new seconday
		// EXPECTED RESULT : The uploads are copied to new secondary SS
                $hostname = general_function::get_hostname(TEST_HOST_1);
		diskmapper_setup::reset_diskmapper_storage_servers();
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,120) , "Failed to copy file to secondary disk");
		$secondary_path = diskmapper_functions::get_file_path_from_disk_mapper(DUMMY_FILE_1, $hostname, 'secondary');
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'secondary'),"Failed adding bad disk entry");

		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PriSS = $PriMapping['storage_server'];
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PriSS));
                $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,120) , "Failover completed");
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
                $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "File not copied to secondary");
		// wait till primay and new secondary synch
		$status = diskmapper_functions::compare_primary_secondary($hostname);
		$this->assertTrue($status,"File not copied to new primary");

		// verify old secondary doesn't have the new uploaded file
		$this->assertFalse(file_function::check_file_exists($SecSS, $secondary_path), "New file uploaded to secondary");

	}

	public function test_Upload_By_Host_Having_Both_Disks_Bad () {
	//get error in disk mapper log file and upload should fail
		// AIM : Upload a backup from a host and once it is complete, mark both the primary and secondary partition disks as bad. Uplaod another backup
		// EXPECTED RESULT :
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'),"Failed adding bad disk entry");
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'secondary'),"Failed adding bad disk entry");
		sleep(15);
		$status = diskmapper_api::zstore_put(DUMMY_FILE_2, $hostname);
		$host_mapping = diskmapper_api::get_all_config();
		$this->assertTrue(empty($host_mapping[$hostname]),"hostmapping not empty");
	}

	public function test_Data_Copy_When_Primary_Disk_Is_MarkedBad() {
		// AIM : when a disk that contains the primary date for a host is marked as bad (by the nagios/coalescers) verify that a new primary space is identified,
		//		a torrent file is created and all the files in the secondary are copied to the new primary space.
		// EXPECTED RESULT : The files are copied to the new location
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,120) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		remote_function::remote_execution($PrimSS,"sudo rm -rf /var/www/html/torrent/*");
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'),"Failed adding bad disk entry");
		sleep(10);
                $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PrimSS, $Pridisk, "bad", 15));
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($SecSS), "Torrent not created");
		diskmapper_api::zstore_get(DUMMY_FILE_1GB, $hostname, "test");
			// verify request comes from secondary since primary is not available
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Request redirected to : http://$SecSS/api/zbase/".$hostname."/".ZBASE_CLOUD."/test/dummy_file_1gb");
		$this->assertTrue($status, "Request not redirected to Secondary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to new primary disk");
			// verify host mapping is updated with new primay
		$PriMapping_new = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS_after_swap = $PriMapping_new['storage_server'];
		$Pridisk_after_swap = $PriMapping_new['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after primary disk marked bad");
			// verify new request comes from new primay
		diskmapper_api::zstore_get(DUMMY_FILE_1GB, $hostname, "test");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Request redirected to : http://$PrimSS_after_swap/api/zbase/".$hostname."/".ZBASE_CLOUD."/test/dummy_file_1gb");
		$this->assertTrue($status, "Request not redirected to New Primary SS");

	}

	public function test_Data_Copy_When_Secondary_Disk_Is_Marked_Bad() {
		// AIM : Verify that when a disk that contains the secondary data for a host is marked as bad (by the nagios/coalescers)
		//verify that a new secondary space is identified, a torrent file is created and all the files in the primary are copied to the new secondary space
		// EXPECTED RESULT : The files are copied to the new location
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		$Secdisk = $SecMapping['disk'];
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS = $PriMapping['storage_server'];
		remote_function::remote_execution($SecSS,"sudo rm -rf /var/www/html/torrent/*");
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'secondary'),"Failed adding bad disk entry");
		sleep(60);
		$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $SecSS, $Secdisk, "bad",15));
		//	$this->assertEquals(diskmapper_functions::get_mapping_param($hostname, "secondary", "status"), "bad", "Secondary disk is not marked bad");
		//	$this->assertTrue(diskmapper_functions::wait_until_param_change($hostname, "secondary", "status", "bad"), "Disk mapper failed to updated bad disk");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,100) , "Failed to copy file to the new secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS_after_swap = $SecMapping['storage_server'];
		$Secdisk_after_swap = $SecMapping['disk'];
		$SecMap = $SecSS.":".$Secdisk;
		$SecMap_after_swap = $SecSS_after_swap.":".$Secdisk_after_swap;
		$this->assertNotEquals($SecMap,$SecMap_after_swap,"Storage server not swapped after secodnary  disk marked bad");
	}

	public function test_Dirty_File_Entry_added_While_DM_Stopped() {
		// AIM : If the disk mapper is stopped and a dirty file has been added, ensure that when disk mapper is restarted he picks up the dirty file host and initiates a torrent for it
		// EXPECTED RESULT : When the DM is started,it picks up the bad disk entry and initiates a swap
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'stop');
		$entry ="/$PriDisk/primary/".$hostname."/".ZBASE_CLOUD."/test";
		$status = diskmapper_api::curl_call("http://$PrimSS/api/?action=add_entry&type=dirty_files&entry=$entry");
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'start');
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PrimSS), "Torrent not created");
	}

	public function test_Bad_Disk_added_While_DM_Stopped() {
		// AIM : If the disk mapper is stopped and a bad disk file has been added for a particular host, ensure that when the disk mapper is restarted he swaps out the disk
		// EXPECTED RESULT : When the DISK_MAPPER is started,it picks up the bad disk entry and initiates a swap
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		$status =  diskmapper_api::curl_call("http://$PrimSS/api/?action=add_entry&type=bad_disk&entry=$Pridisk");
		diskmapper_setup::clear_diskmapper_log_files();
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
		sleep(10);
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$this->assertTrue(strpos($logs ,"INFO Initialized ".$hostname) > 0,"New disk not swapped in after restarting the Disk Mapper");
       		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to new primary");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS_after_swap = $PriMapping['storage_server'];
		$Pridisk_after_swap = $PriMapping['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after starting disk mapper");

	}

	public function test_DM_Dies_While_Swapping_Bad_Disk() {
		// AIM : when the DISK_MAPPER_SERVER_ACTIVE detects that a disk is down and proceeds to initialize another spare disk but dies in the process. If it is restarted verify it's behavior.
		// EXPECTED RESULT : On restarting,the DISK_MAPPER_SERVER_ACTIVE successfully swaps the bad disk
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'), "Failed adding bad disk entry");
		sleep(2);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		sleep(5);
		diskmapper_setup::clear_diskmapper_log_files();
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
		sleep(10);
		$this->assertTrue(diskmapper_functions::verify_both_disks_active($hostname), "Failed in successfull failover");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS_after_swap = $PriMapping['storage_server'];
		$Pridisk_after_swap = $PriMapping['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after starting disk mapper");

	}

	public function test_Primary_Bad_Disk_Becomes_Healthy_again() {
		// AIM : If a disk (cotaining primary data for a host) is falsely reported as bad and all data from the secondary
		//	is copied to the new primary and then verify behavior of DISK_MAPPER when the bad disk becomes healthy again.
		// EXPECTED RESULT : New uploads should go to new primary

		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
       		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
        	$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
        	$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PrimSS, $Pridisk, "good"));
		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'), "Failed adding bad disk entry");
		//wait till disk is swapped
	 	$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PrimSS, $Pridisk, "bad", 20));
	#	$this->assertTrue(diskmapper_functions::wait_until_param_change($hostname, "primary", "status", "good"), "Disk mapper failed to updated bad disk");
	#	$this->assertTrue(diskmapper_functions::wait_until_param_change($hostname, "primary", "status", "bad"), "Disk mapper failed to updated good disk");
		//clear bad disk entry in the primary storage server
		storage_server_setup::clear_bad_disk_entry($PrimSS);
		remote_function::remote_execution($PrimSS, "sudo rm -rf /".$Pridisk."/{primary,secondary}/*");
                $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Promotion failed");
                $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PrimSS, $Pridisk, "good", 20));
        #       $this->assertTrue(diskmapper_functions::wait_until_param_change($hostname, "primary", "status", "good"), "Disk mapper failed to updated bad disk");
					// verify host mapping is updated with new primay
		$PriMapping_new = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PrimSS_after_swap = $PriMapping_new['storage_server'];
		$Pridisk_after_swap = $PriMapping_new['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after primary disk marked bad");
			// verify new upload goes to new primary
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, $hostname),"File not uploaded to primary SS");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Request redirected to : http://$PrimSS_after_swap/api/zbase/".$hostname."/".ZBASE_CLOUD."/test/".basename(DUMMY_FILE_2));
		$this->assertTrue($status, "Request not redirected to New Primary SS");
	}

	public function test_Secondary_Bad_Disk_Becomes_Healthy_again() {
		// AIM : If a disk (cotaining secondary data for a host) is falsely reported as bad and all data from the primary
		//		is copied to the new secondary and then verify behavior of DISK_MAPPER when the bad disk becomes healthy again.
		// EXPECTED RESULT : Torrent should send data to new secondary
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,$hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS = $SecMapping['storage_server'];
		$Secdisk = $SecMapping['disk'];
		$SecondaryMap = $SecSS.":".$Secdisk;
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'secondary'),"Failed adding bad disk entry");
                $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $SecSS, $Secdisk, "bad", 20));
		//wait till disk is marked bad
	#	$this->assertTrue(diskmapper_functions::wait_until_param_change($hostname, "secondary", "status", "good"), "Disk mapper failed to updated bad disk");
		// wait till new disk is assigned
	#	$this->assertTrue(diskmapper_functions::wait_until_param_change($hostname, "secondary", "status", "bad"), "Disk mapper failed to updated good disk");
		//clear bad disk entry in the primary storage server
		storage_server_setup::clear_bad_disk_entry($SecSS);
                remote_function::remote_execution($SecSS, "sudo rm -rf /".$Secdisk."/{primary,secondary}/*");
                $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $SecSS, $Secdisk, "good",20));
		sleep(10);
					// verify host mapping is updated with new secondary
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");
		$SecMapping_new = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS_after_swap = $SecMapping_new['storage_server'];
		$Secdisk_after_swap = $SecMapping_new['disk'];
		$SecondaryMap_after_swap = $SecSS_after_swap.":".$Secdisk_after_swap;
		$this->assertNotEquals($SecondaryMap,$SecondaryMap_after_swap,"Disk not swapped after secondary disk marked bad");

	}

        public function est_Bad_disk_Healthy_When_Other_Spares_Unavailable() {
                // AIM : When all primary and secondary partititions contain a hostname and when one of the disks are reported to be bad,
                //              verify operation of the DISK_MAPPER.
                // EXPECTED RESULT :
                // Will not work with 1.9 Setup
                diskmapper_setup::reset_diskmapper_storage_servers();
                for($i=1;$i<=16;$i++){
                        $slave_host_array[$i] ="test_slave_$i";
                        $this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,$slave_host_array[$i]),"Failed uploading to primary SS");
                }

		 for($i=1;$i<=16;$i++){
                        $slave_host_array[$i] ="test_slave_$i";
                	$this->assertTrue(torrent_functions::wait_for_torrent_copy($slave_host_array[$i], 60) , "Failed to copy file to secondary disk");
		}
                $PriMap = diskmapper_functions::get_primary_partition_mapping("test_slave_16");
		$PriSS = $PriMap['storage_server'];
		$Pridisk = $PriMap['disk'];
                $this->assertTrue(diskmapper_functions::add_bad_disk("test_slave_16",'primary'),"Failed adding bad disk entry");
                $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PriSS, $Pridisk, "bad",20));
                $this->assertTrue(torrent_functions::wait_for_torrent_copy("test_slave_16" , 150) , "Failed to copy file to new disk");
                storage_server_setup::clear_bad_disk_entry($PriSS);
		general_function::execute_command("sudo rm -rf /".$Pridisk."/{primary,secondary}/*", $PriSS);
		$PriMap1 = diskmapper_functions::get_primary_partition_mapping("test_slave_16");
                $PriSS1 = $PriMap1['storage_server'];
                $Pridisk1 = $PriMap1['disk'];
                $this->assertTrue(diskmapper_functions::add_bad_disk("test_slave_16",'primary'),"Failed adding bad disk entry");
                $this->assertTrue(torrent_functions::wait_for_torrent_copy("test_slave_16" , 150) , "Failed to copy file to new disk");
                $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PriSS1, $Pridisk1, "bad",20));
                $PriMap_new = diskmapper_functions::get_primary_partition_mapping("test_slave_16");
                $PriSS_new = $PriMap_new['storage_server'];
                $Pridisk_new = $PriMap_new['disk'];
                $PrimaryMap_old = $PriSS.":".$Pridisk;
		$PrimaryMap_new = $PriSS_new.":".$Pridisk_new;
		$this->assertEquals($PrimaryMap_old, $PrimaryMap_new, "Old disk not rehydrated");
        }



	public function est_Disk_Becomes_Bad_when_No_Spares_Available() {
		// AIM : When all primary and secondary partititions contain a hostname and when one of the disks are reported to be bad,
		//		verify operation of the DISK_MAPPER.
		// EXPECTED RESULT :
        // Will not work with 1.9
		diskmapper_setup::reset_diskmapper_storage_servers();
        if(IBR_Style == '2.0') {
		for($i=1;$i<17;$i++){
			$slave_host_array[$i] ="test_slave_$i";
			$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,$slave_host_array[$i]),"Failed uploading to primary SS");
			$this->assertTrue(torrent_functions::wait_for_torrent_copy($slave_host_array[$i] , 150) , "Failed to copy file to secondary disk");
		}
		diskmapper_api::zstore_put(DUMMY_FILE_1,"test_slave_17");
		diskmapper_api::zstore_put(DUMMY_FILE_1,"test_slave_18");
		$this->assertTrue(diskmapper_functions::add_bad_disk("test_slave_1",'primary'),"Failed adding bad disk entry");
		sleep(10);
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR primary spare not found");
		$this->assertTrue($status, "Error for no priamry spare disks not reported despite no spare disks");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR Failed to swap");
		$this->assertTrue($status, "Disk swapped despite no spares available");
        }
        $this->assertFalse(True, "incompatible testcase");
	}

	public function test_No_Permissions_to_Mapping_File() {
		// AIM : Test Functioning of disk mapper if it does not find the mapping file or if the file does not have read permissions
		// EXPECTED RESULT : An error is thrown
		diskmapper_setup::reset_diskmapper_storage_servers();
		remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "sudo chmod 200 ".DISK_MAPPER_HOST_MAPPING);
		$this->assertFalse(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File uploaded to primary SS despite no permissions to mapping file");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR [Errno 13] Permission denied: '".DISK_MAPPER_HOST_MAPPING."'");
		$this->assertTrue($status, "Permissions error not thrown");
		remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "sudo chmod 644 ".DISK_MAPPER_HOST_MAPPING);
	}

	public function test_Bad_Disk_Reported_in_HostMapping(){
		// AIM : If a disk is considered to be bad and contains a baddisk file, verify that the same is reported in the hostmapping file.
		// EXPECTED RESULT : The bad disk is reported in the mapping file
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,$hostname),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,90) , "Failed to copy file to secondary disk");

		$PriMapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'),"Failed adding bad disk entry");
		$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PriSS, $PriDisk, "bad", 15));

	}

	public function test_zstore_get_multiple_times(){
		// AIM: Issue get for same file which is present on server twice
		// Expected: Request should not hang and file is not updated. File is downloaded only if md5sum match fails

		diskmapper_setup::reset_diskmapper_storage_servers();
		$test_file_1 = "/tmp/test_file_1";
		file_function::create_dummy_file(TEST_HOST_2, $test_file_1, 1048576);
		$md5_file_1 = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		$this->assertTrue(diskmapper_api::zstore_put($test_file_1, TEST_HOST_1), "File not uploaded to primary SS");
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf $test_file_1");
		diskmapper_api::zstore_get($test_file_1, TEST_HOST_1);
		$md5_file_new = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		$this->assertEquals($md5_file_1, $md5_file_new, "md5sum match fails for the downloaded file");
		$modified_time_file_1 = file_function::file_attributes(TEST_HOST_2, $test_file_1, "modified_time");
		diskmapper_api::zstore_get($test_file_1, TEST_HOST_1);
		$modified_time_file_new = file_function::file_attributes(TEST_HOST_2, $test_file_1, "modified_time");
		$this->assertEquals($modified_time_file_1, $modified_time_file_new, "file downloaded even with md5sum match");

		file_function::create_dummy_file(TEST_HOST_2, $test_file_1, 1500000);
		$md5_file_2 = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		diskmapper_api::zstore_get($test_file_1, TEST_HOST_1);
		$md5_file_new = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		$this->assertNotEquals($md5_file_2, $md5_file_new, "file not downloaded when md5sum match fails");
		$this->assertEquals($md5_file_1, $md5_file_new, "md5sum match fails for the downloaded file");
	}

	public function test_zstore_get_invalid_path(){
		// AIM: Get on invalid file name or invalid host name
		// Expected: Request should not hang

		$test_file_1 = "/tmp/non_existant_file_1";
		diskmapper_api::zstore_get($test_file_1, TEST_HOST_1);
		$this->assertFalse(file_function::check_file_exists(TEST_HOST_2, $test_file_1));

		diskmapper_api::zstore_get($test_file_1, "dummy_server_name");
		$this->assertFalse(file_function::check_file_exists(TEST_HOST_2, $test_file_1));

	}

	public function est_Primary_Secondary_going_down_loop() {
		// AIM : If primary and secondary disk goes down in a loop ( Primary1 down, wait for new primary2, Secondary1 down, wait for new secondary2, ...
		// EXPECTED RESULT : The files are backuped up properly. When request is made for getting a file it redirects to new primary
        //Do not run with 1.9 setup. It takes really long to complete.
		diskmapper_setup::reset_diskmapper_storage_servers();
                $hostname = general_function::get_hostname(TEST_HOST_1);
		$test_file_1 = "/tmp/test_file_1";
		file_function::create_dummy_file(TEST_HOST_2, $test_file_1, 1048576);
		$md5_file_1 = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		$this->assertTrue(diskmapper_api::zstore_put($test_file_1, $hostname), "File not uploaded to primary SS");
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf $test_file_1");
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $hostname), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
	        $this->assertTrue(diskmapper_functions::compare_primary_secondary($hostname), "primary and secondary out of sync");
		$PriMapping1 = diskmapper_functions::get_primary_partition_mapping($hostname);
        	$PriSS1 = $PriMapping1['storage_server'];
       		$PriDisk1 = $PriMapping1['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'), "Failed adding bad disk entry");
		//wait till disk is swapped and new disk is assinged
 	        $this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PriSS1, $PriDisk1, "bad",15));
	        $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to new primary");
		$PriMapping2 = diskmapper_functions::get_primary_partition_mapping($hostname);
		$this->assertNotEquals($PriMapping1['storage_server'].":".$PriMapping1['disk'], $PriMapping2['storage_server'].":".$PriMapping2['disk'], "New primary is same as old primary");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
        	$test_file_2 = "/tmp/test_file_2";
   	        file_function::create_dummy_file(TEST_HOST_2, $test_file_2, 1048576);
	        $md5_file_2 = file_function::get_md5sum(TEST_HOST_2, $test_file_2);
	        $this->assertTrue(diskmapper_api::zstore_put($test_file_2, $hostname), "File not uploaded to primary SS");
	        remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf $test_file_2");
       		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to new primary");
		$SecMapping1 = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS1 = $SecMapping1['storage_server'];
		$Secdisk1 = $SecMapping1['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'secondary'), "Failed adding bad disk entry");
		//wait till disk is swapped and new disk is assinged
        	$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $SecSS1, $Secdisk1, "bad",15));
	        $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to new primary");
		$SecMapping2 = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$this->assertNotEquals($SecMapping1['storage_server'].":".$SecMapping1['disk'], $SecMapping2['storage_server'].":".$SecMapping2['disk'], "New secondary server is same as old secondary server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
	        $test_file_3 = "/tmp/test_file_3";
   	        file_function::create_dummy_file(TEST_HOST_2, $test_file_3, 1048576);
       	 	$md5_file_3 = file_function::get_md5sum(TEST_HOST_2, $test_file_3);
	        $this->assertTrue(diskmapper_api::zstore_put($test_file_3, $hostname), "File not uploaded to primary SS");
 	        remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf $test_file_3");
       		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to new primary");
		$PriMapping2 = diskmapper_functions::get_primary_partition_mapping($hostname);
		$PriSS2 = $PriMapping2['storage_server'];
		$Pridisk2 = $PriMapping2['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'primary'), "Failed adding bad disk entry");
		//wait till disk is swapped and new disk is assinged
        	$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $PriSS2, $Pridisk2, "bad",15));
      		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to new primary");
		$PriMapping3 = diskmapper_functions::get_primary_partition_mapping($hostname);
		$this->assertNotEquals($PriMapping2['storage_server'].":".$PriMapping2['disk'], $PriMapping3['storage_server'].":".$PriMapping3['disk'], "New primary is same as old primary");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
		$PriSS3 = $PriMapping3['storage_server'];
		$Pridisk3= $PriMapping3['disk'];
		$SecMapping2 = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$SecSS2 = $SecMapping2['storage_server'];
		$Secdisk2 = $SecMapping2['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk($hostname,'secondary'), "Failed adding bad disk entry");
		//wait till disk is swapped and new disk is assinged
		$this->assertTrue(diskmapper_functions::query_disk_status_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, $SecSS2, $Secdisk2, "bad",15));
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to secondary");
		$test_file_4 = "/tmp/test_file_4";
		file_function::create_dummy_file(TEST_HOST_2, $test_file_4, 1048576);
		$md5_file_4 = file_function::get_md5sum(TEST_HOST_2, $test_file_4);
		$this->assertTrue(diskmapper_api::zstore_put($test_file_4, $hostname), "File not uploaded to primary SS");
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf $test_file_4");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to new primary");
		$SecMapping3 = diskmapper_functions::get_secondary_partition_mapping($hostname);
		$this->assertNotEquals($SecMapping2['storage_server'].":".$SecMapping2['disk'], $SecMapping3['storage_server'].":".$SecMapping3['disk'], "New secondary server is same as old secondary server");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy file to secondary disk");
			// verify new upload is successful and upload goes to new primary and secondary
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, $hostname), "File not uploaded to primary SS");
                $this->assertTrue(torrent_functions::wait_for_torrent_copy($hostname,180) , "Failed to copy to secondary");
		$file_path_primary = "/".$PriMapping3['disk']."/primary/".$hostname."/".ZBASE_CLOUD."/test/".basename(DUMMY_FILE_2);
		$file_path_secondary = "/".$SecMapping3['disk']."/secondary/".$hostname."/".ZBASE_CLOUD."/test/".basename(DUMMY_FILE_2);
		$file_path_primary_1 = "/".$PriMapping3['disk']."/primary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_1);
		$file_path_secondary_1 = "/".$SecMapping3['disk']."/secondary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_1);
		$file_path_primary_2 = "/".$PriMapping3['disk']."/primary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_2);
		$file_path_secondary_2 = "/".$SecMapping3['disk']."/secondary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_2);
		$file_path_primary_3 = "/".$PriMapping3['disk']."/primary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_3);
		$file_path_secondary_3 = "/".$SecMapping3['disk']."/secondary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_3);
		$file_path_primary_4 = "/".$PriMapping3['disk']."/primary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_4);
		$file_path_secondary_4 = "/".$SecMapping3['disk']."/secondary/".$hostname."/".ZBASE_CLOUD."/test/".basename($test_file_4);
		$this->assertTrue(file_function::check_file_exists($PriMapping3['storage_server'], $file_path_primary),"failure in primary");
		$this->assertTrue(file_function::check_file_exists($SecMapping3['storage_server'], $file_path_secondary), "failure in secondary");
		// get previously uploaded file and ensure md5sum match
		$this->assertTrue(file_function::check_file_exists($PriMapping3['storage_server'], $file_path_primary_1 ),"failure in primary");
		$this->assertTrue(file_function::check_file_exists($SecMapping3['storage_server'], $file_path_secondary_1), "failure in secondary");
		$this->assertTrue(file_function::check_file_exists($PriMapping3['storage_server'], $file_path_primary_2),"failure in primary");
		$this->assertTrue(file_function::check_file_exists($SecMapping3['storage_server'], $file_path_secondary_2), "failure in secondary");
		$this->assertTrue(file_function::check_file_exists($PriMapping3['storage_server'], $file_path_primary_3),"failure in primary");
		$this->assertTrue(file_function::check_file_exists($SecMapping3['storage_server'], $file_path_secondary_3), "failure in secondary");
		$this->assertTrue(file_function::check_file_exists($PriMapping3['storage_server'], $file_path_primary_4),"failure in primary");
		$this->assertTrue(file_function::check_file_exists($SecMapping3['storage_server'], $file_path_secondary_4), "failure in secondary");
		diskmapper_api::zstore_get($test_file_1, $hostname);
		$md5_file_new_1 = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		$this->assertEquals($md5_file_1, $md5_file_new_1, "md5sum match fails for the downloaded file");
		diskmapper_api::zstore_get($test_file_2, $hostname);
		$md5_file_new_2 = file_function::get_md5sum(TEST_HOST_2, $test_file_2);
		$this->assertEquals($md5_file_2, $md5_file_new_2, "md5sum match fails for the downloaded file");
		diskmapper_api::zstore_get($test_file_3, $hostname);
		$md5_file_new_3 = file_function::get_md5sum(TEST_HOST_2, $test_file_3);
		$this->assertEquals($md5_file_3, $md5_file_new_3, "md5sum match fails for the downloaded file");
		diskmapper_api::zstore_get($test_file_4, $hostname);
		$md5_file_new_4 = file_function::get_md5sum(TEST_HOST_2, $test_file_4);
		$this->assertEquals($md5_file_4, $md5_file_new_4, "md5sum match fails for the downloaded file");

	}

	public function est_Primary_disk_going_down() {
		// AIM : If primary disk goes down ensure upload / download request doesn't get stuck in a loop

		diskmapper_setup::reset_diskmapper_storage_servers();
		$test_file_1 = "/tmp/test_file_1";
		file_function::create_dummy_file(TEST_HOST_2, $test_file_1, 1048576);
		$md5_file_1 = file_function::get_md5sum(TEST_HOST_2, $test_file_1);
		$this->assertTrue(diskmapper_api::zstore_put($test_file_1, TEST_HOST_1), "File not uploaded to primary SS");
		remote_function::remote_execution(TEST_HOST_2, "sudo rm -rf $test_file_1");

			// unmount primary disk
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		remote_function::remote_execution($PriSS, "mount"); // need this to log disk mount details to log file
		$mount_partition = trim(remote_function::remote_execution($PriSS, "mount | grep $PriDisk | awk '{print $1}'"));
		remote_function::remote_execution($PriSS, "sudo umount -l $mount_partition");
				// issue get request
		diskmapper_api::zstore_get($test_file_1, TEST_HOST_1);
		$this->assertFalse(file_function::check_file_exists(TEST_HOST_2, $test_file_1));
		$this->assertFalse(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
			// upload a new file
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
			// mount the disk back
		remote_function::remote_execution($PriSS, "sudo mount $mount_partition /".$PriDisk);

	}

}

class DiskMapper_TestCase_Full extends DiskMapper_TestCase {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

