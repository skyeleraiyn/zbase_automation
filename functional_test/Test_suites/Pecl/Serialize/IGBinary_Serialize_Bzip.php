<?php

abstract class IGBinary_Serialize_Bzip_test extends ZStore_TestCase {

 	/**
     * @dataProvider keyValueProvider
     */
	public function test_set_get_bzip_Normal_Value($testKey, $testValue){

			$instance = $this->sharedFixture;
			$testValue = $testValue.$testValue.$testValue.$testValue;
			$testFlags = MEMCACHE_COMPRESSED_BZIP2;
			 
			// positive set test
			$success = $instance->set($testKey, $testValue, $testFlags);
			$this->assertTrue($success, "Memcache::set (positive)");
			
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
	public function test_set_get_igbinary_serialize_Normal_Value($testKey, $testValue){

			$instance = $this->sharedFixture;
			$testValue = array($testValue);
			$testFlags = MEMCACHE_SERIALIZED_IGBINARY;
			
			// positive set test
			$success = $instance->set($testKey, $testValue, $testFlags);
			$this->assertTrue($success, "Memcache::set (positive)");
			
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
	public function test_set_get_bzip_igbinary_serialize_Normal_Value($testKey, $testValue){

			$instance = $this->sharedFixture;
			$testValue = $testValue.$testValue.$testValue.$testValue;
			$testFlags = MEMCACHE_COMPRESSED_LZO | MEMCACHE_SERIALIZED_IGBINARY;
			 
			// positive set test
			$success = $instance->set($testKey, $testValue, $testFlags);
			$this->assertTrue($success, "Memcache::set (positive)");
			
			// validate added value
			$returnFlags = null;
			$returnValue = $instance->get($testKey, $returnFlags);
			$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
			$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	
	}


		
 	/**
     * @dataProvider keyValueFlagsProvider_old_set
     */
	public function test_Set_Check_with_Old_New_Flag($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// positive set test
		$instance->set($testKey, $testValue, $testFlags);
		$temp_testFlags = MEMCACHE_COMPRESSED_BZIP2;
		$success = $instance->set($testKey, $testValue, $temp_testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($temp_testFlags, $returnFlags, "Memcache::get (flag)");		
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");				
	} 

	/**
     * @dataProvider keyValueFlagsProvider_old_set
     */
	public function test_Getl_Check_with_Old_New_Flag($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$instance->set($testKey, $testValue, $testFlags);
		$instance->get($testKey, $returnFlags);
 		$temp_testFlags = MEMCACHE_COMPRESSED_BZIP2;
		$instance->set($testKey, $testValue, $temp_testFlags);  		
		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->getl($testKey, 2, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($temp_testFlags, $returnFlags, "Memcache::get (flag)");
		
		$instance->set($testKey, $testValue, $testFlags);  		
		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->getl($testKey, 2, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	} 

	/**
     * @dataProvider keyValueFlagsProvider_old_set
     */
	public function test_Delete_Check_with_Old_New_Flag($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// set reference value
		$instance->set($testKey, $testValue, $testFlags);
  		$temp_testFlags = MEMCACHE_COMPRESSED_BZIP2;
		$instance->set($testKey, $testValue, $temp_testFlags);  		

   		$success = $instance->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		   		
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->delete($testKey);
		$instance->set($testKey, $testValue, $temp_testFlags);

   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($temp_testFlags, $returnFlags, "Memcache::get (flag)");		
	} 

	/**
     * @dataProvider keyValueFlagsProvider_old_set
     */
	public function test_Replace_Check_with_Old_New_Flag($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
   		   		
   		// positive replace test
		$instance->replace($testKey, $testValue1, $testFlags);
		$temp_testFlags = MEMCACHE_COMPRESSED_BZIP2;
   		$success = $instance->replace($testKey, $testValue2, $temp_testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		// validate replaced value
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($temp_testFlags, $returnFlags, "Memcache::get (flag)");	
		
   		$success = $instance->replace($testKey, $testValue1, $testFlags);
   		$this->assertTrue($success, "Memcache::replace (positive)");
   		
   		// validate replaced value
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");			
	}
	

	/**
     * @dataProvider keyValueFlagsProvider_old_set
     */
	public function test_SetTTLExpired_Check_with_Old_New_Flag($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 2;
		
		// positive set test
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		$testFlags = MEMCACHE_COMPRESSED_BZIP2;
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
   		sleep($testTTL + 2);
   		
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

 	/**
     * @dataProvider keyValueFlagsProvider_old_set
     */
	public function test_Set_Evict_Set_Check_with_Old_New_Flag($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// positive set test
		$instance->set($testKey, $testValue, $testFlags);
		$temp_testFlags = MEMCACHE_COMPRESSED_BZIP2;
		$success = $instance->set($testKey, $testValue, $temp_testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($temp_testFlags, $returnFlags, "Memcache::get (flag)");		
		
		$instance->set($testKey, $testValue, $testFlags);
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");			
	} 	
		
}


class IGBinary_Serialize_Bzip_Full extends IGBinary_Serialize_Bzip_test{
	
	public function keyValueProvider() {
		return array(array(uniqid('testkey_'), uniqid('testvalue_', True).uniqid('testvalue_', True).uniqid('testvalue_', True).uniqid('testvalue_', True)));
	}

	public function keyValueFlagsProvider_old_set() {
		return Data_generation::provideKeyValueFlags_old_set();
	}	
	
}

?>

