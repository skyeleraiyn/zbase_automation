<?php

abstract class Checksum_TestCase extends ZStore_TestCase {

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);

		$instance->set($testKey, $testValue, $testFlags);
   		
   		// validate added value
   		$returnFlags = null;

   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	} 

	 /**
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get_Corrupted_Data() {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
	
		$testKey = "testkey";
		$testValue = "testvalue";
		$testFlags = 0;
		
		$instance->set($testKey, $testValue, $testFlags);
		Utility::netcat_execute($testkey, $testFlags + Utility::get_flag_checksum_test(), $testValue, TEST_HOST_1);
		   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}


	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get_turnoff_checksum($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		$instance->setproperty("EnableChecksum", false);
		$instance->set($testKey, $testValue, $testFlags);

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
	public function test_Get_turnoff_after_set_checksum($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		$instance->set($testKey, $testValue, $testFlags);
		$instance->setproperty("EnableChecksum", false);

   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	} 	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get2($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$instance->set($testKey, $testValue, $testFlags);
   		
   		// validate added value
   		$returnFlags = null;
   		$success = $instance->get2($testKey, $returnValue);
   		$this->assertNotEquals($success, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	} 

		/**
     * @dataProvider keyValueProvider
     */
	public function test_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		// set reference value
		$instance->set($testKey, $testValue);
 		$instance->setproperty("EnableChecksum", true);
 		
   		// cleanup (this shouldn't be here, but we need a full membase flush to get rid of this)
   		$success = $instance->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		   		
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

	
	/**
     * @dataProvider keyValueProvider
    */
	public function test_ReplaceNonExistingValue($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
   		// negative replace test
   		$success = $instance->replace($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::replace (negative)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replace_Expired_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		$instance->set($testKey, $testValue1, $testFlags, 2);
   		sleep(3);
   		
   		// negative replace test
   		$success = $instance->replace($testKey, $testValue2);
   		$this->assertFalse($success, "Memcache::replace (negative)");
		
		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

		/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replace_Deleted_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		$instance->set($testKey, $testValue1, $testFlags);
   		$instance->delete($testKey);
   		
   		// negative replace test
   		$success = $instance->replace($testKey, $testValue2);
   		$this->assertFalse($success, "Memcache::replace (negative)");
		
		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}
		
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testValue1 = serialize(array($testValue));		
		$testValue2 = $testValue;
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
   		   		
   		// positive replace test
   		$success = $instance->replace($testKey, $testValue2, $testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flags)");
	}
	

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_AddExistingValue($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		$instance->set($testKey, $testValue1, $testFlags);

   		// positive add test
		$success = $instance->add($testKey, $testValue2, $testFlags);
   		$this->assertFalse($success, "Memcache::replace (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}

		/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Expired_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
		 // positive add test 
   		$instance->set($testKey, $testValue2, $testFlags, 2);
   		sleep(3);
   		$success = $instance->add($testKey, $testValue1, $testFlags);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Deleted_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
		 // positive add test 
   		$instance->set($testKey, $testValue2, $testFlags);
   		$instance->delete($testKey);
   		$success = $instance->add($testKey, $testValue1, $testFlags);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_IncrementNonExistingValue($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);	
		
   		$testValue1 = strlen($testValue);
		
		// negative increment test
   		$returnValue = $instance->increment($testKey, $testValue1);
   		$this->assertFalse($returnValue, "Memcache::increment (negative)");
	} 
	
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Increment($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
   		$testValue1 = strlen($testValue);
		
   		// set initial value
   		$instance->set($testKey, $testValue1);

   		// should not increment value if EnableChecksum is enabled
   		$instance->increment($testKey, $testValue1);
  		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");		
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_DecrementNonExistingValue($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
   		$testValue1 = strlen($testValue);
		
		// negative increment test
   		$returnValue = $instance->decrement($testKey, $testValue1);
   		$this->assertFalse($returnValue, "Memcache::decrement (negative)");
	}
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Decrement($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
   		$testValue1 = strlen($testValue);
   		
   		// set initial value
   		$instance->set($testKey, $testValue1);

   		// should not decrement value if EnableChecksum is enabled
   		$instance->decrement($testKey, $testValue1);
  		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");		
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_SetTTL($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testTTL = 30;
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_AddTTL($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testTTL = 30;
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_ReplaceTTL($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testTTL = 30;
		
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		
   		// positive add test 
   		$success = $instance->replace($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
		$this->assertEquals($testFlags + Utility::get_flag_checksum_test(), $returnFlags, "Memcache::get (flag)");
		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	}
   		
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_SetTTLExpired($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testTTL = 1;
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
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
	public function test_AddTTLExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testTTL = 1;
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		sleep($testTTL+1);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_ReplaceTTLExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$testTTL = 1;
		
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		
   		// positive add test 
   		$success = $instance->replace($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		sleep($testTTL+1);
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get_Uncompressed_Data_with_Flag_Compressed() {

		//  Negative testcase to ensure php-pecl doesn't crash
		$testKey = "testkey";
		$testKey1 = "testkey1";
		$testKey2 = "testkey2";
		$testValue = "testvalue";
		$testFlags = 2;

		// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $tempflag, $testValue, TEST_HOST_1);

		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);
		
		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		$instance->get($testKey);
		$instance->get($getkey_list);

	}	

	/**
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get_Unserialized_Data_with_Flag_Set_Serialize() {

		//  Negative testcase to ensure php-pecl doesn't crash
		$testKey = "testkey";
		$testKey1 = "testkey1";
		$testKey2 = "testkey2";
		$testValue = "testvalue";
		$testFlags = 1;
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		
		$instance->get($getkey_list);
		$instance->get($testKey);

}



	public function test_Append() {
		$testKey = "testkey";
		$testValue = "OldValue"; 
		$testFlags = 0;
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);	
		// positive append test
		$instance->set($testKey, $testValue, $testFlags);
		$success = $instance->append($testKey, "testValue");
   		$this->assertTrue($success, "Memcache::append (positive)");
		
		   // validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue."testValue", $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	} 	


	public function test_Prepend() {
		$testKey = "testkey";
		$testValue = "OldValue"; 
		$testFlags = 0;
		
		$instance = $this->sharedFixture;
		$instance->setproperty("EnableChecksum", true);	
		// positive append test
		$instance->set($testKey, $testValue, $testFlags);
		$success = $instance->prepend($testKey, "testValue");
   		$this->assertTrue($success, "Memcache::append (positive)");
		
		   // validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals("testValue".$testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	} 	
	
}



class Checksum_TestCase_Full extends Checksum_TestCase{

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	
}

?>
