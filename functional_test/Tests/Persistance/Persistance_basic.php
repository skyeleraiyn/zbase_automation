<?php
	
abstract class Persistance_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Set_Get($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$ep_total_persisted_count = Utility::Get_ep_total_persisted();
		$instance->delete($testKey);
		Utility::Get_ep_total_persisted($ep_total_persisted_count);
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertFalse(Utility::Check_keys_are_persisted(3));
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");
		$flusher_state = stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("running", $flusher_state, "Flusher State");
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
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
		$this->assertFalse(Utility::Check_keys_are_persisted(3));
		
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");
		$flusher_state = stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("running", $flusher_state, "Flusher State");
		$this->assertTrue(Functional_test::restart_membase_after_persistance(), "Failed persisting the data");
   		
			// setup a new connection
		$instance = Connection::getMaster();
		
			// validate persisted value after restart
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

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

