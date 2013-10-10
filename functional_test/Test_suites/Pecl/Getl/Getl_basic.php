<?php

abstract class Getl_TestCase extends ZStore_TestCase {
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl($testKey, $testValue, $testFlags) {
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		// same client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		// different client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		//release lock
		$instance2->set($testKey, $testValue);
	}
	
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Set($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		// same client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
		
		// different client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertFalse($success, "Memcache::set (negative)");
		
		//release lock
		$instance->set($testKey, $testValue);
	} 
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Getl($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		// same client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
		// different client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
		//release lock
		$instance->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider keyProvider
    */
	public function test_Getl_GetNonExistingValue($testKey) {
		
		$instance = $this->sharedFixture;
		
		// negative get test
   		$returnValue = $instance->getl($testKey);
		$this->assertNull($returnValue, "Memcache::get (negative)");
	}
	
	/**
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Uncompressed_Data_with_Flag_Compressed() {

		//  Negative testcase to ensure php-pecl doesn't crash
		$testKey = "testkey";
		$testKey1 = "testkey1";
		$testKey2 = "testkey2";
		$testValue = "testvalue";
		$stringlen = strlen($testValue);
		$testFlags = 2;

		// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);
		
		$instance = $this->sharedFixture;

		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		$instance->getl($getkey_list);
		$instance->getl($testKey);
		
	}	
	
	/**
     * @dataProvider keyProvider
     */
	public function test_Getl_GetNullOnKeyMiss($testKey) {

		$instance = $this->sharedFixture;

		$instance->setproperty("NullOnKeyMiss", true);
		
   		// validate added value
   		$returnValue = $instance->getl($testKey);
   		$this->assertNull($returnValue, "Memcache::get (negative)");
	} 
	
	/**
     * @dataProvider keyProvider
     */
	public function test_Getl_GetNullOnKeyMissBadConnection($testKey) {

		$instance = $this->sharedFixture;
		
		// bogus connection
		$instance = new Memcache;
		@$instance->addServer("192.168.168.192");
		@$instance->setproperty("NullOnKeyMiss", true);
				
   		// validate added value
   		$returnValue = @$instance->getl($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	} 

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

   		// same client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey);
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		// different client
   		$returnValue = $instance2->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		//release lock
		$instance->set($testKey, $testValue);
	}  
	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Get2($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		
   		// same client
   		$returnFlags = null;
   		$returnValue = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get2 (flag)");
		
		// different client
		$returnFlags = null;
   		$returnValue = null;
   		$success = $instance2->get2($testKey, $returnValue, $returnFlags);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get2 (flag)");
		
		//release lock
		$instance->set($testKey, $testValue);
	}	

	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Delete_Same_Client($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		// same client
		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
   		$success = $instance->delete($testKey);
		$this->assertFalse($success, "Memcache::delete (negative)");  		
   		 // verify key is present	
   		$returnValue = $instance->get($testKey);
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");

		//release lock
		$instance->set($testKey, $testValue);
	} 

	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Delete_Different_Client($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		// different client
		$instance->set($testKey, $testValue);
		$instance->getl($testKey);
   		$success = $instance2->delete($testKey);
		$this->assertFalse($success, "Memcache::delete (negative)");  		
   		 // verify key is present	
   		$returnValue = $instance->get($testKey);
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");

		//release lock
		$instance->set($testKey, $testValue);
	} 	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		// same client 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
   		$success = $instance->replace($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::replace (negative)");
   		
					// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
		
		// different client
		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);
   		$success = $instance2->replace($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::replace (negative)");
   		
					// validate value is not replaced
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");

		//release lock
		$instance->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Getl_Same_Client($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		
   		$instance->add($testKey, $testValue1, $testFlags);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		//release lock
		$instance->set($testKey, $testValue);
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Getl_Different_Client($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		
		$instance->add($testKey, $testValue1, $testFlags);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
			
		//release lock
		$instance2->set($testKey, $testValue);
}
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Getl_Add_Same_Client($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));		
		
   		$instance->add($testKey, $testValue1, $testFlags);
   		$instance->getl($testKey);

		$success = $instance->add($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::replace (positive)");

		
		//release lock
		$instance->set($testKey, $testValue);
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Getl_Add_Different_Client($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		$instance->add($testKey, $testValue1, $testFlags);
   		$instance->getl($testKey);

		$success = $instance2->add($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::add (negative)");
		
		//release lock
		$instance->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Increment_Same_Client($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
		
   		// same client
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);

					// positive increment test
   		$returnValue = $instance->increment($testKey, $testValue1);
		$this->assertFalse($returnValue, "Memcache::increment (negative)");
   		$returnValue = null;
		$returnValue = $instance->get($testKey);
		$this->assertEquals($returnValue, $testValue1,  "Memcache::increment (negative)");
		
		//release lock
		$instance->set($testKey, $testValue);
	} 

	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Increment_Different_Client($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
		
		// different client
		$instance->set($testKey, $testValue1);
		$instance->getl($testKey);

					// verify value is not incremented
   		$returnValue = $instance2->increment($testKey, $testValue1);
		$this->assertFalse($returnValue, "Memcache::increment (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::increment (negative)");
		
		//release lock
		$instance->set($testKey, $testValue);
	} 
	
	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Decrement_Same_Client($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
   		
   		// same client
   		$instance->set($testKey, $testValue1 * 2);
		$instance->getl($testKey);
					// positive decrement test
   		$returnValue = $instance->decrement($testKey, $testValue1);
		$this->assertFalse($returnValue, "Memcache::decrement (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);
   		$this->assertEquals($returnValue, $testValue1 * 2,  "Memcache::decrement (negative)");
			
		//release lock
		$instance->set($testKey, $testValue);
	}

	/**
     * @dataProvider keyValueProvider
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Decrement_Different_Client($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
   		
		// different client
		$instance->set($testKey, $testValue1 * 2);
		$instance->getl($testKey);
					// verify value is not decremented
   		$returnValue = $instance2->decrement($testKey, $testValue1);
		$this->assertFalse($returnValue, "Memcache::decrement (negative)");
		$returnValue = null;
		$returnValue = $instance->get($testKey);
   		$this->assertEquals($returnValue, $testValue1 * 2,  "Memcache::decrement (negative)");
		
		//release lock
		$instance->set($testKey, $testValue);
	}	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Getl_SetTTL($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testTTL = 3;
		
		// same client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
					// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		// different client
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		$success = $instance2->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertFalse($success, "Memcache::set (positive)");
					// validate key is not expired
		sleep($testTTL + 1);
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
				
		//release lock
		$instance->set($testKey, $testValue);
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */

	public function test_Getl_SetTTLExpired_Same_Client($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testTTL = 3;
		
		// positive set test
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		$instance->getl($testKey);
		
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

   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_AddTTLExpired_Same_Client($testKey, $testValue, $testFlags) { //commented for bug 3252

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		$testTTL = 3;
		
   		// positive add test 
   		$instance->add($testKey, $testValue, $testFlags, $testTTL);
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
   		sleep($testTTL+1);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_ReplaceTTLExpired_Same_Client($testKey, $testValue, $testFlags) { //commented for bug 3252

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		$testTTL = 3;
			
   		// replace and getl 
		$instance->set($testKey, $testValue, $testFlags);
   		$instance->replace($testKey, $testValue, $testFlags, $testTTL);
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
   		sleep($testTTL+1);
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");


	}

	/**
     * @dataProvider keyValueFlagsProvider
     */

	public function test_Getl_SetTTLExpired_Different_Client($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testTTL = 3;
		
		//  set expiry and getl
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
   		
   		sleep($testTTL + 1);
   		
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
		//  getl and set expiry 
		$instance->set($testKey, $testValue, $testFlags);
		$instance2->getl($testKey);
		$success = $instance2->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
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
