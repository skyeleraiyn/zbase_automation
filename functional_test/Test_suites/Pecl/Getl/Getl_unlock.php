<?php

abstract class Getl_TestCase extends ZStore_TestCase {

		//**** unlock ***** //
		
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$success = $instance->unlock($testKey);
		$this->assertTrue($success, "Memcache::unlockock (positive)");

	} 		

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_After_Unlock($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$instance->unlock($testKey);
		
		// same client
   		$returnValue = $instance->getl($testKey);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
		$instance->unlock($testKey);
		
		// different client
   		$returnValue = $instance2->getl($testKey);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
		$success = $instance2->unlock($testKey);
		$this->assertTrue($success, "Memcache::unlockock (positive)");

	} 
	
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock_Negative($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$success = $instance->unlock($testKey);
		$this->assertFalse($success, "Memcache::unlockock (negative)");
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$instance->unlock($testKey);
		$success = $instance->unlock($testKey);
		$this->assertFalse($success, "Memcache::unlockock (negative)");

	} 	

 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock_After_Timeout($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
		$success = $instance->unlock($testKey);
		$this->assertFalse($success, "Memcache::unlockock (negative)");
	} 
	
	 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock_From_Different_Client($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$success = $instance2->unlock($testKey);
		$this->assertFalse($success, "Memcache::unlockock (negative)");
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertFalse($success, "Memcache::set (negative)");
		$instance->unlock($testKey);
		$this->assertFalse($success, "Memcache::unlockock (positive)");
	} 
	
		//***** getl and unlock. Check lock is released ***** ////
		
		
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock_Set($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
			// same client
		$instance->getl($testKey);
		$instance->unlock($testKey);
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
			// different client
		$instance->getl($testKey);
		$instance->unlock($testKey);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
			// both clients
		$instance->getl($testKey);
		$instance->unlock($testKey);
		$instance2->set($testKey, $testValue, $testFlags);
		$success = $instance->set($testKey, $testValue, $testFlags);		
   		$this->assertTrue($success, "Memcache::set (positive)");
			
	} 

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey);
		$instance->unlock($testKey);
		// same client
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		// different client
   		$returnValue = $instance2->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

	}  

	/**
     * @dataProvider keyValueProvider
     */
	public function test_Unlock_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		// same client
		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
		$instance->unlock($testKey);
   		$success = $instance->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		 // verify key is not present	
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		// different client
		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
		$instance->unlock($testKey);
   		$success = $instance2->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		 // verify key is not present	
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
	} 
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->unlock($testKey);
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
	public function test_Add_Unlock($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		
   		// positive add test 
   		$instance->add($testKey, $testValue1, $testFlags);
		$instance->getl($testKey);
		$instance->unlock($testKey);
		$success = $instance2->add($testKey, $testValue, $testFlags);
   		$this->assertFalse($success, "Memcache::add (negative)");		
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
	}
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Unlock_Increment($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
		
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
		$instance->unlock($testKey);
   		$returnValue = $instance2->increment($testKey, $testValue1);
   		$this->assertEquals($returnValue, 2 * $testValue1,  "Memcache::increment (positive)");
	
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Unlock_Decrement($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
   		
   		$instance->set($testKey, $testValue1 * 2);
		$instance->getl($testKey);
		$instance->unlock($testKey);
   		$returnValue = $instance2->decrement($testKey, $testValue1);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::decrement (positive)");
		
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
