<?php

/* This suite is supported for CentOS 6*/

abstract class Multi_KV_Store_TestCase extends ZStore_TestCase {

	public function test_Verify_Shard_Pattern(){
		//Verify that keys are being correctly sharded
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(1000, 1000, 1, 20),"Failed adding keys");
		// Ensure that keys are persisted on master
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Check the count of keys under each /data* partition
		$sqlite_count = zbase_function::get_sqlite_item_count(TEST_HOST_1);
		// Verify that keys are sharded correctly
		for($i=0;$i<count($sqlite_count);$i++){
			//Ensure that each kvstore gets 1 key
			$this->assertGreaterThan(280, $sqlite_count[$i], "Keys not shared correctly");		
		}
	} 

	public function test_One_Disk_Goes_Down(){ // needs umount for wal mode
		// Zbase working with 3 disk and 3 kvstore after one of the disks go down.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		//flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 500000, 1, 10240), "adding keys failed");		
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Delete one of the databses
		$command_to_be_executed = "sudo rm -rf /data_2/zbase/*";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		//$this->assertTrue(Data_generation::verify_added_keys(TEST_HOST_1, 4, "testvalue", 1), "verifying keys failed");
		// Set on a few more keys
		$this->assertTrue(Data_generation::add_keys(100000,500000, 501, 10240), "adding keys failed");
		$persisted=stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$enqueued=stats_functions::get_all_stats(TEST_HOST_1, "ep_total_enqueued");
		$diff = $enqueued-$persisted;
		$this->assertGreaterThan(150, $diff, "Unexpected persistence behaviour");
	}

	public function test_Multi_Disk_Goes_Down(){
		// Zbase working with 3 disk and 3 kvstore after one of the disks go down.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, "testvalue"), "adding keys failed");		
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Delete one of the databses
		$command_to_be_executed = "sudo rm -rf /data_*/zbase/*";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		$this->assertTrue(Data_generation::verify_added_keys(TEST_HOST_1, 4, "testvalue", 1), "verifying keys failed");
		// Set on a few more keys
		$this->assertTrue(Data_generation::add_keys(500, 100, 501, "testvalue"), "adding keys failed");
		$persisted=stats_functions::get_all_stats(TEST_HOST_1, "ep_total_persisted");
		$enqueued=stats_functions::get_all_stats(TEST_HOST_1, "ep_total_enqueued");
		$diff = $enqueued-$persisted;
		$this->assertEquals(500, $diff, "Unexpected persistence behaviour");
	}
	
	public function test_Stop_and_Restart_Persistance_on_Master(){
		// Effect of stopping persistence and then restarting it.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, 20),"Failed adding keys");
		
		//Stop persistence on master
		flushctl_commands::Run_flushctl_command(TEST_HOST_1 ,'stop');
		$this->assertTrue(Data_generation::add_keys(500, 100, 501, 20),"Failed adding keys");
		$this->assertFalse(Utility::Check_keys_are_persisted() , "Keys are being persisted on master despite persistence being off");
		flushctl_commands::Run_flushctl_command(TEST_HOST_1 ,'start');
		$this->assertTrue(Utility::Check_keys_are_persisted() , "Keys not persisted on the master after turning persistence on");
	}

	public function test_flush_all_All_Keys_Persisted(){
		//Effect of flush_all when all keys are persisted
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, 20),"Failed adding keys");
		// Ensure that all keys are persisted
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		// Issue flushall
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"enable_flushall","true");
		remote_function::remote_execution(TEST_HOST_1 ,"echo flush_all |nc 0 11211");
		// Ensure that curr items are zero
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,0 , "All keys not flushed");
		// Ensure from sqlite files that all items flushed
		$sqlite_count = zbase_function::get_sqlite_item_count(TEST_HOST_1);
		for($i=0;$i<count($sqlite_count) ;$i++)	
		$this->assertEquals($sqlite_count[$i],0, "Not all keys flushed from the sqlite files");
	}

	public function test_Restart_memcached(){	
		// Effect of stopping memcached and restarting it again... Stop the memcached server before the queues have completely normalized
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, "testvalue"), "adding keys failed");
		sleep(10);
		flushctl_commands::Run_flushctl_command(TEST_HOST_1 ,'stop');
		$this->assertTrue(Data_generation::add_keys(500, 100, 1001, "testvalue"), "adding keys failed");
		sleep(10);
		zbase_setup::memcached_service(TEST_HOST_1, "stop");
		$this->assertTrue(zbase_setup::memcached_service(TEST_HOST_1, "start"));
		$this->assertTrue(Data_generation::verify_added_keys(TEST_HOST_1, 500, "testvalue", 1), "verifying keys failed");
	}
	
	public function test_Corrupt_DataBase_Header(){	
		// Effect of corrupt databases on zbase
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 1000);
		$this->assertTrue(Data_generation::add_keys(2000, 1000, 1, 20),"Failed adding keys");
		// Ensure that keys are replicated to the slave
		sleep(30);
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$this->assertEquals($curr_items ,2000 , "All keys not replicated to slave");
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys on master");
		zbase_setup::memcached_service(TEST_HOST_1, "stop");
		// Corrupt the databases
		for($i=1;$i<=4;$i++){
			for($j=0;$j<4;$j++){
				$file = "/data_".$i."/zbase/ep.db-".$j.".sqlite";
				sqlite_functions::corrupt_sqlite_file(TEST_HOST_1, $file);
			}
		}
		remote_function::remote_execution(TEST_HOST_1,"sudo /etc/init.d/memcached start");
		$command_to_be_executed = "tail -8 ".ZBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"sqlite error:  file is encrypted or is not a database")>0, "Log entries not as expected");
	}
	
	public function est_Zbase_restart_with_shard_missing(){ // SEG-10891 - missing shard or missing kvstore file is not detected
		// Zbase working with 3 disk and 3 kvstore after one of the disks go down.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, "testvalue"), "adding keys failed");		
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		
		$this->assertTrue(zbase_setup::memcached_service(TEST_HOST_1, "stop"));
		// Delete one of the databses
		$command_to_be_executed = "sudo rm -rf /data_2/zbase/*";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		$this->assertTrue(zbase_setup::memcached_service(TEST_HOST_1, "start"));
		exit; // zbase is operational with missing data set

	}

	public function est_Zbase_restart_with_one_file_in_shard_missing(){ // SEG-10891 - missing shard or missing kvstore file is not detected 
		// Zbase working with 3 disk and 3 kvstore after one of the disks go down.
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 100);
		$this->assertTrue(Data_generation::add_keys(500, 100, 1, "testvalue"), "adding keys failed");		
		$this->assertTrue(Utility::Check_keys_are_persisted(),"Failed persisiting keys");
		
		$this->assertTrue(zbase_setup::memcached_service(TEST_HOST_1, "stop"));
		// Delete one of the databses
		$command_to_be_executed = "sudo rm -rf /data_1/zbase/ep.db-0.sqlite*";
		remote_function::remote_execution(TEST_HOST_1,$command_to_be_executed);
		zbase_setup::clear_zbase_log_file(TEST_HOST_1);
		$this->assertTrue(zbase_setup::memcached_service(TEST_HOST_1, "start"));
		exit; // zbase is operational with missing data set

	}
	

}

class Multi_KV_Store_TestCase_Full extends Multi_KV_Store_TestCase{
	public function keyProvider() {
		return Utility::provideKeys();
	}

}

