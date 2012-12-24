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
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$parsed_hostmap = diskmapper_api::get_all_config();
		$PrimSS = $parsed_hostmap[TEST_HOST_1]['primary']['storage_server'];
		$SecSS = $parsed_hostmap[TEST_HOST_1]['secondary']['storage_server'];
		$command_to_be_executed = "ls /var/www/html/".GAME_ID;
		$status = trim(remote_function::remote_execution($PrimSS,$command_to_be_executed));
		// We verify that the name of the symlink is as expected
		$this->assertEquals(strcmp($status ,TEST_HOST_1) , 0 ,"Symlink name different than expected");
		$status = trim(remote_function::remote_execution($SecSS,$command_to_be_executed));
		$this->assertEquals(strcmp($status ,TEST_HOST_1) , 0 ,"Symlink name different than expected");
	}
	
	public function test_Verify_Mapping_File_Created_for_First_Time() {
		// AIM : Verify that the mapping file on the DM is created for the first time when the DM polls the storage servers for status
		// EXPECTED RESULT : THe file is created for the first time afte polling
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'stop');
		sleep(3);
		remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE, "sudo rm -rf ".DISK_MAPPER_HOST_MAPPING);
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
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$this->assertNotEquals($PriSS, $SecSS,"Primary and secondary storage servers are same");
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, TEST_HOST_1, 'secondary');
		$this->assertTrue($status,"File not copied to secondary");
	}

	public function test_Upload_By_All_Host_Without_Mapping_Without_Any_Spare_Disks() {
		// AIM : Upload of a backup when the host does not have an existing mapping and no spare disks are available
		// EXPECTED RESULT : Upload fails
		diskmapper_setup::reset_diskmapper_storage_servers();
		for($i=1;$i<19;$i++){
			$slave_host_name ="test_slave_$i";
			$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, $slave_host_name),"File not uploaded to primary SS");
			$this->assertTrue(torrent_functions::wait_for_torrent_copy($slave_host_name,60) , "Failed to copy file to secondary disk");
		}
		$this->assertFalse(diskmapper_api::zstore_put(DUMMY_FILE_1 , TEST_HOST_1) , "File uploaded despite no spares available");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$this->assertTrue(strpos($logs , "ERROR primary spare not found for ".TEST_HOST_1) > 0 ,"Primay spare initialized despite no spares available");
	}

	public function test_Second_Upload_With_Existing_Mapping() { 
		// AIM : Upload of a backup when the host has an existing mapping.
		// EXPECTED RESULT : uplaod is successful and the file is uploaded according to the same mapping
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		sleep(2);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");		
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$this->assertTrue(strpos($logs , "INFO Found primary for ".TEST_HOST_1) > 0 ,"Primary disk not found depite mapping exisitng");
	}

	public function test_Second_Upload_After_First_Backup_Deleted() {	
		// AIM : Upload a backup.Then delete the backup(both primary and secondary). Then upload another backup
		//EXPECTED RESULT : The backup does not fail but is either mapped to the same disk or another disk and secondary copy is created for the same.
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		storage_server_functions::clear_host_primary(TEST_HOST_1);
		storage_server_functions::clear_host_secondary(TEST_HOST_1);
		sleep(5);
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, TEST_HOST_1,'primary');
		$this->assertTrue($status,"File not copied to primary");
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, TEST_HOST_1,'secondary');
		$this->assertTrue($status,"File not copied to secondary");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Initializing primary for ".TEST_HOST_1);
		$this->assertTrue($status, "Primary not initialized");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Initializing secondary for ".TEST_HOST_1);
		$this->assertTrue($status, "Secondary not initialized");
	}

	public function test_Invalid_IP_Address_in_Config_File() {	
		// AIM : Invalid IP addresses specified in the config file of the DISK_MAPPER
		// EXPECTED RESULT : The uploads should fail
		diskmapper_setup::reset_diskmapper_storage_servers();
		$modify_storage_server= array("10.10.10.10");
		diskmapper_functions::modify_diskmapper_config_file(DISK_MAPPER_SERVER_ACTIVE, $modify_storage_server);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "restart");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR Failed to connect to 10.10.10.10", 30);
		$this->assertTrue($status, "Message not Found: ERROR Failed to connect to 10.10.10.10");		
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR Failed to get config from storage server", 30);
		$this->assertTrue($status, "Message not Found: ERROR Failed to get config from storage server");
	}	

	public function test_Upload_When_Primary_Disk_Goes_Down() { 
		// AIM : Verify that when a disk goes down for a host, the DM redirects the slave server to upload the backup to the secondary for the same host name
		// EXPECTED RESULT : The uploads are redirected to the secondary SS	
		diskmapper_setup::reset_diskmapper_storage_servers();
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$primay_path = diskmapper_functions::get_file_path_from_disk_mapper(DUMMY_FILE_1, TEST_HOST_1, 'primary');
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'primary'),"Failed adding bad disk entry");

		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($SecSS));
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE, "cat ".DISK_MAPPER_LOG_FILE);
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Primary disk is not available or is bad");
		$this->assertTrue($status, "Bad Disk being marked bad not detected by Disk Mapper");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Found secondary for ".TEST_HOST_1);
		$this->assertTrue($status, "Request not redirected to Secondary SS");
		
		// verify old primay doesn't have the new uploaded file
		$this->assertFalse(file_function::check_file_exists($PriSS, $primay_path), "New file uploaded to failed primary");		

		// verify secondary has the new uploaded file
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1, TEST_HOST_1, 'secondary');
		$this->assertTrue($status,"File not copied to secondary");		
		
		// verify new primary has the new uploaded file
		$status = diskmapper_functions::compare_primary_secondary(TEST_HOST_1);
		$this->assertTrue($status,"File not copied to new primary");		
		
	} 	

	public function test_Upload_When_Secondary_Disk_Goes_Down() { 
		// AIM : Verify that when secondary disk goes down for a host, the DM redirects primary to copie all files to new seconday 
		// EXPECTED RESULT : The uploads are copied to new secondary SS	
		
		diskmapper_setup::reset_diskmapper_storage_servers();
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$secondary_path = diskmapper_functions::get_file_path_from_disk_mapper(DUMMY_FILE_1, TEST_HOST_1, 'secondary');
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'secondary'),"Failed adding bad disk entry");

		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PriSS));
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");

		// wait till primay and new secondary synch
		$status = diskmapper_functions::compare_primary_secondary(TEST_HOST_1);
		$this->assertTrue($status,"File not copied to new primary");	

		// verify old secondary doesn't have the new uploaded file
		$this->assertFalse(file_function::check_file_exists($SecSS, $secondary_path), "New file uploaded to secondary");				
		
	} 	

	public function est_Upload_By_Host_Having_Both_Disks_Bad () {	//check: SEG-10524 no indication in diskmapper log file when disk is marked bad, 2nd file goes fine
	//get error in disk mapper log file and upload should fail
		// AIM : Upload a backup from a host and once it is complete, mark both the primary and secondary partition disks as bad. Uplaod another backup
		// EXPECTED RESULT : 
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'primary'),"Failed adding bad disk entry");
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'secondary'),"Failed adding bad disk entry");
		sleep(15);
		$status = diskmapper_api::zstore_put(DUMMY_FILE_2, TEST_HOST_1);
		$host_mapping = diskmapper_api::get_all_config();
	}
	
	public function test_Data_Copy_When_Primary_Disk_Is_MarkedBad() { 
		// AIM : when a disk that contains the primary date for a host is marked as bad (by the nagios/coalescers) verify that a new primary space is identified, 
		//		a torrent file is created and all the files in the secondary are copied to the new primary space.
		// EXPECTED RESULT : The files are copied to the new location
		diskmapper_setup::reset_diskmapper_storage_servers();
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		remote_function::remote_execution($PrimSS,"sudo rm -rf /var/www/html/torrent/*");
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'primary'),"Failed adding bad disk entry");
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($SecSS), "Torrent not created");
		diskmapper_api::zstore_get(DUMMY_FILE_1GB, TEST_HOST_1, "test");
			// verify request comes from secondary since primary is not available
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Request redirected to : http://$SecSS/api/membase/".TEST_HOST_1."/zc2/test/dummy_file_1gb");
		$this->assertTrue($status, "Request not redirected to Secondary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to new primary disk");
			// verify host mapping is updated with new primay
		$PriMapping_new = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS_after_swap = $PriMapping_new['storage_server'];
		$Pridisk_after_swap = $PriMapping_new['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after primary disk marked bad");	
			// verify new request comes from new primay
		diskmapper_api::zstore_get(DUMMY_FILE_1GB, TEST_HOST_1, "test");	
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Request redirected to : http://$PrimSS_after_swap/api/membase/".TEST_HOST_1."/zc2/test/dummy_file_1gb");
		$this->assertTrue($status, "Request not redirected to New Primary SS");
		
	}

	public function test_Data_Copy_When_Secondary_Disk_Is_MarkedBad() {
		// AIM : Verify that when a disk that contains the secondary data for a host is marked as bad (by the nagios/coalescers) 
		//verify that a new secondary space is identified, a torrent file is created and all the files in the primary are copied to the new secondary space
		// EXPECTED RESULT : The files are copied to the new location
		diskmapper_setup::reset_diskmapper_storage_servers();
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$Secdisk = $SecMapping['disk'];
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS = $PriMapping['storage_server'];
		remote_function::remote_execution($SecSS,"sudo rm -rf /var/www/html/torrent/*");
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'secondary'),"Failed adding bad disk entry");
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PrimSS), "Torrent not created");
		sleep(2);
		$this->assertEquals(diskmapper_functions::get_mapping_param(TEST_HOST_1, "secondary", "status"), "bad", "Secondary disk is not marked bad");
		$this->assertTrue(diskmapper_functions::wait_until_param_change(TEST_HOST_1, "secondary", "status", "bad"), "Disk mapper failed to updated bad disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
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
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'stop');
		$entry ="/$PriDisk/primary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test";	
		$status = diskmapper_api::curl_call("http://$PrimSS/api/?action=add_entry&type=dirty_files&entry=$entry");
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, 'start');
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PrimSS), "Torrent not created");
	}

	public function test_Bad_Disk_added_While_DM_Stopped() {
		// AIM : If the disk mapper is stopped and a bad disk file has been added for a particular host, ensure that when the disk mapper is restarted he swaps out the disk
		// EXPECTED RESULT : When the DISK_MAPPER is started,it picks up the bad disk entry and initiates a swap
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		$status =  diskmapper_api::curl_call("http://$PrimSS/api/?action=add_entry&type=bad_disk&entry=$Pridisk");
		diskmapper_setup::clear_diskmapper_log_files();
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
		sleep(10);
		$logs = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE , "cat ".DISK_MAPPER_LOG_FILE);
		$this->assertTrue(strpos($logs ,"INFO Initialized ".TEST_HOST_1) > 0,"New disk not swapped in after restarting the Disk Mapper");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS_after_swap = $PriMapping['storage_server'];
		$Pridisk_after_swap = $PriMapping['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after starting disk mapper");

	}

	public function test_DM_Dies_While_Swapping_BadDisk() { 
		// AIM : when the DISK_MAPPER_SERVER_ACTIVE detects that a disk is down and proceeds to initialize another spare disk but dies in the process. If it is restarted verify it's behavior.
		// EXPECTED RESULT : On restarting,the DISK_MAPPER_SERVER_ACTIVE successfully swaps the bad disk
		diskmapper_setup::reset_diskmapper_storage_servers();
		// Upload a large file so that disk swap takes some time
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");		
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'primary'), "Failed adding bad disk entry");
		sleep(2);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		sleep(5);
		diskmapper_setup::clear_diskmapper_log_files();
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
		sleep(10);
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
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
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS = $PriMapping['storage_server'];
		$Pridisk = $PriMapping['disk'];
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'primary'), "Failed adding bad disk entry");
		//wait till disk is swapped
		$this->assertTrue(diskmapper_functions::wait_until_param_change(TEST_HOST_1, "primary", "status", "good"), "Disk mapper failed to updated bad disk");	
		// wait till new disk is assigned
		$this->assertTrue(diskmapper_functions::wait_until_param_change(TEST_HOST_1, "primary", "status", "bad"), "Disk mapper failed to updated good disk");
		//clear bad disk entry in the primary storage server
		storage_server_setup::clear_bad_disk_entry($PrimSS);
					// verify host mapping is updated with new primay
		$PriMapping_new = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PrimSS_after_swap = $PriMapping_new['storage_server'];
		$Pridisk_after_swap = $PriMapping_new['disk'];
		$PrimaryMap = $PrimSS.":".$Pridisk;
		$PrimaryMap_after_swap = $PrimSS_after_swap.":".$Pridisk_after_swap;
		$this->assertNotEquals($PrimaryMap,$PrimaryMap_after_swap,"Disk not swapped after primary disk marked bad");
			// verify new upload goes to new primary
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, TEST_HOST_1),"File not uploaded to primary SS");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "INFO Request redirected to : http://$PrimSS_after_swap/api/membase/".TEST_HOST_1."/zc2/test/".basename(DUMMY_FILE_2));
		$this->assertTrue($status, "Request not redirected to New Primary SS");
	}

	public function test_Secondary_Bad_Disk_Becomes_Healthy_again() {	
		// AIM : If a disk (cotaining secondary data for a host) is falsely reported as bad and all data from the primary 
		//		is copied to the new secondary and then verify behavior of DISK_MAPPER when the bad disk becomes healthy again.
		// EXPECTED RESULT : Torrent should send data to new secondary
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$Secdisk = $SecMapping['disk'];		
		$SecondaryMap = $SecSS.":".$Secdisk;
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'secondary'),"Failed adding bad disk entry");
		
		//wait till disk is marked bad
		$this->assertTrue(diskmapper_functions::wait_until_param_change(TEST_HOST_1, "secondary", "status", "good"), "Disk mapper failed to updated bad disk");	
		// wait till new disk is assigned
		$this->assertTrue(diskmapper_functions::wait_until_param_change(TEST_HOST_1, "secondary", "status", "bad"), "Disk mapper failed to updated good disk");
		//clear bad disk entry in the primary storage server
		storage_server_setup::clear_bad_disk_entry($SecSS);
					// verify host mapping is updated with new secondary
		$SecMapping_new = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS_after_swap = $SecMapping_new['storage_server'];
		$Secdisk_after_swap = $SecMapping_new['disk'];
		$SecondaryMap_after_swap = $SecSS_after_swap.":".$Secdisk_after_swap;
		$this->assertNotEquals($SecondaryMap,$SecondaryMap_after_swap,"Disk not swapped after secondary disk marked bad");	
			
	}

	public function test_Disk_Becomes_Bad_when_No_Spares_Available() {
		// AIM : When all primary and secondary partititions contain a hostname and when one of the disks are reported to be bad, 
		//		verify operation of the DISK_MAPPER. 
		// EXPECTED RESULT : 
		diskmapper_setup::reset_diskmapper_storage_servers();

		for($i=1;$i<19;$i++){
			$slave_host_name ="test_slave_$i";
			$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,$slave_host_name),"Failed uploading to primary SS");			
			$this->assertTrue(torrent_functions::wait_for_torrent_copy($slave_host_name , 60) , "Failed to copy file to secondary disk");

		}
		$this->assertTrue(diskmapper_functions::add_bad_disk("test_slave_1",'primary'),"Failed adding bad disk entry");
		sleep(10);
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR primary spare not found");
		$this->assertTrue($status, "Error for no priamry spare disks not reported despite no spare disks");
		$status = diskmapper_functions::query_diskmapper_log_file(DISK_MAPPER_SERVER_ACTIVE, "ERROR Failed to swap");
		$this->assertTrue($status, "Disk swapped despite no spares available");
		
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

	public function test_BadDisk_Reported_in_HostMapping(){
		// AIM : If a disk is considered to be bad and contains a baddisk file, verify that the same is reported in the hostmapping file.
		// EXPECTED RESULT : The bad disk is reported in the mapping file
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1,TEST_HOST_1),"File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$this->assertTrue(diskmapper_functions::add_bad_disk(TEST_HOST_1,'primary'),"Failed adding bad disk entry");
		sleep(5);
		$this->assertEquals(diskmapper_functions::query_diskmapper_hostmapping_file(DISK_MAPPER_SERVER_ACTIVE, "bad"), 1, "disk mapper hostmapping file doesn't contain bad disk");
		
	}	
	
	// add primay to secon to primay to sec loop
	// add umount primary and test upload
	// add umount sec and test upload
	
}

class DiskMapper_TestCase_Full extends DiskMapper_TestCase {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

