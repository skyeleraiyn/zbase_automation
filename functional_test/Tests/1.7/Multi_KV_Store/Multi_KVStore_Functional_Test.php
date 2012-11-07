<?php

abstract class Multi_KV_Store_TestCase extends ZStore_TestCase {

	public function test_Verify_Shard_Pattern(){
		//Verify that keys are being correctly sharded
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(4, 1000, 1, 20),"Failed adding keys");
		// Ensure that keys are persisted on master
		$this->assertTrue(Utility::Check_keys_are_persisted(10),"Failed persisiting keys");
		// Check the count of keys under each /data* partition
		$sqlite_count = mb_backup_commands::get_sqlite_item_count(TEST_HOST_1);
		// Verify that keys are sharded correctly
		for($i=0;$i<count($sqlite_count);$i++){
			//Ensure that each kvstore gets 1 key
			$this->assertEquals($sqlite_count[$i],1,"Keys not shared correctly");		
		}
	} 

	public function test_Deleting_Database(){
		// Membase working with 1 disk and 1 kvstore after the database is deleted.
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_4", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<1500;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		// Ensure that keys are persisted on master
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Delete the database
		remote_function::remote_execution(TEST_HOST_1,"sudo rm -rf /data_*/membase/*");
		for($key_id=0;$key_id<1500;$key_id++){
			$val = $mc->get("testkey_".$key_id);
			$this->assertEquals(strcmp($val,"testvalue_".$key_id),0,"Get failed for key testkey_".$key_id);
		}
		// Set on a few more keys
		for($key_id=1500;$key_id<1600;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"test_value_".$key_id));
		}
		// Verify that these keys are not persisted
		$this->assertFalse(Utility::Check_keys_are_persisted() , "Keys added after deleting the database are also persisted");
	}

	public function test_One_Disk_Goes_Down(){
		// Membase working with 4 disk and 4 kvstore after one of the disks go down.
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 2000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<10000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Delete one of the databses
		$command_to_be_executed = "sudo rm -rf /data_2/membase/*";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		for($key_id=0;$key_id<10000;$key_id++){
			$val = $mc->get("testkey_".$key_id);
			$this->assertEquals(strcmp($val,"testvalue_".$key_id),0,"Get failed for key testkey_".$key_id);
		}
		// Set on a few more keys
		for($key_id=10000;$key_id<20000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"test_value_".$key_id));
		}
		sleep(10);
		$queue_size = stats_functions::get_all_stats(TEST_HOST_1, "ep_queue_size");
		$this->assertGreaterThan(1900,$queue_size, "Keys mapping to the deleted database are also persisted");
		$this->assertLessThan(2100 , $queue_size , "Keys mapping to the deleted database are also persisted");
	}

	public function test_Master_Slave_Mode_Disk_Goes_Down_on_Master(){
		//Operation in master slave mode when one of the kvstores on the master is deleted.
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		// While being pumped remove databse on master
		$command_to_be_executed = "sudo rm -rf /data_2/membase/*";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,4000 , "All keys not replicated to slave");

	}

	public function test_Stop_and_Restart_Persistance_on_Master(){
		// Effect of stopping persistence and then restarting it.
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		//Stop persistence on master
		flushctl_commands::Run_flushctl_command(TEST_HOST_1 ,'stop');
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 2001, 20),"Failed adding keys");
		$this->assertFalse(Utility::Check_keys_are_persisted() , "Keys are being persisted on master despite persistence being off");
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,4000 , "All keys not replicated to slave");
		flushctl_commands::Run_flushctl_command(TEST_HOST_1 ,'start');
		$this->assertTrue(Utility::Check_keys_are_persisted() , "Keys not persisted on the master after turning persistence on");
	}

	public function test_flush_all_All_Keys_Persisted(){
		//Effect of flush_all when all keys are persisted
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		// Ensure that all keys are persisted
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Issue flushall
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"enable_flushall","true");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2,"enable_flushall","true");
		remote_function::remote_execution(TEST_HOST_1 ,"echo flush_all |nc 0 11211");
		// Ensure that curr items are zero
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,0 , "All keys not flushed");
		// Ensure from sqlite files that all items flushed
		$sqlite_count = mb_backup_commands::get_sqlite_item_count(TEST_HOST_1);
		for($i=0;$i<count($sqlite_count) ;$i++)	
		$this->assertEquals($sqlite_count[$i],0, "Not all keys flushed from the sqlite files");
	}

	public function test_flush_all_Few_Persisted(){
		// Effect of flush_all when only a few of the keys are persisted
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"enable_flushall","true");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_2,"enable_flushall","true");
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		// Stop persisitence so that we have some keys that are not persisted
		flushctl_commands::Run_flushctl_command(TEST_HOST_1,"stop");
		$this->assertTrue(Data_generation::add_keys(10000, 1000,2001, 20),"Failed adding keys");
		flushctl_commands::Run_flushctl_command(TEST_HOST_1,"start");
		// Issue flush all while few persisted
		remote_function::remote_execution(TEST_HOST_1 ,"echo flush_all |nc 0 11211");
		// Ensure that curr items are zero
		sleep(30);
		$stats = stats_functions::get_stats_netcat(TEST_HOST_2,"curr_items");
		$this->assertTrue(strpos($stats,"curr_items 0")>0 , "All keys not flushed" );
		// Ensure from sqlite files that all items flushed
		$sqlite_count = mb_backup_commands::get_sqlite_item_count(TEST_HOST_1);
		for($i=0;$i<count($sqlite_count) ;$i++)
		$this->assertEquals($sqlite_count[$i],0, "Not all keys flushed from the sqlite files");
	}

	public function test_Restart_memcached(){	
		// Effect of stopping memcached and restarting it again... Stop the memcached server before the queues have completely normalized
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<1000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		sleep(10);
		flushctl_commands::Run_flushctl_command(TEST_HOST_1 ,'stop');
		for($key_id=1000;$key_id<2000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		sleep(10);
		membase_function::stop_memcached_service(TEST_HOST_1);
		$this->assertTrue(membase_function::start_memcached_service(TEST_HOST_1));
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<1000;$key_id++){
			$val = $mc->get("testkey_".$key_id);
			$this->assertEquals(strcmp($val,"testvalue_".$key_id),0,"Get failed for key testkey_".$key_id);
		}
	}

	public function est_Interchange_DataBase(){		// Gets and sets not failing
		// Effect of interchanging databases
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<4;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}

		// Ensure that keys are replicated to the slave
		sleep(2);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys on master");
		membase_function::stop_memcached_service(TEST_HOST_1);
		// Swap the databases
		$command_to_be_executed = "sudo cp -r /data_1/membase /tmp/ ;sudo cp -r /data_2/membase /data_1/ ; sudo cp -r /tmp/membase /data_2/ ; sudo cp -r /data_3/membase /tmp/ ;sudo cp -r /data_4/membase /data_3/ ; sudo cp -r /tmp/membase /data_4/ ";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		membase_function::stop_memcached_service(TEST_HOST_1);
		membase_function::start_memcached_service(TEST_HOST_1);
		$mc = Connection::getMaster();
		for($key_id = 1 ; $key_id <= 2000 ; $key_id++)
		$this->assertFalse($mc->get("testkey_".$key_id) , "Get successful for key testkey_".$key_id);
		for($key_id = 2001;$key_id<=2100 ; $i++)
		$this->assertFalse($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set successful for key testkey_".$key_id);
		$mc->set("testkey_1","random_testval");
		membase_function::restart_memcached_service(TEST_HOST_1);	
		$mc = Connection::getMaster();
		for($key_id = 0 ; $key_id <= 4 ; $key_id++)
		$this->assertFalse($mc->get("testkey_".$key_id) , "Get successful for key testkey_".$key_id);
	}	

	public function test_Corrupt_DataBase_Header(){	
		// Effect of corrupt databases on membase
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys on master");
		membase_function::stop_memcached_service(TEST_HOST_1);
		// Corrupt the databases
		for($i=1;$i<=4;$i++){
			for($j=0;$j<4;$j++){
				$file = "/data_".$i."/membase/ep.db-".$j.".sqlite";
				sqlite_functions::corrupt_sqlite_file($file);
			}
		}
		remote_function::remote_execution(TEST_HOST_1,"sudo /etc/init.d/memcached start");
		$command_to_be_executed = "tail -8 ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"sqlite error:  file is encrypted or is not a database")>0, "Log entries not as expected");
	}

	public function est_IBR(){					//Restore fails
		// Incremental backups and restore on a server running multi kv store
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<2000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		// Take incremental backup using backup daemon
		mb_backup_commands::clear_backup_data(TEST_HOST_2);
		mb_backup_commands::clear_temp_backup_data(TEST_HOST_2);
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		// Kill vbucket migrator between master and slave
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		membase_function::stop_memcached_service(TEST_HOST_2);
		remote_function::remote_execution(TEST_HOST_2,"sudo rm -rf /data_*/membase/*");
		membase_function::start_memcached_service(TEST_HOST_2);
		// Perform restore
		mb_restore_commands::restore_server(TEST_HOST_2);
		//Attempt get on all keys
		$mc = Connection::getSlave();
		for($key_id=0;$key_id<2000;$key_id++){
			$val = $mc->get("testkey_".$key_id);
			$this->assertEquals(strcmp($val,"testvalue_".$key_id),0,"Get failed for key testkey_".$key_id);
		}	
	}

	public function test_Backfill_Based_Replication_Master_to_Slave(){
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<3000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		//Kill vbucket migrator between master and slave
		$status = service_function::control_service(TEST_HOST_1, VBUCKETMIGRATOR_SERVICE, "stop");
		//Deregister replication tap
		$status = tap_commands::deregister_replication_tap_name(TEST_HOST_1);
		membase_function::stop_memcached_service(TEST_HOST_2);
		remote_function::remote_execution(TEST_HOST_2,"sudo rm -rf /data_*/membase/*");
		membase_function::start_memcached_service(TEST_HOST_2);
		//Register backfill based replication tap
		$status = tap_commands::register_replication_tap_name(TEST_HOST_1," -l 0 -b");
		$status = vbucketmigrator_function::start_vbucketmigrator_service(TEST_HOST_1);
		sleep(30);
		$mc = Connection::getSlave();
		for($key_id=0;$key_id<3000;$key_id++){
			$val = $mc->get("testkey_".$key_id);
			$this->assertEquals(strcmp($val,"testvalue_".$key_id),0,"Get failed for key testkey_".$key_id);
		}
	}

	public function test_Backfill_Based_Backup_on_Slave(){
		membase_function::reset_membase_vbucketmigrator(TEST_HOST_1,TEST_HOST_2);
		//		membase_function::reset_servers_and_backupfiles(TEST_HOST_1, TEST_HOST_2);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$mc = Connection::getMaster();
		for($key_id=0;$key_id<2000;$key_id++){
			$this->assertTrue($mc->set("testkey_".$key_id,"testvalue_".$key_id),"Set failed for key testkey_".$key_id);
		}
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		// Take incremental backup using backup daemon
		mb_backup_commands::clear_backup_data(TEST_HOST_2);
		mb_backup_commands::clear_temp_backup_data(TEST_HOST_2);
		mb_backup_commands::start_backup_daemon(TEST_HOST_2);
		$this->assertTrue(mb_backup_commands::verify_membase_backup_success(), "Failed to upload the backup files to Storage Server");
		mb_backup_commands::stop_backup_daemon(TEST_HOST_2);
		mb_backup_commands::clear_storage_server();
		mb_backup_commands::start_backup_daemon_full(TEST_HOST_2);
		// Verfiy correctness of backups uploaded
		//Comparing values across backup and actual db
		$array = mb_backup_commands::list_master_backups();
		$backup_checksum_array = array();
		$backup_checksum_array = explode("\n", sqlite_functions::sqlite_select(STORAGE_SERVER, "cksum", "cpoint_op", trim($array[0])));
		$db_checksum_array = sqlite_functions::db_sqlite_select(TEST_HOST_2, "cksum", "kv");
		$diff_array = array_diff($backup_checksum_array, $db_checksum_array);
		$this->assertEquals(count($diff_array), 0, "Checksums across DB and Backups do not match");

	}

}

class Multi_KV_Store_TestCase_Full extends Multi_KV_Store_TestCase{
	public function keyProvider() {
		return Utility::provideKeys();
	}

}
