<?php

abstract class Getl_TestCase extends ZStore_TestCase {

	
			//***** Evict and getl ***** ////
		

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Evict_Getl($testKey, $testValue, $testFlags) {
		$instance = $this->sharedFixture;
		
		$instance->set($testKey, $testValue, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);

   		$returnFlags = null;
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Evict($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);

   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Evict_Getl($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);

   		$returnFlags = null;
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}
	
	
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Evict_Set($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		// set from same client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");

		// set from different client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertFalse($success, "Memcache::set (negative)");

	} 

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Evict_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
   		
   		// get from same client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		   		
   		// get from different client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		$returnValue = $instance2->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}  

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Evict_Get2($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		// same client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   
   		$returnFlags = null;
   		$returnValue = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get2 (flag)");

		// different client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   
   		$returnFlags = null;
   		$returnValue = null;
   		$success = $instance2->get2($testKey, $returnValue, $returnFlags);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get2 (flag)");
	}	

		/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Evict_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		// same client
		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		$success = $instance->delete($testKey);
		$this->assertFalse($success, "Memcache::delete (negative)");  		
   		 // verify key is present	
   		$returnValue = $instance->get($testKey);
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	
		// different client
		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		$success = $instance2->delete($testKey);
		$this->assertFalse($success, "Memcache::delete (negative)");  		
   		 // verify key is present	
   		$returnValue = $instance->get($testKey);
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	} 
	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Evict_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		// same client
   		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);	
   		// positive replace test
   		$success = $instance->replace($testKey, $testValue2, 0);
   		$this->assertFalse($success, "Memcache::replace (negative)");
   		
   		// validate value not replaced 
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
		
		// different client
   		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		// positive replace test
   		$success = $instance2->replace($testKey, $testValue2, 0);
   		$this->assertFalse($success, "Memcache::replace (negative)");
   		
   		// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}


		/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Evict_Increment($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
		
   		// same client
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		$returnValue = $instance->increment($testKey, $testValue1);
   		$this->assertFalse($returnValue, "Memcache::increment (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::increment (negative)");
		
		// different client
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		$returnValue = $instance2->increment($testKey, $testValue1);
		$this->assertFalse($returnValue, "Memcache::increment (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);		
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::increment (negative)");
		
	} 
	
	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Evict_Decrement($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
   		
   		// same client
   		$instance->set($testKey, $testValue1 * 2);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
   		$returnValue = $instance->decrement($testKey, $testValue1);
   		$this->assertFalse($returnValue, "Memcache::decrement (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);		
   		$this->assertEquals($returnValue, $testValue1 * 2,  "Memcache::decrement (negative)");
		
		// different client
   		$instance->set($testKey, $testValue1 * 2);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		$returnValue = $instance2->decrement($testKey, $testValue1);
		$this->assertFalse($returnValue, "Memcache::decrement (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);		
   		$this->assertEquals($returnValue, $testValue1 * 2,  "Memcache::decrement (negative)");
	}
	
		/**
     * @dataProvider keyValueFlagsProvider
     */

	public function test_SetTTLExpired_Getl_Evict($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 3;
		
		// positive set test
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey, $testTTL);	   			
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}
		
	
}


class Getl_TestCase_Full extends Getl_TestCase
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
	public function simpleKeyValueFlagProvider() {
		return array(array(uniqid('key_'), uniqid('value_'), 0));
	}	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}

?>