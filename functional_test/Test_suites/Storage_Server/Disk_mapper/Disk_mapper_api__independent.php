<?php

abstract class DiskMapper_api_TestCase extends ZStore_TestCase {

	public function test_add_entry_api_dirty_file() {
		//AIM: To Test Addition of an entry in dirty file via the api
		//Expected Result: Dirty entry was added in the appropriate disk as per the entry		
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "dirty_files", "/data_1/test_file");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/dirty"));
		$this->assertEquals($val,"/data_1/test_file", "file not added");
	}

	public function test_remove_entry_api_dirty_file() {
		//AIM: To Test Removal of an entry in bad disk file via the api
		//Expected Result: Entry is removed from the dirty file
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "dirty_files", "/data_1/test_file");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/dirty"));
		$this->assertEquals($val,"/data_1/test_file", "file not added");
		storage_server_api::remove_entry_api(STORAGE_SERVER_1, "dirty_files", "/data_1/test_file");
		$new_val = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/dirty"));
		$this->assertEquals(strlen($new_val),0,"entry not removed");
	}

	public function test_add_entry_api_bad_disk() {
		//AIM: To Test Addition of an entry in bad_disk file via the api
		//Expected Result: entry was added
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "bad_disk", "data_1");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/bad_disk"));
		$this->assertEquals($val,"data_1", "file not added");
	}

	public function test_remove_entry_api_bad_disk() {
		//AIM: To Test Removal of an entry in bad_disk file via the api
		//Expected Result:the entry was removed
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "bad_disk", "data_1");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/bad_disk"));
		$this->assertEquals($val,"data_1", "file not added");
		storage_server_api::remove_entry_api(STORAGE_SERVER_1, "bad_disk", "data_1");
		$new_val = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/bad_disk"));
		$this->assertEquals(strlen($new_val),0,"entry not removed");
	}

	public function test_add_entry_api_to_be_promoted() {
		//AIM: To Test Addition of an entry in to_be_promoted file via the api
		//Expected Result:entry was added 
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "to_be_promoted", "test_data");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/to_be_promoted"));
		$this->assertEquals($val,"test_data", "file not added");
	}


	public function test_remove_entry_api_to_be_promoted() {
		//AIM: To Test Removal of an entry in to_be_promoted file via the api
		//Expected Result:  entry was removed
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "to_be_promoted", "test_data");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/to_be_promoted"));
		$this->assertEquals($val,"test_data", "file not added");
		storage_server_api::remove_entry_api(STORAGE_SERVER_1, "to_be_promoted", "test_data");
		$new_val = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/to_be_promoted"));
		$this->assertEquals(strlen($new_val),0,"entry not removed");
	}


	public function test_add_entry_api_copy_completed() {
		//AIM: To Test Addition of an entry in copy_completed file via the api
		//Expected Result:entry was added
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "copy_completed", "test_data");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/copy_completed"));
		$this->assertEquals($val,"test_data", "file not added");
	}


	public function test_remove_entry_api_copy_completed() {
		//AIM: To Test Removal of an entry in copy_completed file via the api
		//Expected Result:  entry was removed
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "copy_completed", "test_data");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/copy_completed"));
		$this->assertEquals($val,"test_data", "file not added");
		storage_server_api::remove_entry_api(STORAGE_SERVER_1, "copy_completed", "test_data");
		$new_val = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/copy_completed"));
		$this->assertEquals(strlen($new_val),0,"entry not removed");
	}

	public function test_remove_entry_api_to_be_deleted() {
		//AIM: To test removal of to_be_deleted entry
		//Expected Result: entry was removed
		diskmapper_setup::reset_diskmapper_storage_servers();
		remote_function::remote_execution(STORAGE_SERVER_1, "echo /data_1/test >> /data_1/to_be_deleted ; sudo chown storageserver /data_1/to_be_deleted");
		$file = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/to_be_deleted"));
		$this->assertEquals($file,"/data_1/test","entry not added properly");
		storage_server_api::remove_entry_api(STORAGE_SERVER_1, "to_be_deleted", "/data_1/test");
		$new_val = remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/to_be_deleted");
		$this->assertEquals(strlen($new_val),0,"entry not removed");
	}

	public function test_list_api() {
		//AIM: To Test the list api after uploading a file.
		//Expected result: the uploaded files are listed properly
		diskmapper_setup::reset_diskmapper_storage_servers();
		diskmapper_api::zstore_put(DUMMY_FILE_1, "game-test-slave-1");	
		$PriMapping = diskmapper_functions::get_primary_partition_mapping("game-test-slave-1");
		$PriSS = $PriMapping['storage_server'];
		$list = storage_server_api::list_api($PriSS,"/".GAME_ID."/");
		$expected_list = "s3://".$PriSS."/".GAME_ID."/"."game-test-slave-1/";
		$this->assertEquals($list,$expected_list,"listing failed");

	}

	public function test_list_api_recursive() {
		//AIM: To Test the list api after uploading a file.
		//Expected result: the uploaded files are listed recursively.
		$flag=False;
		diskmapper_setup::reset_diskmapper_storage_servers();
		diskmapper_api::zstore_put(DUMMY_FILE_1, "game-test-slave-1");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping("game-test-slave-1");
		$PriSS = $PriMapping['storage_server'];
		$list = storage_server_api::list_api($PriSS,"/".GAME_ID."/", "true");
		$flag = stristr($list,"dummy_file_1")?True:False;
		$this->assertTrue($flag,"listing recursively failed");

	}

	public function test_get_file_api_dirty_files() {
		//AIM: To Test the get_file api for dirty files	
		//Expected result: dirty file contents are returned from the api call
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "dirty_files", "/data_1/test_file");
		$file_content = storage_server_api::get_file_api(STORAGE_SERVER_1,"dirty_files");
		$this->assertEquals(count($file_content),1,"unexpected number of entries");
		$this->assertEquals($file_content[0],"/data_1/test_file", "unexpected entry");
	}

	public function test_get_file_api_to_be_deleted_files() {
		//AIM: To Test the get_file api for to_be_deleted files	
		//Expected result: to_be_deleted file contents are returned from the api call
		diskmapper_setup::reset_diskmapper_storage_servers();
		remote_function::remote_execution(STORAGE_SERVER_1, "echo /data_1/test >> /data_1/to_be_deleted ; sudo chown storageserver /data_1/to_be_deleted");
		$file = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/to_be_deleted"));
		$this->assertEquals($file,"/data_1/test","entry not added properly");
		$file_content = storage_server_api::get_file_api(STORAGE_SERVER_1,"to_be_deleted");
		$this->assertEquals(count($file_content),1,"unexpected number of entries");
		$this->assertEquals($file_content[0],"/data_1/test", "unexpected entry");
	}

	public function test_get_file_api_bad_disk() {
		//AIM: To test get_file api for the bad_disk files
		//Expected Result: contents of bad_disk file are returned from the api call
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "bad_disk", "data_1");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/bad_disk"));
		$this->assertEquals($val,"data_1", "file not added");
		$file_content = storage_server_api::get_file_api(STORAGE_SERVER_1,"bad_disk");
		$this->assertEquals(count($file_content),1,"unexpected number of entries");
		$this->assertEquals($file_content[0],"data_1", "unexpected entry");

	}

	public function test_get_file_api_to_be_promoted() {
		//AIM: To test get_file api for the to_be_promoted files
		//Expected Result: contents of to_be_promoted file are returned from the api call
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "to_be_promoted", "/data_1/test_file");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/to_be_promoted"));
		$this->assertEquals($val,"/data_1/test_file", "file not added");
		$file_content = storage_server_api::get_file_api(STORAGE_SERVER_1,"to_be_promoted");
		$this->assertEquals(count($file_content),1,"unexpected number of entries");
		$this->assertEquals($file_content[0],"/data_1/test_file", "unexpected entry");

	}


	public function test_get_file_api_copy_completed() {
		//AIM: To test get_file api for the copy_completed files
		//Expected Result: contents of copy_completed file are returned from the api call
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::add_entry_api(STORAGE_SERVER_1, "copy_completed", "/data_1/test_file");
		$val =trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /var/tmp/disk_mapper/copy_completed"));
		$this->assertEquals($val,"/data_1/test_file", "file not added");
		$file_content = storage_server_api::get_file_api(STORAGE_SERVER_1,"copy_completed");
		$this->assertEquals(count($file_content),1,"unexpected number of entries");
		$this->assertEquals($file_content[0],"/data_1/test_file", "unexpected entry");

	}

	public function test_initialize_host_api_primary() {
		//AIM : To initialize a primary host via api call
		//Expected Result: primary partition was initialised and reflected in mapping
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::initialize_host_api(STORAGE_SERVER_1, "data_1", GAME_ID, "test_host_1", "primary");
		sleep(10);
		$PriMapping = diskmapper_functions::get_primary_partition_mapping("test_host_1");
		$storage_server_ip = end(explode(" ",general_function::execute_command("host ".STORAGE_SERVER_1)));
		$this->assertEquals($PriMapping['storage_server'],$storage_server_ip, "mismatch in storage server");
		$this->assertEquals($PriMapping['disk'],"data_1","mismatch in disk");
	}

	public function test_initialize_host_api_secondary() {
		//AIM : To initialize a secondary host via api call
		//Expected Result: secondary partition was initialised and reflected in mapping
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::initialize_host_api(STORAGE_SERVER_1, "data_1", GAME_ID, "test_host_1", "secondary");
		sleep(10);
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping("test_host_1");
		$storage_server_ip = end(explode(" ",general_function::execute_command("host ".STORAGE_SERVER_1)));
		$this->assertEquals($SecMapping['storage_server'],$storage_server_ip, "mismatch in storage server");
		$this->assertEquals($SecMapping['disk'],"data_1","mismatch in disk");
	}

	public function test_initialize_host_api_primary_promoting() {
		//AIM : To initialize a primary host as promoting via api call
		//Expected Result: primary partition was initialised and reflected in mapping
		diskmapper_setup::reset_diskmapper_storage_servers();
		storage_server_api::initialize_host_api(STORAGE_SERVER_1, "data_1", GAME_ID, "test_host_1", "primary", "true");
		sleep(10);
		$PriMapping = diskmapper_functions::get_primary_partition_mapping("promoting");
		$storage_server_ip = end(explode(" ",general_function::execute_command("host ".STORAGE_SERVER_1)));
		$this->assertEquals($PriMapping['storage_server'],$storage_server_ip, "mismatch in storage server");
		$this->assertEquals($PriMapping['disk'],"data_1","mismatch in disk");
		$this->assertTrue(file_function::check_file_exists(STORAGE_SERVER_1,"/data_1/primary/test_host_1/.promoting"),".promoting file not found");
	}

	public function test_get_mtime_api() {
		//AIM: To test working of get_mtime api
		//Expected result: get_mtime returns the last modified time of a given partition
		diskmapper_setup::reset_diskmapper_storage_servers();
		diskmapper_api::zstore_put(DUMMY_FILE_1, "game-test-slave-1");
		sleep(10);
		$PriMapping = diskmapper_functions::get_primary_partition_mapping("game-test-slave-1");
		$PriSS=$PriMapping['storage_server'];
		$Pridisk=$PriMapping['disk'];
		$time_old= storage_server_api::get_mtime_api($PriSS,$Pridisk,"game-test-slave-1");
		diskmapper_api::zstore_put(DUMMY_FILE_2, "game-test-slave-1");	
		$time_new= storage_server_api::get_mtime_api($PriSS,$Pridisk,"game-test-slave-1");
		$this->assertGreaterThan($time_old,$time_new,"mtimes are equal");
		sleep(10);
		$time_new_2= storage_server_api::get_mtime_api($PriSS,$Pridisk,"game-test-slave-1");
		$this->assertEquals($time_new_2,$time_new, "mtimes changed without modification");
		$SecMapping = diskmapper_functions::get_secondary_partition_mapping("game-test-slave-1");
		$SecSS=$SecMapping['storage_server'];
		$Secdisk=$SecMapping['disk'];
		$time_sec= storage_server_api::get_mtime_api($SecSS,$Secdisk,"game-test-slave-1","secondary");
		$this->assertGreaterThan($time_new,$time_sec,"mtime of secondary not greater than primary");
		$time_false = storage_server_api::get_mtime_api($PriSS,$Pridisk,"false-slave");
		$this->assertEquals($time_false, 0, "mtime returned for a false host name");
	}

	public function test_get_config_api() {
		//AIM: To test working of get_config api
		//Expected Result:: the api returns the current configuration of the storage_server
		diskmapper_setup::reset_diskmapper_storage_servers();
		diskmapper_api::zstore_put(DUMMY_FILE_1, "game-test-slave-1");
		sleep(10);
		$PriMapping = diskmapper_functions::get_primary_partition_mapping("game-test-slave-1");
		$PriSS=$PriMapping['storage_server'];
		$Pridisk=$PriMapping['disk'];
		$map = storage_server_api::get_config_api($PriSS);
		$host_name = $map[$Pridisk]["primary"];
		$this->assertEquals($host_name, "game-test-slave-1", "host names does not match");
	}	 

	public function test_create_torrent_api() {
		//AIM: To test working of create_torrent api
		//Expected result: After the api was called , ensure that a torrent file was created
		diskmapper_setup::reset_diskmapper_storage_servers();
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE,"stop");
		remote_function::remote_execution(STORAGE_SERVER_1,"sudo mkdir -p /data_1/primary/test_slave/cloud/folder/;sudo chown -R storageserver /data_1/;sudo chmod -R 777 /data_1/primary/test_slave/");
		remote_function::remote_execution(STORAGE_SERVER_1,"echo test_data >> /data_1/primary/test_slave/cloud/folder/file;sudo chown storageserver /data_1/primary/test_slave/cloud/folder/file");
		$val = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/primary/test_slave/cloud/folder/file"));
		$this->assertEquals($val,"test_data","file not created");
		$result = storage_server_api::create_torrent_api(STORAGE_SERVER_1, "/data_1/primary/test_slave/cloud/folder/file");
		$file_name = end(explode("/",$result));
		$this->assertTrue(file_function::check_file_exists(STORAGE_SERVER_1, "/var/www/html/torrent/".$file_name),"Torrent File not created");
	}

	public function test_start_download_api() {
		//AIM: To test start_download api
		//Expected result: On giving a valid file path and torrent url, the file was downloaded
		$success=false;
		diskmapper_setup::reset_diskmapper_storage_servers();
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE,"stop");
		remote_function::remote_execution(STORAGE_SERVER_1,"sudo mkdir -p /data_1/primary/test_slave/cloud/folder/;sudo chown -R storageserver /data_1/;sudo chmod -R 777 /data_1/primary/test_slave/");
		remote_function::remote_execution(STORAGE_SERVER_1,"echo test_data >> /data_1/primary/test_slave/cloud/folder/file;sudo chown storageserver /data_1/primary/test_slave/cloud/folder/file");
		$val = trim(remote_function::remote_execution(STORAGE_SERVER_1, "cat /data_1/primary/test_slave/cloud/folder/file"));
		$this->assertEquals($val,"test_data","file not created");
		$torrent_url = storage_server_api::create_torrent_api(STORAGE_SERVER_1, "/data_1/primary/test_slave/cloud/folder/file");
		$file_name = end(explode("/",$torrent_url));
		$this->assertTrue(file_function::check_file_exists(STORAGE_SERVER_1, "/var/www/html/torrent/".$file_name),"Torrent File not created");
		remote_function::remote_execution(STORAGE_SERVER_2,"sudo mkdir -p /data_1/secondary/test_slave/cloud/folder/;sudo chown -R storageserver /data_1/;sudo chmod -R 777 /data_1/secondary/test_slave/");
		$result = storage_server_api::start_download_api(STORAGE_SERVER_2, "/data_1/secondary/test_slave/cloud/folder/", $torrent_url);
		$success= stristr($result,"downloaded")?True:False;
		$this->assertTrue($success,"Success message not returned by the api");
		$this->assertTrue(file_function::check_file_exists(STORAGE_SERVER_2, "/data_1/secondary/test_slave/cloud/folder/file"),"File not downloaded to secondary");
		$val_sec = trim(remote_function::remote_execution(STORAGE_SERVER_2, "cat /data_1/secondary/test_slave/cloud/folder/file"));
		$this->assertEquals($val_sec,"test_data","file content different in secondary");

	}


}

class DiskMapper_api_TestCase_Full extends DiskMapper_api_TestCase {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}
