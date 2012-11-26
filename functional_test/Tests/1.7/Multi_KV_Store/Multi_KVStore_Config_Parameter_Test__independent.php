<?php

abstract class Multi_KV_Store_Config_Param_TestCase extends ZStore_TestCase {

	public function test_SingleKVStore_DefaultParam(){
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		// Membase initialization with only 1 kv store and all default parameters
		$status = membase_function::reset_membase_servers(array(TEST_HOST_1));
		$this->assertTrue($status,"Failed to start membase");
		sleep(5);
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"num_kvstores");
		$this->assertEquals($stats, 1, "No of kv stores not equal to expected");
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["db_shards"], 4, "No of db_shards not equal to expected");
		$this->assertEquals($stats["db_shard1"], $db_base."/membase/ep.db-1.sqlite","db_shard name not as expected");
	}

	public function test_FourKVStores(){//Skip on Centos 5
		//Membase intialization with 3 kv stores
		//Copy the kvstore config files to master
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_3", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		$status = membase_function::restart_memcached_service(TEST_HOST_1);
		$this->assertTrue($status,"Failed to start membase");
		// check stats
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"num_kvstores"); 
		$this->assertEquals($stats, 3 ,"No of kvstores not equal to expected");
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore3");
		$this->assertEquals($stats["db_shard3"],"/data_3/membase/ep.db-3.sqlite","db_shard name not as expected");
		
	}

	public function test_Conflicting_DBName(){
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		//Membase initialization with conflicting dbname
		membase_function::clear_membase_log_file(TEST_HOST_1);
		//Change dbname from "dbname" : "/data_1/ep.db" to "dbname" : "/data_1/ep.db-0.sqlite"
		membase_function::edit_multikv_config_file(TEST_HOST_1 ,"\"dbname\" : \"$db_base\/membase\/ep.db\"","\"dbname\" : \"$db_base\/membase\/ep.db-0.sqlite\"", 'replace');
		remote_function::remote_execution(TEST_HOST_1, "sudo /etc/init.d/memcached restart");
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"This dbname will repeat when sharded based on pattern: $db_base/membase/ep.db-0.sqlite")>0 ,"Error in conflicting dbname not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0 ,"Error instance not logged");
	}

	public function test_MoreThanOne_DBName(){
		// Membase initialization with more that one dbname
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		membase_function::clear_membase_log_file(TEST_HOST_1);
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "\"dbname\" : \"$db_base\/membase\/ep.db\"" , "\"dbname\" : [\"$db_base\/membase\/ep.db\",\"$db_base\/membase\/ep2.db\"]" ,'replace');
		remote_function::remote_execution(TEST_HOST_1, "sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"Parameter dbname must have a string value")>0,"Error in dbname parameter not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0 ,"Error instance not logged");	

	}

	public function test_ShardPattern_Defined(){
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		//Membase initialization with shardpattern explicitly specified in the config
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "\"kvstore1\" : {", "\"shardpattern\" : \"%d/%i.sqlite\"," , 'append');
		remote_function::remote_execution(TEST_HOST_1, "sudo /etc/init.d/memcached restart");
		//Check stats	
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");		
		$this->assertEquals($stats["db_shard0"], "$db_base/membase/0.sqlite", "Shard names not according to the pattern specified");	

	}

	public function test_No_DBName(){
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		//Membase initialization with no dbnames specified in the config file
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "\"dbname\" :","k", 'delete');
		$status = membase_function::restart_memcached_service(TEST_HOST_1);
		$this->assertTrue($status,"Failed to start membase");
		//Check stats            
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["dbname"], "/tmp/test.db", "db file not as expected");
		$this->assertEquals($stats["db_shard0"], "$db_base/membase/ep.db-0.sqlite", "Shard names not the same as expected");
	
	}

	public function test_1_data_DBName_10_DBShards(){
		//Membase initialization with one data_dbname and db_shards value set to 10.
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "]",",\"db_shards\" : 10", 'append');
		$status = membase_function::restart_memcached_service(TEST_HOST_1);
		$this->assertTrue($status,"Failed to start membase");
		//Check stats            
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["db_shards"], 10, "No of db_shards not equal to specified");
		$this->assertEquals($stats["db_shard9"], "$db_base/membase/ep.db-9.sqlite", "Shard names not the same as expected");
	
	}

	public function test_2_data_DBNames_10_DBShards(){
		//Membase initialization with two data_dbnames and dbshards set to 10
		$db_base = (CENTOS_VERSION==5)?"/db":"/data_1";
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "\"data_dbnames\"\ :\ \[","\"$db_base\/membase\/ep2.db\",", 'append');
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "]",",\"db_shards\" : 10", 'append');
		$status = membase_function::restart_memcached_service(TEST_HOST_1);
		$this->assertTrue($status,"Failed to start membase");
		//Check stats            
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore1");
		$this->assertEquals($stats["data_dbs"], 2, "data_dbs not equal to expected number");
		$this->assertEquals($stats["data_dbname0"], "$db_base/membase/ep2.db", "data_db names not as expected");
		$this->assertEquals($stats["db_shard0"], "$db_base/membase/ep2.db-0.sqlite", "Shard names not the same as expected");
		$this->assertEquals($stats["db_shard9"], "$db_base/membase/ep.db-9.sqlite","Shard names not the same as expected");

	}

	public function test_1_data_DBName_GreaterThan10_DBShards(){
		//Membase initialization with db_shards value greater than 10 and one data_dbname.
		membase_function::clear_membase_log_file(TEST_HOST_1);
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "]",",\"db_shards\" : 15", 'append');
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"sqlite error:  SQL logic error or missing database")>0 ,"Error in sqlite logic not caught");
		$this->assertTrue(strpos($logs , "Failed to create database: unknown database")>0,"Error not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0,"Error instance not logged");

	}

	public function test_1_DBName_1_data_DBName_Invalid_ShardPattern(){
		//Membase initialization with one dbname, one data_dbname and default db_shards value but invalid shardpattern.
		membase_function::clear_membase_log_file(TEST_HOST_1);
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "]",",\"db_shards\" : 4" , 'append' );
		membase_function::edit_multikv_config_file(TEST_HOST_1 , "\"kvstore1\" : {" ,"\"shardpattern\" : \"%d/%p/%g-%i.sqlite\"," ,  'append');
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		//Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"sqlite error:  unable to open database file")>0,"Error in invalid shard pattern not caught");
		$this->assertTrue(strpos($logs , "Failed to create database: unknown database")>0 ,"Error not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0,"Error instance not logged");

	}

	public function test_Invalid_Config_File(){
		//starting membase with invalid config file
		membase_function::clear_membase_log_file(TEST_HOST_1);
		$command_to_be_executed = "sudo sed -i '3 i\ random_text' ".MEMCACHED_MULTIKV_CONFIG;
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		//Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"Parse error in JSON file")>0 ,"Parse error in memcached config file not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0 ,"Error instance not logged");

	}

	public function test_No_Read_Permissions(){
		//Starting membase with kvstore config file that does have have read permissions
		membase_function::clear_membase_log_file(TEST_HOST_1);
		$command_to_be_executed = "sudo chmod 000 ".MEMCACHED_MULTIKV_CONFIG;
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"Parse error in JSON file")>0 ,"Error in read permissions to file not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0 ,"Error instance not logged");
		$command_to_be_executed = "sudo chmod 775 ".MEMCACHED_MULTIKV_CONFIG;
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
	}

	public function test_No_Permissions_to_data_dbname_path(){//Skip on Centos 5
		//Starting membase when data_dbnames path does not have permissions
		$db_path=unserialize(MEMBASE_DATABASE_PATH);
		membase_function::clear_membase_log_file(TEST_HOST_1);
		// Copy the kvstore config files to master
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig_multikv_store", MEMCACHED_SYSCONFIG, False, True, True);
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_multikvstore_config_3", MEMCACHED_MULTIKV_CONFIG, False, True, True);
		$command_to_be_executed = "sudo chmod -R 000 ".$db_path[0];
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"Failed to create database: Error initializing sqlite3")>0 ,"Error in read permissions to data path not caught");
		$this->assertTrue(strpos($logs,"No access to \"".$db_path[0]."ep.db\".")>0,"Error in read permissions to data path not caught");
		$command_to_be_executed = "sudo chmod -R 777 ".$db_path[0];
		remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
	}

	public function test_Old_Config_File(){
		// Starting membase with older config file
		membase_function::clear_membase_log_file(TEST_HOST_1);
		// Copy the older config file to master
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// Compare the entries in the log file
		$command_to_be_executed = "cat ".MEMBASE_LOG_FILE;
		$logs = remote_function::remote_execution(TEST_HOST_1, $command_to_be_executed);
		$this->assertTrue(strpos($logs,"Unsupported key: <dbname>")>0,"Error in config file not caught");
		$this->assertTrue(strpos($logs,"Unsupported key: <initfile>")>0,"Error in config file not caught");
		$this->assertTrue(strpos($logs,"Failed to parse configuration. The following parameters are deprecated. Please use json based kvstore configuration to setup the database (refer docs): dbname, shardpattern, initfile, postInitfile, db_shards, db_strategy")>0,"Error in config file not caught");
		$this->assertTrue(strpos($logs,"Failed to initialize instance. Error code: 255")>0,"Error instance not logged");
		remote_function::remote_file_copy(TEST_HOST_1, BASE_FILES_PATH."memcached_sysconfig_multikv_store", MEMCACHED_SYSCONFIG, False, True, True);

	}

	public function test_Missing_KV_Store_Parameter(){
		// Starting membase with new config file which is missing the kvstore_config_file parameter
		membase_function::edit_sysconfig_file(TEST_HOST_1 , ";kvstore_config_file=/etc/sysconfig/memcached_multikvstore_config","",'replace');	
		remote_function::remote_execution(TEST_HOST_1 ,"sudo /etc/init.d/memcached restart");
		// check stats
		$stats = stats_functions::get_kvstore_stats(TEST_HOST_1,"kvstore");
		$this->assertEquals($stats["dbname"], "/tmp/test.db", "DB name not as expected");
		$this->assertEquals($stats["data_dbs"], 0, "No of data_dbs not equal to expected");
		$this->assertEquals($stats["db_shard2"], "/tmp/test.db-2.sqlite", "Shard name not as expected");
	}

}

class Multi_KV_Store_Config_Param_TestCase_Full extends Multi_KV_Store_Config_Param_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}	


