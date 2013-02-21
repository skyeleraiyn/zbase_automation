<?php

/* This suite is supported for CentOS 6*/

abstract class Multi_KV_Store_Config_Param_TestCase extends ZStore_TestCase {

	public function test_SingleKVStore_DefaultParam(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		// Membase initialization with only 1 kv store and all default parameters
		$status = membase_setup::reset_membase_servers(array(TEST_HOST_1));
		$this->assertTrue($status,"Failed to start membase");
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"num_kvstores");
		$this->assertEquals($stats["num_kvstores"], 1, "No of kv stores not equal to expected");
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["kvstore1"]["db_shards"], 9, "No of db_shards not equal to expected");
		$this->assertEquals($stats["kvstore1"]["db_shard1"], "$database_prefix/membase/ep.db-1.sqlite","db_shard name not as expected");
	}

	public function test_MultipleKVStores(){
		//Membase intialization with 10 kv stores
		//Copy the kvstore config files to master
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_10", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		// create folders from 4 to 10
		$command_to_be_executed = "for i in {4..10} ; do sudo mkdir -p $database_prefix\$i/membase ; done ; sudo chown -R nobody /$database_prefix*/membase";
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$status = membase_setup::memcached_service(TEST_HOST_1, "restart");
		$this->assertTrue($status,"Failed to start membase");
		// check stats
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"num_kvstores"); 
		$this->assertEquals($stats["num_kvstores"], 10 ,"No of kvstores not equal to expected");
		$command_to_be_executed = "for i in {4..10} ; do sudo rm -rf /$database_prefix\$i/ ; done";
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		
	}

	public function test_Conflicting_DBName(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		//Membase initialization with conflicting dbname
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		//Change dbname from "dbname" : "/data_1/ep.db" to "dbname" : "/data_1/ep.db-0.sqlite"
		membase_setup::edit_multikv_config_file(TEST_HOST_1 ,"\"dbname\" : \"\/data_1\/membase\/ep.db\"", "\"dbname\" : \"\/data_1\/membase\/ep.db-0.sqlite\"", 'replace');
		remote_function::remote_execution(TEST_HOST_1, "sudo /etc/init.d/memcached restart");
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("This dbname will repeat when sharded based on pattern:", $logs, "Error in conflicting dbname not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs ,"Error instance not logged");
	}

	public function test_MoreThanOne_DBName(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		// Membase initialization with more that one dbname
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"dbname\" : \"\/data_1\/membase\/ep.db\"" , "\"dbname\" : [\"$\/data_1\/membase\/ep.db\",\"$\/data_1\/membase\/ep2.db\"]" ,'replace');
		remote_function::remote_execution(TEST_HOST_1, "sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("Parameter dbname must have a string value", $logs, "Error in dbname parameter not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs, "Error instance not logged");	

	}

	public function test_ShardPattern_Defined(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		//Membase initialization with shardpattern explicitly specified in the config
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"kvstore1\" : {", "\"shardpattern\" : \"%d/%i.sqlite\"," , 'append');
		remote_function::remote_execution(TEST_HOST_1, "sudo /etc/init.d/memcached restart");
		//Check stats	
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");		
		$this->assertEquals($stats["kvstore1"]["db_shard0"], "$database_prefix/membase/0.sqlite", "Shard names not according to the pattern specified");	

	}

	public function test_No_DBName(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		//Membase initialization with no dbnames specified in the config file
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"dbname\" :","k", 'delete');
		$status = membase_setup::memcached_service(TEST_HOST_1, "restart");
		$this->assertTrue($status,"Failed to start membase");
		//Check stats            
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["kvstore1"]["dbname"], "/tmp/test.db", "db file not as expected");
		$this->assertEquals($stats["kvstore1"]["db_shard0"], "$database_prefix/membase/ep.db-0.sqlite", "Shard names not the same as expected");
	
	}

	public function test_10_kvstores_10_DBShards(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		// create folders from 4 to 10
		$command_to_be_executed = "for i in {4..10} ; do sudo mkdir -p /data_\$i/membase ; done ; sudo chown -R nobody /data_*/membase";
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		//Membase initialization with one data_dbname and db_shards value set to 10.
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_10", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"db_shards\" : 9" , "\"db_shards\" : 10" ,'replace');
		$status = membase_setup::memcached_service(TEST_HOST_1, "restart");
		$this->assertTrue($status,"Failed to start membase");
		//Check stats            
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["kvstore1"]["db_shards"], 10, "No of db_shards not equal to specified");
		$this->assertEquals($stats["kvstore1"]["db_shard9"], "$database_prefix/membase/ep.db-9.sqlite", "Shard names not the same as expected");
		$command_to_be_executed = "for i in {4..10} ; do sudo rm -rf /data_\$i/ ; done";
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);	
	
	}

	public function test_GreaterThan10_DBShards(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		//Membase initialization with db_shards value greater than 10 and one data_dbname.
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"db_shards\" : 9" , "\"db_shards\" : 15" ,'replace');
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("sqlite error:  too many attached databases - max 10", $logs , "Error in sqlite logic not caught");
		$this->assertContains("Failed to create database: unknown database", $logs, "Error not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs, "Error instance not logged");

	}
	
	public function test_2_data_DBNames_10_DBShards(){
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		//Membase initialization with two data_dbnames and dbshards set to 10
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"data_dbnames\"\ :\ \[","\"$database_prefix\/membase\/ep2.db\",", 'append');
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"db_shards\" : 9" , "\"db_shards\" : 10" ,'replace');
		$status = membase_setup::memcached_service(TEST_HOST_1, "restart");
		$this->assertTrue($status,"Failed to start membase");
		//Check stats            
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["kvstore1"]["data_dbs"], 2, "data_db not equal to expected number");
		$this->assertEquals($stats["kvstore1"]["data_dbname0"], "$database_prefix/membase/ep2.db", "data_db names not as expected");
		$this->assertEquals($stats["kvstore1"]["db_shard0"], "$database_prefix/membase/ep2.db-0.sqlite", "Shard names not the same as expected");
		$this->assertEquals($stats["kvstore1"]["db_shard9"], "$database_prefix/membase/ep.db-9.sqlite","Shard names not the same as expected");

	}

	public function test_1_DBName_1_data_DBName_Invalid_ShardPattern(){	
		//Membase initialization with one dbname, one data_dbname and default db_shards value but invalid shardpattern.
		$database_prefix = (CENTOS_VERSION==5)?"/db":"/data_1";
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"db_shards\" : 9" , "\"db_shards\" : 4" ,'replace');
		membase_setup::edit_multikv_config_file(TEST_HOST_1 , "\"kvstore1\" : {" ,"\"shardpattern\" : \"%d/%p/%g-%i.sqlite\"," ,  'append');
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		//Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("sqlite error:  unable to open database:", $logs, "Error in invalid shard pattern not caught");
		$this->assertContains("Failed to create database: unknown database" , $logs, "Error not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs, "Error instance not logged");

	}

	public function test_Invalid_Config_File(){
		//starting membase with invalid config file
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		$command_to_be_executed = "sudo sed -i '3 i\ random_text' ".MEMCACHED_MULTIKV_CONFIG;
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		//Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("Parse error in JSON file", $logs ,"Parse error in memcached config file not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs ,"Error instance not logged");

	}

	public function test_No_Read_Permissions(){
		//Starting membase with kvstore config file that does have have read permissions
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		$command_to_be_executed = "sudo chmod 000 ".MEMCACHED_MULTIKV_CONFIG;
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
			// reset it back to orginal permissions
		$command_to_be_executed = "sudo chmod 775 ".MEMCACHED_MULTIKV_CONFIG;
		// Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("Parse error in JSON file", $logs ,"Error in read permissions to file not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs ,"Error instance not logged");
		
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
	}

	public function test_No_Permissions_to_data_dbname_path(){
		//Starting membase when data_dbnames path does not have permissions
		$db_path=unserialize(MEMBASE_DATABASE_PATH);
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		// Copy the kvstore config files to master
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig_multikv_store", MEMCACHED_SYSCONFIG, False, True, True);
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_3", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		$command_to_be_executed = "sudo chmod -R 000 ".$db_path[0];
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// reset it back to original value
		$command_to_be_executed = "sudo chmod -R 755 ".$db_path[0];
		// Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("Failed to create database: Error initializing sqlite3", $logs ,"Error in read permissions to data path not caught");
		$this->assertContains("No access to \"".$db_path[0]."ep.db\".", $logs,"Error in read permissions to data path not caught");
		
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
	}

	public function test_Old_Config_File(){
		// Starting membase with older config file
		membase_setup::clear_membase_log_file(TEST_HOST_1);
		
		// keep a copy of current sysconfig file 
		file_function::keep_copy_original_file(TEST_HOST_1, MEMCACHED_SYSCONFIG, "n");
		// Copy the older config file to master
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// reset the original sysconfig file
		remote_function::remote_execution(TEST_HOST_1 ,"sudo cp ".MEMCACHED_SYSCONFIG.".org ".MEMCACHED_SYSCONFIG);
		// Compare the entries in the log file
		$logs = file_function::query_log_files(MEMBASE_LOG_FILE, NULL, TEST_HOST_1);
		$this->assertContains("Unsupported key: <dbname>", $logs, "Error in config file not caught");
		$this->assertContains("Unsupported key: <initfile>", $logs, "Error in config file not caught");
		$this->assertContains("Failed to parse configuration. The following parameters are deprecated. Please use json based kvstore configuration to setup the database (refer docs): dbname, shardpattern, initfile, postInitfile, db_shards, db_strategy", $logs, "Error in config file not caught");
		$this->assertContains("Failed to initialize instance. Error code: 255", $logs,"Error instance not logged");

	}

	public function test_Missing_KV_Store_Parameter(){
		// Starting membase with new config file which is missing the kvstore_config_file parameter
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_1", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		membase_setup::edit_sysconfig_file(TEST_HOST_1 , ";kvstore_config_file=\/etc\/sysconfig\/memcached_multikvstore_config","");	
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// check stats
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore");
		$this->assertEquals($stats["kvstore"]["dbname"], "/tmp/test.db", "DB name not as expected");
		$this->assertEquals($stats["kvstore"]["data_dbs"], 0, "No of data_dbs not equal to expected");
		$this->assertEquals($stats["kvstore"]["db_shard2"], "/tmp/test.db-2.sqlite", "Shard name not as expected");
			// cleanup /tmp/test.db*
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached stop");
		$temp_file = $stats["kvstore"]["dbname"];
		remote_function::remote_execution(TEST_HOST_1 ,"sudo rm -rf $temp_file*");
	}

}

class Multi_KV_Store_Config_Param_TestCase_Full extends Multi_KV_Store_Config_Param_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}	


