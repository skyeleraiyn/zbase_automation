<?php

abstract class Torrents_TestCase extends ZStore_TestCase {

	public function test_Torrent_File_Creation() { 
		// AIM : Verify that when a dirty file is found for a particular host, the DM creates the torrent file in /var/www/html/torrent directory.
		// EXPECTED RESULT : Torrent file is created
		
		diskmapper_setup::reset_diskmapper_storage_servers();
		$file_path_array = torrent_functions::create_storage_directories(array(STORAGE_SERVER_1, STORAGE_SERVER_2), "data_1", TEST_HOST_1);
		$file_list = torrent_functions::create_test_file(STORAGE_SERVER_1, $file_path_array[0]."/test_file_1");
		torrent_functions::create_dirty_file(array(STORAGE_SERVER_1 => "data_1"), $file_list);
		torrent_functions::chown_storageserver(array(STORAGE_SERVER_1, STORAGE_SERVER_2));
	
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(array(STORAGE_SERVER_1 => $file_path_array[0], STORAGE_SERVER_2 => $file_path_array[1]), 60), "Failed to copy file to secondary");
		$this->assertTrue(file_function::check_file_exists(STORAGE_SERVER_2, $file_path_array[1]), "Failed to copy file to secondary");
		
	}

	public function test_File_addition_of_more_files() { 
		// AIM : Verify that if x files are already existing on the primary disk for a particular host, when the x+1th file is added, 
		//		only the x+1th file is copied across to the secondary disk
		// EXPECTED RESULT : Only the new file is copied acorss
		for($ifile = 0 ; $ifile<4 ; $ifile++){
			file_function::create_dummy_file(TEST_HOST_2, "/tmp/dummy_file_$ifile");
		}
		diskmapper_setup::reset_diskmapper_storage_servers();
		for($ifile = 0 ; $ifile<3 ; $ifile++){
			$this->assertTrue(diskmapper_api::zstore_put("/tmp/dummy_file_$ifile", TEST_HOST_1), "dummy_file_$ifile not uploaded to primary SS");
		}
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1, 30) , "Failed to copy file to secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$SecDisk = $SecMapping['disk'];
		// get the time modified of all the files
		$modifyTime = array();
		for($ifile = 0 ; $ifile<3 ; $ifile++){
			$modifyTime[$ifile] = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/dummy_file_$ifile", "modified_time");
		}		
		sleep(5);
			// upload one more file
		$this->assertTrue(diskmapper_api::zstore_put("/tmp/dummy_file_3", TEST_HOST_1), "File not uploaded to primary SS");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,20) , "Failed to copy file to secondary disk");
			// verify new file is copied
		$file_path_in_seconday = "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/dummy_file_3";	
		$this->assertTrue(file_function::check_file_exists($SecSS, $file_path_in_seconday), "Failed to copy file to secondary");
			// verify existings files are not copied
		$new_modifyTime = array();
		for($ifile = 0 ; $ifile<3 ; $ifile++){
			$new_modifyTime[$ifile] = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/dummy_file_$ifile", "modified_time");
		}		
		$diff_array = array_diff($new_modifyTime, $modifyTime);
		$this->assertEquals(count($diff_array), 0, "Old files modified in Secondary");		
	}

	public function test_Files_in_Sync_Dirty_File_Created() { 
		// AIM : if files on both the primary and secondary parameters are in sync and a dirty file is present for that primary directory
		// EXPECTED RESULT : No transfer of data ocuurs
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		//wait till file is copied to sec
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$SecDisk = $SecMapping['disk'];
		$modifyTime = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1), "modified_time");
		$this->assertTrue(diskmapper_functions::add_dirty_entry(TEST_HOST_1 , 'primary' , "/$PriDisk/primary/".TEST_HOST_1."/test/".basename(DUMMY_FILE_1)));
		$this->assertFalse(torrent_functions::verify_torrent_file_creation($PriSS), "Torrent file created");	
		$newModifyTime = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1), "modified_time");
		$this->assertEquals($modifyTime , $newModifyTime ,"FIle copied across to secondary");

		// add invalid file entry in dirty file and ensure files are not transferred
		$this->assertTrue(diskmapper_functions::add_dirty_entry(TEST_HOST_1 , 'primary' , "/$PriDisk/primary/".TEST_HOST_1."/test/testfile"));
		$this->assertFalse(torrent_functions::verify_torrent_file_creation($PriSS), "Torrent file created");	
		$newModifyTime = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1), "modified_time");
		$this->assertEquals($modifyTime , $newModifyTime ,"FIle copied across to secondary");

	}

	public function test_File_addition_across_different_host() {
		// AIM : Verify that when one file is modified on the primary storage server for that particular host, a torrent file is created and 
		//		the same file is copied across to the secondary storage server for that same host.
		// EXPECTED RESULT : The file is copied to secondary
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		//wait till file is copied to secondary
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$SecDisk = $SecMapping['disk'];
		$modifyTime = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1), "modified_time");
				
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, TEST_HOST_2), "File not uploaded to primary SS");
		//wait till file is copied to secondary
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_2,60) , "Failed to copy file to secondary disk");
		$newModifyTime = file_function::file_attributes($SecSS, "/$SecDisk/secondary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1), "modified_time");
		$this->assertEquals($modifyTime , $newModifyTime, "File copied across to secondary");

	}

	public function test_New_File_Put_While_Torrent_Copy_In_Progress() {
		
		// AIM : While a torrent is copying a file to the secondary space, put a new file in the primary disk
		// EXPECTED RESULT : after the first torrent copy is completed a new torrent is created which copies the file that was added last to the secondary again
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PriSS), "Torrent not created");
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_2, TEST_HOST_1), "File not uploaded to primary SS");
		sleep(2);
		$torrent_file_1 = torrent_functions::get_torrent_filename($PriSS);
		$this->assertEquals(count($torrent_file_1), 1, "Two torrent files created for the same disk");
		torrent_functions::wait_till_torrent_file_exists($PriSS, $torrent_file_1[0]);
		$this->assertTrue(torrent_functions::verify_torrent_file_creation($PriSS), "Torrent not created for second upload");
		$torrent_file_2 = torrent_functions::get_torrent_filename($PriSS);
		$this->assertNotEquals($torrent_file_1, $torrent_file_2, "same torrent file created for the second upload");
	}

	public function test_Torrent_Copy_Multiple_Files() {
		// AIM : Copy multiple files for torrent copy
		// EXPECTED RESULT : All the files should get copied to the secondary

		diskmapper_setup::reset_diskmapper_storage_servers();
		$file_path_array = torrent_functions::create_storage_directories(array(STORAGE_SERVER_1, STORAGE_SERVER_2), "data_1", TEST_HOST_1);
		$file_list = torrent_functions::create_test_file(STORAGE_SERVER_1, $file_path_array[0]."/test_file_1", 1048576, 3);
		$files = implode("\n",$file_list);
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
#		torrent_functions::update_dirty_file(STORAGE_SERVER_1, "data_1", $files);
		torrent_functions::create_dirty_file(array(STORAGE_SERVER_1 => "data_1"), $file_list);
		torrent_functions::chown_storageserver(array(STORAGE_SERVER_1, STORAGE_SERVER_2));
                diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "start");
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(array(STORAGE_SERVER_1 => $file_path_array[0], STORAGE_SERVER_2 => $file_path_array[1]), 300), "Failed to copy file to secondary");	

	}
	
	public function test_Torrent_Killed_on_Primary_SS() {
		// AIM : Verify that when a torrent gets killed it is automatically restarted.
		// EXPECTED RESULT : The torrent is restarted
		// upload a large file
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		for($i=0;$i<20;$i++){
			$status = trim(remote_function::remote_execution($PriSS,"ls /var/www/html/torrent"));
			if(strlen($status) > 0){
				for($i=0;$i<5;$i++){
					torrent_functions::kill_all_torrents($PriSS);
				}
				break;
			}
			sleep(1);
		}
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1 , 60), "Failed to copy file to secondary disk");
		$this->assertTrue(storage_server_functions::check_file_exists(DUMMY_FILE_1GB , TEST_HOST_1 , 'secondary' , 'test'), "File is not uploaded to secondary");
	}

	public function test_Torrent_Killed_on_Both_Servers() {
		// AIM : Verify that when a torrent gets killed it is automatically restarted
		// EXPECTED RESULT : The torrent is restarted
		// upload a large file
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		for($i=0;$i<20;$i++){
			$status = trim(remote_function::remote_execution($PriSS,"ls /var/www/html/torrent"));
			if(strlen($status) > 0){
				for($i=0;$i<5;$i++){
					torrent_functions::kill_all_torrents($PriSS);
					torrent_functions::kill_all_torrents($SecSS);
				}
				break;
			}
			sleep(1);
		}
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1 , 60), "Failed to copy file to secondary disk");
		$this->assertTrue(storage_server_functions::check_file_exists(DUMMY_FILE_1GB , TEST_HOST_1 , 'secondary' , 'test'), "File is not uploaded to secondary");

	}	

	public function test_Torrent_file_without_read_permission() {
		// AIM : Add a file without read permission
		// EXPECTED RESULT : Torrent should fail copying this file
		diskmapper_setup::reset_diskmapper_storage_servers();
		$file_path_array = torrent_functions::create_storage_directories(array(STORAGE_SERVER_1, STORAGE_SERVER_2), "data_1", TEST_HOST_1);
		$file_list = torrent_functions::create_test_file(STORAGE_SERVER_1, $file_path_array[0]."/test_file_1", 1024, 1);
		remote_function::remote_execution(STORAGE_SERVER_1, "sudo chmod 222 ".$file_path_array[0]."/test_file_1");
		torrent_functions::create_dirty_file(array(STORAGE_SERVER_1 => "data_1"), $file_list);
		torrent_functions::chown_storageserver(array(STORAGE_SERVER_1, STORAGE_SERVER_2));
		$this->assertFalse(torrent_functions::wait_for_torrent_copy(array(STORAGE_SERVER_1 => $file_path_array[0], STORAGE_SERVER_2 => $file_path_array[1]), 60), "File copied to secondary even with read only permission");	

	}
	
	public function test_Torrent_Copy_across_all_disks() {
		// AIM : Copy 1GB file across all the disk
		// EXPECTED RESULT : 6 torrents should be created and file copy should happen
		diskmapper_setup::reset_diskmapper_storage_servers();
		$file_path_list_1 = $file_path_list_2 = array();
		for($i=1 ; $i<7 ; $i++){
			$file_path_list_1[] = torrent_functions::create_storage_directories(array(STORAGE_SERVER_1, STORAGE_SERVER_2), "data_$i", "test_host_1_$i");
		}
		for($i=1 ; $i<7 ; $i++){
			$file_path_list_2[] = torrent_functions::create_storage_directories(array(STORAGE_SERVER_2, STORAGE_SERVER_1), "data_$i", "test_host_2_$i");
		}	
		$file_list_1 = $file_list_2 = array();	
		foreach($file_path_list_1 as $file_path){
			$temp_file_path = torrent_functions::create_test_file(STORAGE_SERVER_1, $file_path[0]."/test_file_1", 1073741824, 1, "zero");
			$file_list_1[] = $temp_file_path[0];
		}
		foreach($file_path_list_2 as $file_path){
			$temp_file_path = torrent_functions::create_test_file(STORAGE_SERVER_2, $file_path[0]."/test_file_1", 1073741824, 1, "zero");
			$file_list_2[] = $temp_file_path[0];
		}
		$idisk = 1;
		foreach($file_list_1 as $file_path){
			torrent_functions::update_dirty_file(STORAGE_SERVER_1, "data_$idisk", $file_path);
			$idisk++;
		}
		$idisk = 1;
		foreach($file_list_2 as $file_path){
			torrent_functions::update_dirty_file(STORAGE_SERVER_2, "data_$idisk", $file_path);
			$idisk++;
		}				
		torrent_functions::chown_storageserver(array(STORAGE_SERVER_1, STORAGE_SERVER_2));
		
		foreach($file_path_list_1 as $file_path){
			$this->assertTrue(torrent_functions::wait_for_torrent_copy(array(STORAGE_SERVER_1 => $file_path[0], STORAGE_SERVER_2 => $file_path[1]), 300), "Failed to copy file to secondary");	
		}
		foreach($file_path_list_2 as $file_path){
			$this->assertTrue(torrent_functions::wait_for_torrent_copy(array(STORAGE_SERVER_2 => $file_path[0], STORAGE_SERVER_1 => $file_path[1]), 300), "Failed to copy file to secondary");	
		}		
	}	
	
	public function test_File_Deleted_from_Primary() { // to be defined
		// AIM : Verify that when a file is deleted from the primary storage server for that particular host, the file is added to to_be_deleted entry and is deleted from the secondary
		// EXPECTED RESULT : The file is deleted from secondary
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1), "File not uploaded to primary SS");
		//wait till file is copied to sec
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");	
		sleep(2);
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping ['storage_server'];
		$PriDisk = $PriMapping['disk'];
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping(TEST_HOST_1);
		$SecSS = $SecMapping['storage_server'];
		$SecDisk = $SecMapping['disk'];
		storage_server_functions::clear_host_primary(TEST_HOST_1);
		remote_function::remote_execution($PriSS, "echo /$PriDisk"."/primary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1)." > /$PriDisk"."/to_be_deleted");
		torrent_functions::chown_storageserver(array($PriSS));
		sleep(10);
		$this->assertFalse(storage_server_functions::check_file_exists(DUMMY_FILE_1 , TEST_HOST_1 , 'secondary' , 'test'));
	}

	public function est_File_being_Copied_is_Deleted() { // to be defined
		// AIM : Verify behaviour of torretn when a file  that is being copied from the primary to the secondary space gets deleted.
		// EXPECTED RESULT : 
		diskmapper_setup::reset_diskmapper_storage_servers();
		// upload a large file
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1GB, TEST_HOST_1), "File not uploaded to primary SS");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		$command_to_be_executed = "sudo rm -rf /$PriDisk/primary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".DUMMY_FILE_1;
		for($i=0;$i<20;$i++){
			$status = trim(remote_function::remote_execution($PriSS,"ls /var/www/html/torrent"));
			if(strlen($status) > 0){
				remote_function::remote_execution($PriSS , $command_to_be_executed);
				break;
			}
			sleep(1);
		}
		$TorrentCOntents = trim(remote_function::remote_execution($PriSS,"ls /var/www/html/torrent"));
		$status = storage_server_functions::check_file_exists(DUMMY_FILE_1GB , TEST_HOST_1 , 'secondary' , 'test');
	}
	
	
}

class Torrents_TestCase_Full extends Torrents_TestCase	{

	public function keyProvider() {
		return Utility::provideKeys();
	}
}		
