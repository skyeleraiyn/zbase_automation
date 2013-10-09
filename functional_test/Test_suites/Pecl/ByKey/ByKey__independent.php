<?php

abstract class ByKey_TestCase extends ZStore_TestCase {
	
	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/
	public function test_SetByKey_GetByKey($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();

		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		
	}
	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/
	public function test_CasByKey($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();

		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1, $returnCas1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2, $returnCas2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3, $returnCas3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
	
                $getsuccess1 = $instance->casByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, $returnCas1 , SHARDKEY1);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");
                $getsuccess2 = $instance->casByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, $returnCas2 , SHARDKEY2);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");
                $getsuccess3 = $instance->casByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, $returnCas3 , SHARDKEY3);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");

		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1, $returnCas1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2, $returnCas2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue2, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3, $returnCas3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue3, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags3, "Memcache::get (flag1)");
	}



		/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/
	public function test_IncorrectCasByKey($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();

		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1, $returnCas1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2, $returnCas2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3, $returnCas3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$cas_value = CASVALUE;
		$getsuccess1 = $instance->casByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, $cas_value, SHARDKEY1);
		$this->assertFalse($getsuccess1, "Memcache::casByKey (negative)");
		$getsuccess2 = $instance->casByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, $cas_value, SHARDKEY2);
		$this->assertFalse($getsuccess1, "Memcache::casByKey (negative)");
		$getsuccess3 = $instance->casByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, $cas_value, SHARDKEY3);
		$this->assertFalse($getsuccess1, "Memcache::casByKey (negative)");

		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1, $returnCas1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2, $returnCas2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3, $returnCas3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags3, "Memcache::get (flag1)");
	}


	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/

	public function test_DeleteByKey($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();
		
		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$returnValue1 = $instance->deleteByKey($atestKey[0], SHARDKEY1, 0);
		$this->assertTrue($returnValue1, "Memcache::delete1 (positive)");
		$returnValue2 = $instance->deleteByKey($atestKey[0], SHARDKEY2, 0);
		$this->assertTrue($returnValue2, "Memcache::delete2 (positive)");
		$returnValue3 = $instance->deleteByKey($atestKey[0], SHARDKEY3, 0);
		$this->assertTrue($returnValue3, "Memcache::delete3 (positive)");

		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1);
		$this->assertNotEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2);
		$this->assertNotEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3);
		$this->assertNotEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		

	}	
	
	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	
	public function test_AddByKey($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();

		$instance->deleteByKey($atestKey[0], SHARDKEY1, 0);
		$instance->deleteByKey($atestKey[0], SHARDKEY2, 0);
		$instance->deleteByKey($atestKey[0], SHARDKEY3, 0);
		
		$success1 = $instance->addByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$this->assertTrue($success1, "Memcache::add1 (positive)");
		$success2 = $instance->addByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$this->assertTrue($success2, "Memcache::add2 (positive)");
		$success3 = $instance->addByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		$this->assertTrue($success3, "Memcache::add3 (positive)");
		
		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		
	}	

	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	
    public function test_ReplaceByKey($atestKey, $atestValue, $testFlags) {

		$instance = Connection::getServerPool();
		
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);

		$replacesuccess1 = $instance->replaceByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$this->assertTrue($replacesuccess1, "Memcache::append (positive)");
		$replacesuccess2 = $instance->replaceByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$this->assertTrue($replacesuccess2, "Memcache::append (positive)");
		$replacesuccess3 = $instance->replaceByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		$this->assertTrue($replacesuccess3, "Memcache::append (positive)");

		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2, $returnFlags2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3, $returnFlags3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
     }

    public function test_AppendByKey() {

		$instance = Connection::getServerPool();
		$testKey = "testkey";
		$testValue1 = "testvalue1";
		$testValue2 = "testvalue2";
		$testValue3 = "testvalue3";
		$testrevValue1 = strrev($testValue1);
		$testrevValue2 = strrev($testValue2);
		$testrevValue3 = strrev($testValue3);
		$testFlags = 0;

		$instance->setByKey($testKey, $testValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($testKey, $testValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($testKey, $testValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);

		$appendsuccess1 = $instance->AppendByKey($testKey, $testrevValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$this->assertTrue($appendsuccess1, "Memcache::prepend (positive)");
		$appendsuccess2 = $instance->AppendByKey($testKey, $testrevValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$this->assertTrue($appendsuccess2, "Memcache::prepend (positive)");
		$appendsuccess3 = $instance->AppendByKey($testKey, $testrevValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		$this->assertTrue($appendsuccess3, "Memcache::prepend (positive)");

		$getsuccess1 = $instance->getByKey($testKey,  SHARDKEY1, $returnValue1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($testValue1.$testrevValue1, $returnValue1, "Memcache::getbykey (value1)");
		$getsuccess2 = $instance->getByKey($testKey,  SHARDKEY2, $returnValue2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($testValue2.$testrevValue2, $returnValue2, "Memcache::getbykey (value2)");
		$getsuccess3 = $instance->getByKey($testKey,  SHARDKEY3, $returnValue3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($testValue3.$testrevValue3, $returnValue3, "Memcache::getbykey (value3)");
     }
	 
    public function test_PrependByKey() {

		$instance = Connection::getServerPool();
		$testKey = "testkey";
		$testValue1 = "testvalue1";
		$testValue2 = "testvalue2";
		$testValue3 = "testvalue3";
		$testrevValue1 = strrev($testValue1);
		$testrevValue2 = strrev($testValue2);
		$testrevValue3 = strrev($testValue3);
		$testFlags = 0;
		
		$instance->setByKey($testKey, $testValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($testKey, $testValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($testKey, $testValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);

		$prependsuccess1 = $instance->PrependByKey($testKey, $testrevValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$this->assertTrue($prependsuccess1, "Memcache::replace (positive)");
		$prependsuccess2 = $instance->PrependByKey($testKey, $testrevValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$this->assertTrue($prependsuccess2, "Memcache::replace (positive)");
		$prependsuccess3 = $instance->PrependByKey($testKey, $testrevValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		$this->assertTrue($prependsuccess3, "Memcache::replace (positive)");

		$getsuccess1 = $instance->getByKey($testKey,  SHARDKEY1, $returnValue1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($testrevValue1.$testValue1, $returnValue1, "Memcache::getbykey (value1)");
		$getsuccess2 = $instance->getByKey($testKey,  SHARDKEY2, $returnValue2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($testrevValue2.$testValue2, $returnValue2, "Memcache::getbykey (value2)");
		$getsuccess3 = $instance->getByKey($testKey,  SHARDKEY3, $returnValue3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($testrevValue3.$testValue3, $returnValue3, "Memcache::getbykey (value3)");
     }

	public function test_GetByKey_Nonexistant_Key() {
	
		$instance = Connection::getServerPool();
		$testKey = "testkey_not_existant";
		
		$getsuccess1 = $instance->getByKey($testKey,  SHARDKEY1, $returnValue1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertNull($returnValue1, "Memcache::getbykey (value1)");
	
	}
	
	
	public function test_IncByKey() {
	
		$instance = Connection::getServerPool();
		$testKey = "testkey";
		$testValue1 = 10;
		$testValue2 = 15;
		$testValue3 = 19;
		$testFlags = 0;
		
		$instance->setByKey($testKey, $testValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($testKey, $testValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($testKey, $testValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$returnValue1 = $instance->incrementByKey($testKey, SHARDKEY1, $testValue1);
		$this->assertEquals($returnValue1, 2 * $testValue1,  "Memcache::increment (positive)");
		$returnValue2 = $instance->incrementByKey($testKey, SHARDKEY2, $testValue2);
		$this->assertEquals($returnValue2, 2 * $testValue2,  "Memcache::increment (positive)");
		$returnValue3 = $instance->incrementByKey($testKey, SHARDKEY3, $testValue3);
		$this->assertEquals($returnValue3, 2 * $testValue3,  "Memcache::increment (positive)");
		
	}
	
	public function test_DecrByKey() {
	
		$instance = Connection::getServerPool();
		$testKey = "testkey";
		$testValue1 = 10;
		$testValue2 = 15;
		$testValue3 = 19;
		$testFlags = 0;
		
		$instance->setByKey($testKey, $testValue1 * 2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($testKey, $testValue2 * 2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($testKey, $testValue3 * 2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$returnValue1 = $instance->decrementByKey($testKey, SHARDKEY1, $testValue1);
		$this->assertEquals($returnValue1, $testValue1,  "Memcache::increment (positive)");
		$returnValue2 = $instance->decrementByKey($testKey, SHARDKEY2, $testValue2);
		$this->assertEquals($returnValue2, $testValue2,  "Memcache::increment (positive)");
		$returnValue3 = $instance->decrementByKey($testKey, SHARDKEY3, $testValue3);
		$this->assertEquals($returnValue3, $testValue3,  "Memcache::increment (positive)");
		
	}

	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/

	public function test_ByKey_Expiry($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();
		
		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, 1, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, 2, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, 1, CASVALUE, SHARDKEY3);
		
		sleep(3);
		
		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1);
		$this->assertNotEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$getsuccess2 = $instance->getByKey($atestKey[0],  SHARDKEY2, $returnValue2);
		$this->assertNotEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$getsuccess3 = $instance->getByKey($atestKey[0],  SHARDKEY3, $returnValue3);
		$this->assertNotEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		

	}	
	
	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	 
        public function test_Cas_MultiByKey($atestKey, $atestValue, $testFlags) {

                $instance = Connection::getServerPool();
                $keys = array(
                        $atestKey[0] => array(
                                "value" => $atestValue[0],
                                "shardKey" => SHARDKEY1,
                                "flag" => $testFlags,
                                "cas" => 0,
                                "expire" => 1209600
                        ),
                        $atestKey[1] => array(
                                "value" => $atestValue[1],
                                "shardKey" => SHARDKEY2,
                                "flag" => $testFlags,
                                "cas" => 0,
                                "expire" => 1209600
                        ),
                        $atestKey[2] => array(
                                "value" => $atestValue[2],
                                "shardKey" => SHARDKEY3,
                                "flag" => $testFlags,
                                "cas" => 0,
                                "expire" => 1209600
                        )
                );

                $getkeys = array(
                $atestKey[0] => SHARDKEY1,
                $atestKey[1] => SHARDKEY2,
                $atestKey[2] => SHARDKEY3
                );
                $instance->setMultiByKey($keys);
                $return_array = $instance->getMultiBykey($getkeys);
                $keys[$atestKey[0]]["cas"] = $return_array[$atestKey[0]]["cas"];
                $keys[$atestKey[1]]["cas"] = $return_array[$atestKey[1]]["cas"];
                $keys[$atestKey[2]]["cas"] = $return_array[$atestKey[2]]["cas"];

                $keys[$atestKey[0]]["value"] = $atestValue[1];
                $keys[$atestKey[1]]["value"] = $atestValue[2];
                $keys[$atestKey[2]]["value"] = $atestValue[0];

                $instance->casMultiByKey($keys);
                $return_array = $instance->getMultiBykey($getkeys);

                $success = True;
                $printmessage =  "Memcache::casmulti (positive)";

                if (!($return_array[$atestKey[0]]["value"] == $atestValue[1]))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[0]]["value"]." does not match with ".$atestValue[1];
                }
                if (!($return_array[$atestKey[1]]["value"] == $atestValue[2]))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[1]]["value"]." does not match with ".$atestValue[2];
                }
                if (!($return_array[$atestKey[2]]["value"] == $atestValue[0]))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[2]]["value"]." does not match with ".$atestValue[0];
                }
                        // verify flags
                        if (!($return_array[$atestKey[0]]["flag"] == $keys[$atestKey[0]]["flag"]))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[0]]["flag"]." flag does not match with ".$keys[$atestKey[0]]["flag"];
                }
                        if (!($return_array[$atestKey[1]]["flag"] == $keys[$atestKey[1]]["flag"]))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[1]]["flag"]." flag does not match with ".$keys[$atestKey[1]]["flag"];
                }
                        if (!($return_array[$atestKey[2]]["flag"] == $keys[$atestKey[2]]["flag"]))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[2]]["flag"]." flag does not match with ".$keys[$atestKey[2]]["flag"];
                }
                $this->assertTrue($success, $printmessage);
        }

	

		/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	 
	//Sometimes segfaults
	public function test_Cas_Incorrect_MultiByKey($atestKey, $atestValue, $testFlags) { 
	
		$instance = Connection::getServerPool();
		$testValue1 = "testvalue1";
		$testValue2 = "testvalue2";
		$testValue3 = "testvalue3";
		$keys = array(
			$atestKey[0] => array(
				"value" => $atestValue[0],
				"shardKey" => SHARDKEY1,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[1] => array(
				"value" => $atestValue[1],
				"shardKey" => SHARDKEY2,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[2] => array(
				"value" => $atestValue[2],
				"shardKey" => SHARDKEY3,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			)
		);
	
		$getkeys = array(
		$atestKey[0] => SHARDKEY1,
		$atestKey[1] => SHARDKEY2,
		$atestKey[2] => SHARDKEY3
		);
		$instance->setMultiByKey($keys);
		$return_array = $instance->getMultiBykey($getkeys);
	  	
		$keys[$atestKey[0]]["value"] = $atestValue[1];
		$keys[$atestKey[1]]["value"] = $atestValue[2];
		$keys[$atestKey[2]]["value"] = $atestValue[0];			

                $keys[$atestKey[0]]["cas"] = $return_array[$atestKey[0]]["cas"];
                $keys[$atestKey[1]]["cas"] = $return_array[$atestKey[1]]["cas"];
                $keys[$atestKey[2]]["cas"] = $return_array[$atestKey[2]]["cas"];

		$instance->casMultiByKey($keys);
		$return_array = $instance->getMultiBykey($getkeys);

		$success = True;
		$printmessage =  "Memcache::casmulti (negative)";
		
		if (!($return_array[$atestKey[0]]["value"] == $keys[$atestKey[0]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["value"]." does not match with ".$keys[$atestKey[0]]["value"];
		}
		if (!($return_array[$atestKey[1]]["value"] == $keys[$atestKey[1]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["value"]." does not match with ".$keys[$atestKey[1]]["value"];
		}
		if (!($return_array[$atestKey[2]]["value"] == $keys[$atestKey[2]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["value"]." does not match with ".$keys[$atestKey[2]]["value"];
		}

			// verify flags
		if (!($return_array[$atestKey[0]]["flag"] == $keys[$atestKey[0]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["flag"]." flag does not match with ".$keys[$atestKey[0]]["flag"];
		}	
			if (!($return_array[$atestKey[1]]["flag"] == $keys[$atestKey[1]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["flag"]." flag does not match with ".$keys[$atestKey[1]]["flag"];
		}	
			if (!($return_array[$atestKey[2]]["flag"] == $keys[$atestKey[2]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["flag"]." flag does not match with ".$keys[$atestKey[2]]["flag"];
		}	
		$this->assertTrue($success, $printmessage);

	}


	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	 
	public function test_Set_Get_MultiByKey($atestKey, $atestValue, $testFlags) { 
	
		$instance = Connection::getServerPool();
		
			$keys = array(
			$atestKey[0] => array(
				"value" => $atestValue[0],
				"shardKey" => SHARDKEY1,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[1] => array(
				"value" => $atestValue[1],
				"shardKey" => SHARDKEY2,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[2] => array(
				"value" => $atestValue[2],
				"shardKey" => SHARDKEY3,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			)
		);
	
		$getkeys = array(
		$atestKey[0] => SHARDKEY1,
		$atestKey[1] => SHARDKEY2,
		$atestKey[2] => SHARDKEY3
		);
		$instance->setMultiByKey($keys);
		$return_array = $instance->getMultiBykey($getkeys);
	   
		$success = True;
		$printmessage =  "Memcache::getmulti (positive)";
		
		if (!($return_array[$atestKey[0]]["value"] == $keys[$atestKey[0]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["value"]." does not match with ".$keys[$atestKey[0]]["value"];
		}
		if (!($return_array[$atestKey[1]]["value"] == $keys[$atestKey[1]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["value"]." does not match with ".$keys[$atestKey[1]]["value"];
		}
		if (!($return_array[$atestKey[2]]["value"] == $keys[$atestKey[2]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["value"]." does not match with ".$keys[$atestKey[2]]["value"];
		}

			// verify flags
			if (!($return_array[$atestKey[0]]["flag"] == $keys[$atestKey[0]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["flag"]." flag does not match with ".$keys[$atestKey[0]]["flag"];
		}	
			if (!($return_array[$atestKey[1]]["flag"] == $keys[$atestKey[1]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["flag"]." flag does not match with ".$keys[$atestKey[1]]["flag"];
		}	
			if (!($return_array[$atestKey[2]]["flag"] == $keys[$atestKey[2]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["flag"]." flag does not match with ".$keys[$atestKey[2]]["flag"];
		}	
		$this->assertTrue($success, $printmessage);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	
	public function test_Delete_MultiByKey($atestKey, $atestValue, $testFlags) { 
	
		$instance = Connection::getServerPool();
		
			$keys = array(
			$atestKey[0] => array(
				"value" => $atestValue[0],
				"shardKey" => SHARDKEY1,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[1] => array(
				"value" => $atestValue[1],
				"shardKey" => SHARDKEY2,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[2] => array(
				"value" => $atestValue[2],
				"shardKey" => SHARDKEY3,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			)
		);
	
		$deletekeys = array(
		$atestKey[0] => SHARDKEY1,
		$atestKey[1] => SHARDKEY2,
		$atestKey[2] => SHARDKEY3
		);
		$instance->setMultiByKey($keys);
		$instance->deleteMultiByKey($deletekeys);
		$return_array = $instance->getMultiBykey($deletekeys);

		$success = True;
		$printmessage =  "Memcache::getmulti (positive)";
		
		if (!($return_array[$atestKey[0]]["value"] == ""))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["value"]." is not empty";
		}
		if (!($return_array[$atestKey[1]]["value"] == ""))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["value"]." is not empty";
		}
		if (!($return_array[$atestKey[2]]["value"] == ""))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["value"]." is not empty";
		}
	
		$this->assertTrue($success, $printmessage);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	
	public function test_AddMultiByKey($atestKey, $atestValue, $testFlags) { 
	
		$instance = Connection::getServerPool();
		
		
			$keys = array(
			$atestKey[0] => array(
				"value" => $atestValue[0],
				"shardKey" => SHARDKEY1,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[1] => array(
				"value" => $atestValue[1],
				"shardKey" => SHARDKEY2,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			),
			$atestKey[2] => array(
				"value" => $atestValue[2],
				"shardKey" => SHARDKEY3,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			)
		);
	
		$getkeys = array(
		$atestKey[0] => SHARDKEY1,
		$atestKey[1] => SHARDKEY2,
		$atestKey[2] => SHARDKEY3
		);
		$instance->addMultiByKey($keys);
		$return_array = $instance->getMultiBykey($getkeys);
	   
		$success = True;
		$printmessage =  "Memcache::getmulti (positive)";
		
		if (!($return_array[$atestKey[0]]["value"] == $keys[$atestKey[0]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["value"]." does not match with ".$keys[$atestKey[0]]["value"];
		}
		if (!($return_array[$atestKey[1]]["value"] == $keys[$atestKey[1]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["value"]." does not match with ".$keys[$atestKey[1]]["value"];
		}
		if (!($return_array[$atestKey[2]]["value"] == $keys[$atestKey[2]]["value"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["value"]." does not match with ".$keys[$atestKey[2]]["value"];
		}

			// verify flags
			if (!($return_array[$atestKey[0]]["flag"] == $keys[$atestKey[0]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[0]]["flag"]." flag does not match with ".$keys[$atestKey[0]]["flag"];
		}	
			if (!($return_array[$atestKey[1]]["flag"] == $keys[$atestKey[1]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[1]]["flag"]." flag does not match with ".$keys[$atestKey[1]]["flag"];
		}	
			if (!($return_array[$atestKey[2]]["flag"] == $keys[$atestKey[2]]["flag"]))
		{
			$success = False;
			$printmessage = $printmessage.$return_array[$atestKey[2]]["flag"]." flag does not match with ".$keys[$atestKey[2]]["flag"];
		}	
		$this->assertTrue($success, $printmessage);

	}

	/**
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_GetByKey_Infinte_Loop() {

		//  Negative testcase to ensure php-pecl doesn't go into infinte loop
		$testKey = "testkey";
		$testValue = "testvalue";
		$stringlen = strlen($testValue);
		$testFlags = 2;
		$shardKey = "foo";
		
		// set the badkey in all machines 
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_2);
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_3);

		$instance = $this->sharedFixture;
		$success = $instance->getByKey($testKey, $shardKey, $returnValue);
		$this->assertFalse($success, "Memcache::getbykey (negative)");
		$this->assertNull($returnValue);
	
	}		
	
}


class ByKey_TestCase_Full extends ByKey_TestCase{

	public function ArrayKeyArrayValueFlags() {
		return Data_generation::provideArrayKeyArrayValueFlags();
	}

}

?>
