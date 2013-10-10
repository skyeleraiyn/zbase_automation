<?php

abstract class Disk_Greater_than_Memory_TestCase extends ZStore_TestCase {
	
	
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Set_Evict_Set($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// positive set test
		$instance->set($testKey, $testValue, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
	} 
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$instance->set($testKey, $testValue, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Evict_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;

		// set reference value
		$instance->set($testKey, $testValue);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		// cleanup (this shouldn't be here, but we need a full zbase flush to get rid of this)
   		$success = $instance->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		   		
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	} 
	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
		Utility::EvictKeyFromMemory_Master_Server($testKey);   		
   		// positive replace test
   		$success = $instance->replace($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}
	

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_AddExistingValue($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		$instance->set($testKey, $testValue1, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// positive add test
		$success = $instance->add($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::replace (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Evict_Increment($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		
   		$testValue1 = strlen($testValue);
		
   		// set initial value
   		$instance->set($testKey, $testValue1);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// positive increment test
   		$returnValue = $instance->increment($testKey, $testValue1);
   		$this->assertEquals($returnValue, 2 * $testValue1,  "Memcache::increment (positive)");
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Evict_Decrement($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		
   		$testValue1 = strlen($testValue);
   		
   		// set initial value
   		$instance->set($testKey, $testValue1 * 2);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// positive decrement test
   		$returnValue = $instance->decrement($testKey, $testValue1);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::decrement (positive)");
	}

	 /**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_CAS($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnCAS = null;
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS);
   		$this->assertTrue($success, "Memcache::casEvict (positive)");
   		
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_SetTTL($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 30;
		
		$instance->set($testKey, $testValue, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_ReplaceTTL($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testTTL = 30;
		
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// positive add test 
   		$success = $instance->replace($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	}
 
	/**
     * @dataProvider ArrayKeyArrayValueFlags
     */
	public function test_Evict_Negative_AddTTL($atestKey, $atestValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 30;

		$instance->add($atestKey[0], $atestValue[0], $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($atestKey[0]);
		
		// positive set test
		$success = $instance->set($atestKey[0], $atestValue[1], $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($atestKey[0], $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($atestValue[1], $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		Utility::EvictKeyFromMemory_Master_Server($atestKey[0]);
		
		// Add should not succed here as the key exists
		$success = $instance->add($atestKey[0], $atestValue[2], $testFlags);
		$this->assertFalse($success, "Memcache::add (negative)");
   		
   		// verify value is not changed
   		$returnValue = $instance->get($atestKey[0], $returnFlags);
   		$this->assertEquals($atestValue[1], $returnValue, "Memcache::get (value)");	
	} 
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_SetTTLExpired($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 1;
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
   		sleep($testTTL + 1);
   		
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_AddTTLExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testTTL = 3;
		
   		// positive add test 
		$instance->add($testKey, $testValue, $testFlags, $testTTL);
		Utility::EvictKeyFromMemory_Master_Server($testKey, $testTTL + 1);   	
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_ReplaceTTLExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testTTL = 1;
		
		$instance->set($testKey, $testValue, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// positive add test 
   		$success = $instance->replace($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		sleep($testTTL+1);
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

	// Append Prepend
	
	public function test_Evict_Append() {

		$testKey = "test_key";
		$testValue = "test_value";
		$testFlags = 0;
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		   		
   		// positive append test
		$success = $instance->append($testKey, "testValue");
   		$this->assertTrue($success, "Memcache::append (positive)");
		
		   // validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue."testValue", $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
	public function test_Evict_Prepend() {

		$testKey = "test_key";
		$testValue = "test_value";
		$testFlags = 0;
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		   		
   		// positive append test
		$success = $instance->prepend($testKey, "testValue");
   		$this->assertTrue($success, "Memcache::prepend (positive)");
		
		   // validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals("testValue".$testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	
	
}


class Disk_Greater_than_Memory_TestCase_Full extends Disk_Greater_than_Memory_TestCase
{
	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
	
	public function ArrayKeyArrayValueFlags() {
		return Data_generation::provideArrayKeyArrayValueFlags();
	}	
}

?>
