<?php

abstract class ByKeyEviction_TestCase extends ZStore_TestCase {
	
	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/
	public function test_SetByKey_GetByKey_Evict($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();

		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);

		Utility::EvictKeyFromMemory_Server_Array($atestKey[0]);
		
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
	public function test_CasByKey_Evict($atestKey, $atestValue, $testFlags) {

		$testValue1 = "testvalue1";
		$testValue2 = "testvalue2";
		$testValue3 = "testvalue3";
	
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
	
		Utility::EvictKeyFromMemory_Server_Array($atestKey[0]);

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
	public function ttest_IncorrectCasByKey_Evict($atestKey, $atestValue, $testFlags) {

		$testValue1 = "testvalue1";
		$testValue2 = "testvalue2";
		$testValue3 = "testvalue3";
	
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

		Utility::EvictKeyFromMemory_Server_Array($atestKey[0]);	
		$cas_value = CASVALUE;
		$getsuccess1 = $instance->casByKey($atestKey[0], $testValue1, $testFlags, TIMEOUT, $cas_value, SHARDKEY1);
		$this->assertFalse($getsuccess1, "Memcache::casByKey (negative)");
		$getsuccess2 = $instance->casByKey($atestKey[0], $testValue2, $testFlags, TIMEOUT, $cas_value, SHARDKEY2);
		$this->assertFalse($getsuccess1, "Memcache:[0]:casByKey (negative)");
		$getsuccess3 = $instance->casByKey($atestKey[0], $testValue3, $testFlags, TIMEOUT, $cas_value, SHARDKEY3);
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

	public function test_DeleteByKey_Evict($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();
		
		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		Utility::EvictKeyFromMemory_Server_Array($atestKey[0]);

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

	public function test_ByKey_Expiry_Evict($atestKey, $atestValue, $testFlags) {
	
		$instance = Connection::getServerPool();
		
		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, 1, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $atestValue[1], $testFlags, 2, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $atestValue[2], $testFlags, 1, CASVALUE, SHARDKEY3);

		Utility::EvictKeyFromMemory_Server_Array($atestKey[0], 3);
		
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
    public function test_ReplaceByKey_Evict($atestKey, $atestValue, $testFlags) {

		$instance = Connection::getServerPool();
		$testnewValue1 = "testnewvalue1";
		$testnewValue2 = "testnewvalue2";
		$testnewValue3 = "testnewvalue3";
		
		$instance->setByKey($atestKey[0], $testnewValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[0], $testnewValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[0], $testnewValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);

		Utility::EvictKeyFromMemory_Server_Array($atestKey[0]);

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

    public function test_AppendByKey_Evict() {

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

		Utility::EvictKeyFromMemory_Server_Array($testKey);

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
	 
    public function test_PrependByKey_Evict() {

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

		Utility::EvictKeyFromMemory_Server_Array($testKey);

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


	
	public function test_IncByKey_Evict() {
	
		$instance = Connection::getServerPool();
		$testKey = "testkey";
		$testValue1 = 10;
		$testValue2 = 15;
		$testValue3 = 19;
		$testFlags = 0;
		
		$instance->setByKey($testKey, $testValue1, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($testKey, $testValue2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($testKey, $testValue3, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		Utility::EvictKeyFromMemory_Server_Array($testKey);

		$returnValue1 = $instance->incrementByKey($testKey, SHARDKEY1, $testValue1);
		$this->assertEquals($returnValue1, 2 * $testValue1,  "Memcache::increment (positive)");
		$returnValue2 = $instance->incrementByKey($testKey, SHARDKEY2, $testValue2);
		$this->assertEquals($returnValue2, 2 * $testValue2,  "Memcache::increment (positive)");
		$returnValue3 = $instance->incrementByKey($testKey, SHARDKEY3, $testValue3);
		$this->assertEquals($returnValue3, 2 * $testValue3,  "Memcache::increment (positive)");
		
	}
	
	public function test_DecrByKey_Evict() {
	
		$instance = Connection::getServerPool();
		$testKey = "testkey";
		$testValue1 = 10;
		$testValue2 = 15;
		$testValue3 = 19;
		$testFlags = 0;
		
		$instance->setByKey($testKey, $testValue1 * 2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($testKey, $testValue2 * 2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($testKey, $testValue3 * 2, $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		Utility::EvictKeyFromMemory_Server_Array($testKey);

		$returnValue1 = $instance->decrementByKey($testKey, SHARDKEY1, $testValue1);
		$this->assertEquals($returnValue1, $testValue1,  "Memcache::increment (positive)");
		$returnValue2 = $instance->decrementByKey($testKey, SHARDKEY2, $testValue2);
		$this->assertEquals($returnValue2, $testValue2,  "Memcache::increment (positive)");
		$returnValue3 = $instance->decrementByKey($testKey, SHARDKEY3, $testValue3);
		$this->assertEquals($returnValue3, $testValue3,  "Memcache::increment (positive)");
		
	}
	
}


class ByKeyEviction_TestCase_Full extends ByKeyEviction_TestCase
{
	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function ArrayKeyArrayValueFlags() {
		return Data_generation::provideArrayKeyArrayValueFlags();
	}
	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}

?>
