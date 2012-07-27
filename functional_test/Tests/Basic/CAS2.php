<?php
abstract class CAS2_TestCase extends ZStore_TestCase
{
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_CAS2CONST_Existing($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;

                $instance->set($testKey, $testValue, $testFlags);
                $returnFlags = null;
                $returnCAS = null;
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);

		// negative cas test
   		$success = $instance->cas($testKey, $testValue, 0, 0, 255);
   		$this->assertFalse($success, "Memcache::cas (negative)");
	}


	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_CAS2CONST_NonExisting($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		
		// negative cas test
   		$success = $instance->cas($testKey, $testValue, 0, 0, 255);
   		$this->assertFalse($success, "Memcache::cas (negative)");
	}



        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_CAS2Illegal($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;

                $instance->set($testKey, $testValue, $testFlags);

                // cas with expression
                $success = $instance->cas($testKey, $testValue, 0, 0, (3 + 5));
                $this->assertFalse($success, "Memcache::cas (negative)");


		//cas with string
		$cas = "cas";
                $success = @$instance->cas($testKey, $testValue, 0, 0, $cas);
                $this->assertFalse($success, "Memcache::cas (negative)");

		//cas with complex object
		$cas = array("cas");
                $success = @$instance->cas($testKey, $testValue, 0, 0, $cas);
                $this->assertFalse($success, "Memcache::cas (negative)");

		//cas with special character
		$cas = "\n";
                $success = @$instance->cas($testKey, $testValue, 0, 0, $cas);
                $this->assertFalse($success, "Memcache::cas (negative)");

        }


        /**
     * @dataProvider keyValueProvider
     */
        public function test_CasUpdateNum($testKey, $testValue) {

                $instance = $this->sharedFixture;
                $testValue  = 10;
                $instance->set($testKey, $testValue);

                $returnFlags = null;
                $returnCAS = null;
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);

                $oldCas = $returnCAS;
                $instance->decrement($testKey);
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
                $this->assertGreaterThan($oldCas, $returnCAS, "Memcache::get (cas)");


                $oldCas = $returnCAS;
                $instance->increment($testKey);
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
                $this->assertGreaterThan($oldCas, $returnCAS, "Memcache::get (cas)");

        }


        /**
     * @dataProvider keyValueProvider
     */
        public function test_CasUpdate($testKey, $testValue) {

                $instance = $this->sharedFixture;
                $testValue1 = array("testValue1");

                $instance->set($testKey, $testValue);

                $returnFlags = null;
                $returnCAS = null;
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);

                $oldCas = $returnCAS;
                $instance->append($testKey, $testValue1);
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
                $this->assertGreaterThan($oldCas, $returnCAS, "Memcache::get (cas)");

                $oldCas = $returnCAS;
                $instance->prepend($testKey, $testValue1);
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
                $this->assertGreaterThan($oldCas, $returnCAS, "Memcache::get (cas)");


        }




        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_CorrectCAS2_Get($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
		$recas = 5;

                $testValue1 = (array($testValue));
                $testValue2 = $testValue;
                $testValue3 = (array("testValue3"));

                $instance->set($testKey, $testValue1, $testFlags);

                $returnFlags = null;
                $returnCAS = null;
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);

                // positive cas test
                $success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS);
                $this->assertTrue($success, "Memcache::cas (positive)");

                // positive cas test again
                $success = $instance->cas($testKey, $testValue3, $testFlags, 0, $returnCAS);
                $this->assertTrue($success, "Memcache::cas (positive)");

                // validate set value
                $returnFlags = null;
		$getCas = null;
                $returnValue = $instance->get($testKey, $returnFlags, $getCas);
                $this->assertEquals($testValue3, $returnValue, "Memcache::get (value)");
                $this->assertEquals($getCas, $returnCAS, "Memcache::get (flag)");


		$getCas = null ;
                $instance->get2($testKey, $returnValue, $returnFlags, $getCas);
                $this->assertEquals($getCas, $returnCAS, "Memcache::get (flag)");

                $getCas = null;
                $returnValue = $instance->getl($testKey);

		//negative unlock test
 //               $success = $instance->unlock($testKey, -1);
//                $this->assertFalse($success, "Memcache::unlock (negative)");

		//positive unlock test
		$success = $instance->unlock($testKey, $returnCAS);
                $this->assertTrue($success, "Memcache::cas (positive)");

                $instance->get($testKey, $returnFlags, $returnCAS);

		$i = 0;
		while ($i++ < $recas){
			$success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS);
			$this->assertTrue($success, "Memcache::cas (positive) $i");
		}

        }

 
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_IncorrectCAS2($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = (array($testValue));
		$testValue2 = $testValue;
		
		$instance->set($testKey, $testValue1, $testFlags);
		
		$returnFlags = null;
   		$returnCAS = null;
		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
		$returnCAS2 = 100;
		
//		$instance->set($testKey, $testValue2, $testFlags);
   		
   		// negative cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS2);
   		$this->assertFalse($success, "Memcache::cas (negative)");

		// validate set value
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS3);
   		$this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($returnCAS3, $returnCAS, "Memcache::get (value)");
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

                        $data[$key]=array($value, $flags, true);
                        $instance->set($key, $value, $flags);
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
                                $newCas = $returnCAS[$key];

                                $value = $instance->get($key, $flags, $cas);
                                $this->assertEquals($value, "test-value", "Memcache::get (value)");
                                $this->assertEquals($newCas, $cas, "Memcache::get (cas)");


                        } else {
                                // should be omitted in array
                                $this->assertFalse(isset($returnValues[$key]), "Memcache::get (value)");
                        }
                }
        }


	public function test_CASMultiGet2() { //under constuction
		
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

/*
			$this->assertTrue(isset($returnSuccess[$key]), "Memcache::get2 (success)");
			$this->assertTrue(isset($returnValues[$key]), "Memcache::get2 (value)");
			$this->assertTrue(isset($returnFlags[$key]), "Memcache::get2 (flag)");
			$this->assertTrue(isset($returnCAS[$key]), "Memcache::get2 (flag)");
*/
			
			if ($goodKey) {
	   			// full test
	   			$this->assertTrue($returnSuccess[$key], "Memcache::get2 (success)");
				$this->assertEquals($value, $returnValues[$key], "Memcache::get2 (value)");
				$this->assertEquals($flags, $returnFlags[$key], "Memcache::get2 (flag)");
				
				// validate we got the correct CAS value
				$success = $instance->cas($key, "test-value", 0, 0, $returnCAS[$key]);
				$this->assertTrue($success, "Memcache:cas (positive)");
				
			} 
		}		
	} 
	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Get2IncorrectCAS2($testKey, $testValue, $testFlags) {

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
		$returnCAS2 = $returnCAS;
   		$success = $instance->cas($testKey, $testValue1, $testFlags, 0, $returnCAS2);
   		$this->assertFalse($success, "Memcache::cas (positive)");
   		
   		// validate set value
   		$returnFlags = null;
		$returnValue = null;
		$returnCAS3 = null ;
   		$success = $instance->get2($testKey, $returnValue, $returnFlags, $returnCAS3);
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
		$this->assertNotEquals($returnCAS3, $returnCAS2, "Memcache::get2 (cas)");
   		$this->assertTrue($success, "Memcache::get2 (positive)");
	}
	

        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_Get2CorrectCAS2($testKey, $testValue, $testFlags) {

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
	public function test_Cas2Evict($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		
		$testValue1 = array("value1");
		$testValue2 = array("value2");
		$testValue3 = array("value3");
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnCAS = null;
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
   		
		//evict
		Utility::EvictKeyFromMemory_Master_Server($testKey);

   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, 0, $returnCAS);
   		$this->assertTrue($success, "Memcache::casEvict (positive)");

   		//evict and cas again
		Utility::EvictKeyFromMemory_Master_Server($testKey);

   		// positive cas test
   		$success = $instance->cas($testKey, $testValue3, $testFlags, 0, $returnCAS);
   		$this->assertTrue($success, "Memcache::casEvict (positive)");
 	
   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertEquals($testValue3, $returnValue, "Memcache::get (value)");
	}


   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_CorrectCAS2TTLExpired($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;

		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
		$testTTL = 5;
		
		$instance->set($testKey, $testValue1, $testFlags);
   		
   		$returnFlags = null;
   		$returnCAS = null;
   		$returnValue = $instance->get($testKey, $returnFlags, $returnCAS);
   		
   		// positive cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, $testTTL, $returnCAS);
   		$this->assertTrue($success, "Memcache::cas (positive)");
   		
   		sleep($testTTL + 1);

    		// negative cas test
   		$success = $instance->cas($testKey, $testValue2, $testFlags, $testTTL, $returnCAS);
   		$this->assertFalse($success, "Memcache::cas (positive)");


		//case2: Expiry before first cas call
                $instance->set($testKey, $testValue1, $testFlags, $testTTL);

                $returnFlags = null;
                $returnCAS = null;
                $returnValue = $instance->get($testKey, $returnFlags, $returnCAS);

                sleep($testTTL + 1);

                $success = $instance->cas($testKey, $testValue2, $testFlags, $testTTL, $returnCAS);
                $this->assertFalse($success, "Memcache::cas (positive)");


   		
	}

}

class CAS2_TestCase_Full extends CAS2_TestCase{

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}
	
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}

}
