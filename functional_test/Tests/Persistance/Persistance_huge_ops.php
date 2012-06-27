<?php
	
abstract class Persistance_Huge_Ops_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider simpleKeyValueFlagProvider
	*/
	public function test_Pause_Persistance($testKey, $testValue, $testFlags) {
		$instance = $this->sharedFixture;
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "stop");
		$flusher_state = stats_functions::get_stat(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("paused", $flusher_state, "Flusher State");
				
		$instance->set($testKey, $testValue, $testFlags);
		$this->assertFalse(Utility::Check_keys_are_persisted(3));
		
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");
		$flusher_state = stats_functions::get_stat(TEST_HOST_1, "ep_flusher_state");
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


	public function test_Verify_Checkpoint_with_Persistance_Paused() {
		$instance = $this->sharedFixture;
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "stop");
		stats_functions::get_checkpoint_stats(TEST_HOST_1);
		
		$flusher_state = stats_functions::get_stat(TEST_HOST_1, "ep_flusher_state");
		$this->assertEquals("paused", $flusher_state, "Flusher State");
				
		$instance->set($testKey, $testValue, $testFlags);
		$this->assertFalse(Utility::Check_keys_are_persisted(3));
		
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");
		$flusher_state = stats_functions::get_stat(TEST_HOST_1, "ep_flusher_state");
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


class Persistance_TestCase_Huge_Ops_Quick extends Persistance_Huge_Ops_TestCase
{
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	public function simpleKeyValueFlagProvider() {
		return array(array("test_key", "test_value", 0));
	}	
}

?>

