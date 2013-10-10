<?php

abstract class Getl_TestCase extends ZStore_TestCase {

		// Append Prepend
		
	/**
     * @dataProvider simpleKeyValueFlagProvider	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Append($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// same client 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
   		$success = $instance->append($testKey, $testValue2);
   		$this->assertFalse($success, "Memcache::append (negative)");
   		
					// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
		
		// different client
		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
   		$success = $instance2->append($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::append (negative)");
   		
					// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");

		//release lock
		$instance->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider simpleKeyValueFlagProvider	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Prepend($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// same client 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
   		$success = $instance->prepend($testKey, $testValue2);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
   		
					// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
		
		// different client
		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
   		$success = $instance2->prepend($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
   		
					// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");

		//release lock
		$instance->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider simpleKeyValueFlagProvider	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Evict_Append($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// same client
   		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);	   		
   		
   		$success = $instance->append($testKey, $testValue2, 0);
   		$this->assertFalse($success, "Memcache::append (negative)");
   		
   		// validate value not appended 
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
		
		// different client
   		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);	   		
   		
   		$success = $instance2->append($testKey, $testValue2, 0);
   		$this->assertFalse($success, "Memcache::append (negative)");
   		
   		// validate value is not appended
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}


	/**
     * @dataProvider simpleKeyValueFlagProvider	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Evict_Prepend($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// same client
   		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);	   		
   		
   		$success = $instance->prepend($testKey, $testValue2, 0);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
   		
   		// validate value not prepended 
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
		
		// different client
   		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		Utility::EvictKeyFromMemory_Master_Server($testKey);	   		
		
   		$success = $instance2->prepend($testKey, $testValue2, 0);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
   		
   		// validate value is not prepended
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}		

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */		
	public function test_Getl_Update_Append($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue1);
   		$success = $instance2->append($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::append (positive)");
 
    		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1.$testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */		
	public function test_Getl_Update_Prepend($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue1);
   		$success = $instance2->prepend($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::prepend (positive)");
 
    		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2.$testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_Unlock_Append($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->unlock($testKey);
   		$success = $instance2->append($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::append (positive)");
		
		   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1.$testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
 
	}

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */		
	public function test_Unlock_Prepend($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->unlock($testKey);
   		$success = $instance2->prepend($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::prepend (positive)");
		
		   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2.$testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
 
	}

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_Getl_Timeout_Append($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
   		$success = $instance2->append($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
 
     		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1.$testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Timeout_Prepend($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
   		$success = $instance2->prepend($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
 
     		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2.$testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
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
