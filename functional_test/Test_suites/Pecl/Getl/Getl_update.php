<?php

abstract class Getl_TestCase extends ZStore_TestCase {

	//***** getl and update to check lock is released ***** ////
		
		
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Set_Update($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue, $testFlags);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");

	} 
	
		/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Update_Getl($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
		$instance->set($testKey, $testValue, $testFlags);
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		// release lock
		$instance2->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Getl_Update_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue);
   		$success = $instance2->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		 // verify key is not present	
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
	} 
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Update_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue1);
   		$success = $instance2->replace($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
 
    		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Getl_Update($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		
   		// positive add test 
   		$instance->add($testKey, $testValue1, $testFlags);
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
		$instance->set($testKey, $testValue, $testFlags);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
	}
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Getl_Increment_Update($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
		
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue1);
   		$returnValue = $instance2->increment($testKey, $testValue1);
   		$this->assertEquals($returnValue, 2 * $testValue1,  "Memcache::increment (positive)");
	
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Getl_Decrement_Update($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
   		
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue1 * 2);
   		$returnValue = $instance2->decrement($testKey, $testValue1);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::decrement (positive)");
		
	}
	
		/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_SetTTL_Update($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testTTL = 3;
		$testValue1 = serialize(array($testValue));
		
		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		$instance->set($testKey, $testValue1, $testFlags);
		$success = $instance2->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
					// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		sleep($testTTL + 1);
   		
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
