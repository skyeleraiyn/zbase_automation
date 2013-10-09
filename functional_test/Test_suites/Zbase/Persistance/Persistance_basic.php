<?php
	
abstract class Persistance_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Set_Get($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}

	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Replace($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$instance->replace($testKey, strrev($testValue), $testFlags);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals(strrev($testValue), $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}	

	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Delete($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		Utility::Check_keys_are_persisted();
		$ep_total_persisted_count = Utility::Get_ep_total_persisted(TEST_HOST_1);
		$instance->delete($testKey);
		Utility::Get_ep_total_persisted(TEST_HOST_1, $ep_total_persisted_count);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($returnValue, false, "Memcache::get (positive)");

	}	
	
	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Append($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$instance->append($testKey, strrev($testValue), $testFlags);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue.strrev($testValue), $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}
		
	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Prepend($testKey, $testValue, $testFlags) {
	
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$instance->prepend($testKey, strrev($testValue), $testFlags);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals(strrev($testValue).$testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}	

	/**
	* @dataProvider simpleKeyNumericValueFlagProvider
	*/
	public function test_Increment($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$instance->increment($testKey, 5);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue + 5, $returnValue, "Memcache::get (value)");

	}	

	/**
	* @dataProvider simpleKeyNumericValueFlagProvider
	*/
	public function test_Decrement($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$instance->decrement($testKey, 5);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue - 5, $returnValue, "Memcache::get (value)");

	}
	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Expiry($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$expiry_time = 3;
		$instance->set($testKey, $testValue, $testFlags, $expiry_time );
		Utility::Check_keys_are_persisted();
		sleep($expiry_time);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($returnValue, false, "Memcache::get (positive)");

	}	
	
	
	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Evict_Modify($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, "testvalue", 0);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		$instance->replace($testKey, $testValue, $testFlags);
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}	
		
	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Pause_Persistance($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "stop");
		$flusher_state = stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("paused", $flusher_state, "Flusher State");
		
		$instance->set($testKey, $testValue, $testFlags);
		$this->assertFalse(Utility::Check_keys_are_persisted(TEST_HOST_1, False, 3));
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");
		$flusher_state = stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("running", $flusher_state, "Flusher State");
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}

	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/	
	public function test_Verify_Checkpoint_with_Persistance_Paused($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "stop");
		
		$flusher_state = stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("paused", $flusher_state, "Flusher State");
				
		$instance->set($testKey, $testValue, $testFlags);
		$this->assertFalse(Utility::Check_keys_are_persisted(TEST_HOST_1, False, 3));
		
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");
		$flusher_state = stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("running", $flusher_state, "Flusher State");
		$this->assertTrue(zbase_function::restart_zbase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}	


	// set 1000 keys with negative expiry time. Check for duplicate keys
	public function test_verify_duplicate_keys_negative_expiry_time(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		$instance = Connection::getMaster();
		$pid_arr = array();
		$time_now = time();
		for($ithread=0; $ithread<20 ; $ithread++){
			$pid = pcntl_fork();
			if ($pid == 0){
				$start_time = time();
				while(1){
					for($ikey=0 ; $ikey<1000000 ; $ikey++){
						@$instance->set("test_key_negative_expiry_$ikey", "testvalue", 0, $time_now);	// When multiple threads are spwaned, chances are high that some requests 
						@$instance->set("test_key_without_expiry_$ikey", "testvalue");					// will not be served resulting in server error, which in turn causes the 
						@$instance->set("test_key_small_expiry_$ikey", "testvalue", 0, rand(1, 2));		// phpunit framework to trigger an error. Either have expected exception annotation
						@$instance->set("test_key_large_expiry_$ikey", "testvalue", 0, rand(5, 10));	// or put @ before the request to capture the std error 
						@$instance->set("test_key_same_key_mutation", "testvalue", 0);
						if((time() - $start_time) > 300) exit;
					}
				}				
				exit;
			} else{
				$pid_arr[] = $pid;
			}
		}
		
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
		}
		zbase_function::restart_zbase_after_persistance();
		$count = trim(remote_function::remote_execution(TEST_HOST_1, " grep -i -c 'Duplicate key' ".ZBASE_LOG_FILE));
		$this->assertEquals(0, $count , "Duplicate keys during warmup");
	}
	
	// set and delete multiple times
	public function test_set_and_delete(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		
		$instance = Connection::getMaster();
		$pid_arr = array();
		$start_time = time();
		for($ithread=0; $ithread<20 ; $ithread++){
			$pid = pcntl_fork();
			if ($pid == 0){
				while(1){
					$ikey = rand(1, 10000);
					@$instance->set("test_key_delete_$ikey", "testvalue");
					usleep(rand(0,1000));
					@$instance->delete("test_key_delete_$ikey");
					if((time() - $start_time) > 300) break;
				}
				exit;
			} else {
				$pid_arr[] = $pid;
			}
		}
		
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
		}			
		
		zbase_function::restart_zbase_after_persistance();
		$count = trim(remote_function::remote_execution(TEST_HOST_1, " grep -i -c 'Duplicate key' ".ZBASE_LOG_FILE));
		$this->assertEquals(0, $count , "Duplicate keys during warmup");
	}
	
}


class Persistance_TestCase_Quick extends Persistance_TestCase {
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	public function simpleKeyValueFlagProvider() {
		return array(array(uniqid('testkey_'), uniqid('testvalue_'), 0));
	}	
	public function keyProvider() {
		return array(array(uniqid('testkey_')));
	}
	public function simpleKeyNumericValueFlagProvider() {
		return array(array(uniqid('testkey_'), 8, 0));
	}	
}

?>

