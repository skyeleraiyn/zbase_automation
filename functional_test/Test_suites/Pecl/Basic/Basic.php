<?php

abstract class Basic_TestCase extends ZStore_TestCase {
	
	
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Set($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
	} 
	
	/**
     * @dataProvider keyProvider
    */
	public function test_GetNonExistingValue($testKey) {
		$instance = $this->sharedFixture;
		
		// negative get test
   		$returnValue = $instance->get($testKey);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

	/**
     * @expectedException PHPUnit_Framework_Error
    */
	public function test_Get2_Response() {
		$instance = $this->sharedFixture;
		
		$testKey = "testkey";
		$badKey = "badkey";
		$testValue = "testvalue";
		$stringlen = strlen($testValue);
		$testFlags = 2;
		Utility::netcat_execute($badKey, $testFlags, $testValue, TEST_HOST_1);
		$instance->set($testKey, $testValue);
		
		// positive get2 test
		$success = $instance->get2($testKey, $returnValue);
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (positive)");
		$this->assertTrue($success, "Memcache::get2 (positive)");
		
		// negative get2 test
		$returnValue = null;
   		$success = $instance->get2($badKey, $returnValue);
   		$this->assertFalse($returnValue, "Memcache::get2 (negative)");
		$this->assertFalse($success, "Memcache::get2 (positive)");
		
	}	
	
	/**
     * @dataProvider keyProvider
    */
	public function test_Get2NonExistingValue($testKey) {
		$instance = $this->sharedFixture;
		
		// negative get test
		$returnValue = null;
   		$success = $instance->get2($testKey, $returnValue);
   		$this->assertFalse($returnValue, "Memcache::get2 (negative)");
		$this->assertTrue($success, "Memcache::get2 (positive)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

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
	public function test_Get2($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$instance->set($testKey, $testValue, $testFlags);
   		
   		// validate added value
   		$returnFlags = null;
   		$success = $instance->get2($testKey, $returnValue);
   		$this->assertNotEquals($success, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
	} 

	
	/**
     * @dataProvider keyProvider
     */
	public function test_GetNullOnKeyMiss($testKey) {

		$instance = $this->sharedFixture;

		$instance->setproperty("NullOnKeyMiss", true);
		
   		// validate added value
   		$returnValue = $instance->get($testKey);
   		$this->assertNull($returnValue, "Memcache::get (negative)");
	} 

	public function test_GetMulti() {
		
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
		$returnFlags = array();
		$returnValues = $instance->get(array_keys($data), $returnFlags);
		$this->assertNotEquals($returnValues, false, "Memcache::get (positive)");
		$this->assertTrue(is_array($returnValues));
		$this->assertTrue(is_array($returnFlags));
		
		// validate
		foreach ($data as $key => $item) {
			list($value, $flags, $goodKey) = $item;
			
			if ($goodKey) {
				// sanity test
		   		$returnFlags1 = null;
	   			$returnValue1 = $instance->get($key, $returnFlags1);
	   			$this->assertNotEquals($returnValue1, false, "Memcache::get (positive)");
	   			$this->assertEquals($value, $returnValue1, "Memcache::get (value)");
	   			$this->assertEquals($flags, $returnFlags1, "Memcache::get (flag)");
				
	   			// full test
				$this->assertTrue(isset($returnValues[$key]), "Memcache::get (value)");
				$this->assertEquals($value, $returnValues[$key], "Memcache::get (value)");
				$this->assertTrue(isset($returnFlags[$key]), "Memcache::get (flag)");
				$this->assertEquals($flags, $returnFlags[$key], "Memcache::get (flag)");
			} else {
				// should be omitted in array
				$this->assertFalse(isset($returnValues[$key]), "Memcache::get (value)");
			}
		}		
	} 
// need to check for the failure	
	public function est_Get2Multi() {
		
		// one good, one bogus server
		$instance = new Memcache;
		$instance->addServer(TEST_HOST_1);
		@$instance->addServer("192.168.168.192");

		// obtain data
		$set = $this->keyValueFlagsProvider();
		
		// two servers in pool
		$poolSize = 2;
		
		// set all values
		$keys = array();
		$count = 0;
		foreach ($set as $item) {
			list($key,$value,$flags) = $item;
			
        	// is it a good server?
        	$goodServer = ((((crc32($key) >> 16) & 0x7fff) % $poolSize) == 0);
        	$goodKey = (++$count % 2);
        	
        	if ($goodServer) {
				$instance->delete($key);
				
				if ($goodKey) {
					$instance->set($key, $value, $flags);	
				}
        	}

        	$data[$key]=array($value, $flags, $goodKey, $goodServer);
		}
		
		// multi get
		$returnFlags = array();
		$returnValues = array();
		$returnSuccess = @$instance->get2(array_keys($data), $returnValues, $returnFlags);
		$this->assertNotEquals($returnValues, false, "Memcache::get2 (positive)");
		$this->assertTrue(is_array($returnSuccess), "Memcache::get2 (success)");
		$this->assertTrue(is_array($returnValues), "Memcache::get2 (values)");
		$this->assertTrue(is_array($returnFlags), "Memcache::get2 (flags)");
		
		// validate
		foreach ($data as $key => $item) {
			list($value, $flags, $goodKey, $goodServer) = $item;

			// are all fields set?
			$this->assertTrue(isset($returnSuccess[$key]), "Memcache::get2 (success)");
			$this->assertTrue(isset($returnValues[$key]), "Memcache::get2 (value)");
			$this->assertTrue(isset($returnFlags[$key]), "Memcache::get2 (flag)");
			
			// test good case
			if ($goodServer && $goodKey) {
				// sanity test
		   		$returnFlags1 = null;
		   		$returnValue1 = null;
	   			$returnSuccess1 = $instance->get2($key, $returnFlags1);
	   			$this->assertTrue($returnSuccess1, "Memcache::get2 (success)");
	   			$this->assertEquals($value, $returnValue1, "Memcache::get2 (value)");
	   			$this->assertEquals($flags, $returnFlags1, "Memcache::get2 (flag)");

	   			// full test
	   			$this->assertTrue($returnSuccss[$key], "Memcache::get2 (success)");
				$this->assertEquals($value, $returnValues[$key], "Memcache::get2 (value)");
				$this->assertEquals($flags, $returnFlags[$key], "Memcache::get2 (flag)");		
	   			
			} elseif ($goodServer && !$goodKey) { 
				// missing key
	   			$this->assertTrue($returnSuccss[$key], "Memcache::get2 (success)");
				$this->assertFalse($returnValues[$key], "Memcache::get2 (value)");
			} else { 
				// bad server
	   			$this->assertFalse($returnSuccss[$key], "Memcache::get2 (success)");
				$this->assertFalse($returnValues[$key], "Memcache::get2 (value)");
			}
		}		
	} 
	
	/**
     * @dataProvider keyProvider
     */
	public function test_GetNullOnKeyMissBadConnection($testKey) {

		$instance = $this->sharedFixture;
		
		// bogus connection
		$instance = new Memcache;
		@$instance->addServer("192.168.168.192");
		@$instance->setproperty("NullOnKeyMiss", true);
				
   		// validate added value
   		$returnValue = @$instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Get2BadConnection($testKey, $testValue) {

		// bogus connection
		$instance = new Memcache;
		@$instance->addServer("192.168.168.192");

   		// validate added value
   		$returnValue = null;
   		$success = @$instance->get2($testKey, $returnValue);
   		$this->assertFalse($returnValue, "Memcache::get2 (value)" );
   		$this->assertFalse($success, "Memcache::get2 (negative)");
	}
	
	/**
     * @dataProvider keyProvider
    */
	public function test_DeleteNonExistingValue($testKey) {

		$instance = $this->sharedFixture;
		
		// negative delete test
		$success = $instance->delete($testKey);
   		$this->assertFalse($success, "Memcache::delete (negative)");
	}
	/**
     * @dataProvider keyValueFlagsProvider
    */
	public function test_DeleteExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// set value
		$instance->set($testKey, $testValue, $testFlags, 3);
		sleep(4);
		
		// negative delete test
		$success = $instance->delete($testKey);
   		$this->assertFalse($success, "Memcache::delete (expired negative)");
	}


	/**
     * @dataProvider keyValueFlagsProvider
    */
	public function test_DeleteNonExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		// set value
		$instance->set($testKey, $testValue, $testFlags, 3);
		
		// negative delete test
		$success = $instance->delete($testKey);
   		$this->assertTrue($success, "Memcache::delete (nonexpired positive)");
	}

	/**
     * @dataProvider keyValueProvider
     */
	public function test_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;

		// set reference value
		$instance->set($testKey, $testValue);
  		
   		// cleanup (this shouldn't be here, but we need a full zbase flush to get rid of this)
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
		
   		// negative replace test
   		$success = $instance->replace($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::replace (negative)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replace_Expired_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
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
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}
	

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_AddExistingValue($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
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
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}

		/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Expired_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
		 // positive add test 
   		$instance->set($testKey, $testValue1, $testFlags, 2);
   		sleep(3);
   		$success = $instance->add($testKey, $testValue1, $testFlags);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add_Deleted_Key($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
		 // positive add test 
   		$instance->set($testKey, $testValue1, $testFlags);
   		$instance->delete($testKey);
   		$success = $instance->add($testKey, $testValue1, $testFlags);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	}	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Add($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue1, $testFlags);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
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
	public function test_IncrementNonExistingValue($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		
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
		
   		$testValue1 = strlen($testValue);
		
   		// set initial value
   		$instance->set($testKey, $testValue1);

   		// positive increment test
   		$returnValue = $instance->increment($testKey, $testValue1);
   		$this->assertEquals($returnValue, 2 * $testValue1,  "Memcache::increment (positive)");
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_DecrementNonExistingValue($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		
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
		
   		$testValue1 = strlen($testValue);
   		
   		// set initial value
   		$instance->set($testKey, $testValue1 * 2);

   		// positive decrement test
   		$returnValue = $instance->decrement($testKey, $testValue1);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::decrement (positive)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_SetTTL($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 30;
		
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
	public function test_Get2TTL($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		$testTTL = 30;
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::set (positive)");
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = null;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get2 (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_AddTTL($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testTTL = 30;
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags, $testTTL);
   		$this->assertTrue($success, "Memcache::add (positive)");
   		
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
	public function test_ReplaceTTL($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testTTL = 30;
		
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		
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
     * @dataProvider keyValueFlagsProvider
     */
	public function test_SetTTLExpired($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
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
	
	public function test_assignment_of_return_GET2_variable(){

		$instance = $this->sharedFixture;

		$instance->set("testkey", "testvalue");	
		
		$returnValue = NULL;
		$testreturnValue = $returnValue;
		// Verify a variable assinged from $returnCAS has not changed when CAS operation has failed
		$instance->get2("test_dummy_key", $returnValue);
		$this->assertEquals(NULL, $testreturnValue, "Variable assgined with returnValue has changed with negative GET2 test");
		
		// Verify a variable assinged from $returnCAS has not changed when CAS operation is successful
		$returnValue = NULL;
		
		$instance->get2("testkey", $returnValue);
		$testreturnValue = $returnValue;
		$tempreturnValue = trim(shell_exec("echo ".$testreturnValue));

		$instance->set("testkey", "testvalue");	
		$instance->get2("testkey", $returnValue);
		$this->assertEquals($tempreturnValue, $testreturnValue, "Variable assgined with returnValue has changed with positive GET2 test");
			
	}	
}

class Basic_TestCase_Full extends Basic_TestCase{

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

?>

