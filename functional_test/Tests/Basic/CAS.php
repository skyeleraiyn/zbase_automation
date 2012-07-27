<?php

abstract class CAS_TestCase extends ZStore_TestCase{
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_CASNonExistingObject($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		
		// negative cas test
   		$success = $instance->cas($testKey, $testValue, 0, 0, 255);
   		$this->assertFalse($success, "Memcache::cas (negative)");

   		// negative get test
   		$returnValue = $instance->get($testKey);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_IncorrectCAS($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$instance->set($testKey, $testValue1, $testFlags);
		
		$returnFlags = null;
   		$returnCAS = null;
		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
		
		$instance->set($testKey, $testValue2, $testFlags);
   		
   		// negative cas test
   		$success = $instance->cas($testKey, $testValue1, $testFlags, 0, $returnCAS);
   		$this->assertFalse($success, "Memcache::cas (negative)");
   		
		// validate set value
   		$returnValue = $instance->get($testKey);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
	}
	
	
	public function test_CASMultiGet() {
		
		$instance = $this->sharedFixture;

		$set = $this->keyValueFlagsProvider();
		
		// set all values
		$keys = array();
		$count = 0;
		foreach ($set as $item) {
			list($key,$value,$flags) = $item;

			$instance->delete($key);
			
			if (++$count % 2) {
				$data[$key]=array($value, $flags, true);
				$instance->set($key, $value, $flags);
			} else {
				$data[$key]=array($value, $flags, false);
			}
		}
		
		// multi get
		$returnFlags = array();
		$returnCAS = array();
		$returnValues = $instance->get(array_keys($data), $returnFlags, $returnCAS);
		$this->assertNotEquals($returnValues, false, "Memcache::get (positive)");
		$this->assertTrue(is_array($returnValues));
		$this->assertTrue(is_array($returnFlags));
		
		// validate
		foreach ($data as $key => $item) {
			list($value, $flags, $exists) = $item;
			
			if ($exists) {
	   			// full test
				$this->assertTrue(isset($returnValues[$key]), "Memcache::get (value)");
				$this->assertEquals($value, $returnValues[$key], "Memcache::get (value)");
				$this->assertTrue(isset($returnFlags[$key]), "Memcache::get (flag)");
				$this->assertEquals($flags, $returnFlags[$key], "Memcache::get (flag)");
				$this->assertTrue(isset($returnCAS[$key]), "Memcache::get (flag)");
				
				// validate we got the correct CAS value
				$success = $instance->cas($key, "test-value", 0, 0, $returnCAS[$key]);
				$this->assertTrue($success, "Memcache:cas (positive)");
				
			} else {
				// should be omitted in array
				$this->assertFalse(isset($returnValues[$key]), "Memcache::get (value)");
			}
		}		
	} 
	
	public function est_CASMultiGet2() { //under constuction
		
		$instance = $this->sharedFixture;

		$set = $this->keyValueFlagsProvider();
		
		// set all values
		$keys = array();
		$count = 0;
		foreach ($set as $item) {
			list($key,$value,$flags) = $item;

			$instance->delete($key);

			$goodKey = (++$count % 2);
			
			if ($goodKey) {
				$instance->set($key, $value, $flags);
			} 

			$data[$key]=array($value, $flags, $goodKey);
		}
		
		// multi get
		$returnFlags = null;
		$returnCAS = null;
		$returnValues = null;
		$returnSuccess = $instance->get2(array_keys($data), $returnValues, $returnFlags, $returnCAS);
		$this->assertNotEquals($returnValues, false, "Memcache::get2 (positive)");
		$this->assertTrue(is_array($returnSuccess), "Memcache::get2 (success)");
		$this->assertTrue(is_array($returnValues), "Memcache::get2 (values)");
		$this->assertTrue(is_array($returnFlags), "Memcache::get2 (flags)");
		
		// validate
		foreach ($data as $key => $item) {
			list($value, $flags, $goodKey) = $item;

			$this->assertTrue(isset($returnSuccess[$key]), "Memcache::get2 (success)");
			$this->assertTrue(isset($returnValues[$key]), "Memcache::get2 (value)");
			$this->assertTrue(isset($returnFlags[$key]), "Memcache::get2 (flag)");
			$this->assertTrue(isset($returnCAS[$key]), "Memcache::get2 (flag)");
			
			if ($goodKey) {
	   			// full test
	   			$this->assertTrue($returnSuccess[$key], "Memcache::get2 (success)");
				$this->assertEquals($value, $returnValues[$key], "Memcache::get2 (value)");
				$this->assertEquals($flags, $returnFlags[$key], "Memcache::get2 (flag)");
				
				// validate we got the correct CAS value
				$success = $instance->cas($key, "test-value", 0, 0, $returnCAS[$key]);
				$this->assertTrue($success, "Memcache:cas (positive)");
				
			} else {
	   			$this->assertTrue($returnSuccess[$key], "Memcache::get2 (success)");
				$this->assertFalse($returnValues[$key], "Memcache::get2 (value)");
			}
		}		
	} 
	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get2IncorrectCAS($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnValue = null;
   		$returnCAS = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags, $returnCAS);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		
   		$instance->set($testKey, $testValue2, $testFlags);
   		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue1, $testFlags, 0, $returnCAS);
   		$this->assertFalse($success, "Memcache::cas (positive)");
   		
   		// validate set value
   		$returnFlags = null;
		$returnValue = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_CorrectCAS($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnCAS = null;
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
   		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS);
   		$this->assertTrue($success, "Memcache::cas (positive)");
   		
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get2CorrectCAS($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnValue = null;
   		$returnCAS = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags, $returnCAS);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS);
   		$this->assertTrue($success, "Memcache::cas (positive)");
   		
   		// validate set value
   		$returnFlags = null;
		$returnValue = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_CorrectCASTTL($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
	
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
   		$testTTL = 30;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnCAS = null;
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
   		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, $testTTL, $returnCAS);
   		$this->assertTrue($success, "Memcache::cas (positive)");
   		
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_CorrectCASTTLExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$testTTL = 1;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnCAS = null;
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
   		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, $testTTL, $returnCAS);
   		$this->assertTrue($success, "Memcache::cas (positive)");
   		
   		sleep($testTTL + 1);
   		
   		// validate cas'd value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
   		
	}
	
}

class CAS_TestCase_Full extends CAS_TestCase{

	public function keyProvider() {
		return Data_generation::provideKeys();
	}
	
	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}
	
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
}
