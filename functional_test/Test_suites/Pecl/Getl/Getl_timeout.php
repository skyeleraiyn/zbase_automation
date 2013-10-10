<?php

abstract class Getl_TestCase extends ZStore_TestCase {


	// ****** test lock after getl timeout	 **** //

 	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Check_Timeout($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$timeout = 3 ;
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey, $timeout);
		sleep($timeout + 1);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");

	} 	
	
 	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Default_Timeout($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey);
		sleep(16);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");

	} 	

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Max_Timeout($testKey) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		$testValue = "testValue";
		
		$instance->set($testKey, $testValue);
		$instance->getl($testKey, 30);
		sleep(32);
		$success = $instance2->set($testKey, $testValue);
   		$this->assertTrue($success, "Memcache::set (positive)");

	} 

	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Greater_Than_Max_Timeout($testKey) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		$testValue = "testvalue";
		
		$instance->set($testKey, $testValue);
		$instance->getl($testKey, 60);
		sleep(17);						// For timeout values more 30, zbase converts it to 15 seconds
		$success = $instance2->set($testKey, $testValue);
   		$this->assertTrue($success, "Memcache::set (positive)");

	} 	
         /**
     * @dataProvider simpleKeyValueFlagProvider
     */
        public function test_Getl_Multiple_Request_Max_Timeout($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();

                $instance->set($testKey, $testValue, $testFlags);
                $instance->getl($testKey, 5);
                $start_time = time();
                $returnvalue = 0;
                while( $returnvalue != 1)
                {
                        $returnvalue = $instance2->set($testKey, $testValue, $testFlags);
                }
                $end_time = time() - $start_time;
                if (($end_time == 6) or ($end_time == 5))
                {
                        $success = True;
                }
                else
                {
                        $success = False;
                }
                $this->assertTrue($success, "Memcache::set (positive)");

        }
 	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Set_Timeout($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");

	} 
	
		/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Timeout_Getl($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
		sleep(GETL_TIMEOUT + 1);
   		$returnValue = $instance2->getl($testKey, 3, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		// release lock
		$instance2->set($testKey, $testValue);
	}
	
		/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Timeout_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		$instance->set($testKey, $testValue);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
   		$success = $instance2->delete($testKey);
		$this->assertTrue($success, "Memcache::delete (positive)");  		
   		 // verify key is not present	
   		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		
	} 
	
		/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Timeout_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		 
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
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
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Add_Getl_Timeout($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testValue1 = $testValue;
		
   		// positive add test 
   		$instance->add($testKey, $testValue1, $testFlags);
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags);
		sleep(GETL_TIMEOUT + 1);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
	}
	
	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Increment_Timeout($testKey, $testValue) {
   		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
		
   		$instance->set($testKey, $testValue1);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
   		$returnValue = $instance2->increment($testKey, $testValue1);
   		$this->assertEquals($returnValue, 2 * $testValue1,  "Memcache::increment (positive)");
	
	} 
	
	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_Decrement_Timeout($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
   		$testValue1 = strlen($testValue);
   		
   		$instance->set($testKey, $testValue1 * 2);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
   		$returnValue = $instance2->decrement($testKey, $testValue1);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::decrement (positive)");
		
	}
	
		/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Getl_SetTTL_Timeout($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$testTTL = 3;
		$testValue1 = serialize(array($testValue));
		
		$instance->set($testKey, $testValue1, $testFlags);
		$instance->getl($testKey, GETL_TIMEOUT);
		sleep(GETL_TIMEOUT + 1);
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


class Getl_TestCase_Full extends Getl_TestCase{

	public function simpleKeyValueFlagProvider() {
		return array(array(uniqid('key_'), uniqid('value_'), 0));
	}	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}

?>
