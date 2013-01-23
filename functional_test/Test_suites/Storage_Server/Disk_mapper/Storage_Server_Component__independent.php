<?php

abstract class StorageServerComponent_TestCase extends ZStore_TestCase {

	public function test_Verify_Dirty_File_Entry_Added() {
		// AIM : Verify that once the backup upload is complete the storage server component (SSC) on each SS adds the entry to the dirty file
		// EXPECTED RESULT : The entries are made as expected
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		diskmapper_setup::disk_mapper_service(DISK_MAPPER_SERVER_ACTIVE, "stop");
		$command_to_be_executed = "cat /$PriDisk/dirty";
		$dirty_File_Contents = trim(remote_function::remote_execution($PriSS ,$command_to_be_executed));	
		$expectedContents = "/".$PriDisk."/primary/".TEST_HOST_1."/".MEMBASE_CLOUD."/test/".basename(DUMMY_FILE_1);
		$this->assertEquals($dirty_File_Contents , $expectedContents,"Entry not the same as expected");
	}

	public function test_Verify_Dirty_File_Entry_Deleted() {
		// AIM : Once the torrent has completed copying ther file to the secondary disk on another storage server, the dirty entry is deleted from the storage server where the primary resides.
		// EXPECTED RESULT : THe entry is deleted
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		//wait till file is copied to secondary
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");		
		sleep(10);	
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
		$command_to_be_executed = "cat /$PriDisk/dirty";
		$dirty_File_Contents = trim(remote_function::remote_execution($PriSS ,$command_to_be_executed));
		$this->assertEquals(strlen($dirty_File_Contents),0,"Dirty file entry not deleted");
	}

	public function test_Verify_Torrent_Delelted_After_Copy_To_Secondary() {
		// AIM : once the torrent has completed copying the file to the secondary disk on another server, the torrent file on the primary storage server is deleted
		//  EXPECTED RESULT : The torrent file is deleted
		diskmapper_setup::reset_diskmapper_storage_servers();
		$this->assertTrue(diskmapper_api::zstore_put(DUMMY_FILE_1, TEST_HOST_1),"File not uploaded to primary SS");
		$PriMapping = diskmapper_functions::get_primary_partition_mapping(TEST_HOST_1);
		$PriSS = $PriMapping['storage_server'];
		$PriDisk = $PriMapping['disk'];
            //wait till file is copied to secondary
		$this->assertTrue(torrent_functions::wait_for_torrent_copy(TEST_HOST_1,60) , "Failed to copy file to secondary disk");
		$status = remote_function::remote_execution($PriSS,"ls /var/www/html/torrent/*");
		$this->assertTrue(strpos($status, "No such file or directory") >0,"Torrent file not deleted after the file has been copied to secondary");
	}
}

class StorageServerComponent_TestCase_Full extends StorageServerComponent_TestCase {

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

