<?php

abstract class Getl_Metadata_TestCase extends ZStore_TestCase {
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function est_Getl_Basic($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		$returnMetadata = null;
		
		//metadata = NULL
/*   		$returnFlags = null;
		$instance->set($testKey, $testValue, $testFlags);
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, null);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata );
		$this->assertFalse($returnValue, "Memcache::getl");
		$this->assertEquals($returnMetadata, null, "Memcache::getl metadata");
*/
		// metadata = 1024 string
		$metadata = "NETASDHGAF";
		//$metadata = METADATA_BIG;
		$instance->set($testKey, $testValue, $testFlags);
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		$returnMetadata = " dumm";

   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata );

		var_dump($returnValue);
		var_dump($metadata);
		var_dump($returnMetadata);
		sleep(30);
	
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags, METADATA_SMALL);
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata );
		var_dump($returnMetadata);
		echo "\nMeta\n";
		var_dump($metadata);
            exit;
		// metadata = 1024 string
		$metadata = METADATA_SMALL;
		//$instance->set($testKey, $testValue, $testFlags);
//   		sleep(GETL_TIMEOUT+5);
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata );
		sleep(1);
		var_dump($returnMetadata);
		echo "\nMeta\n";
		var_dump($metadata);
		$this->assertFalse($returnValue, "Memcache::getl");
		//$this->assertEquals($returnMetadata, $metadata, "Memcache::getl metadata");

		// metadata = int
		$metadata = 300;
		$instance->set($testKey, $testValue, $testFlags);
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata );
		$this->assertFalse($returnValue, "Memcache::getl");
		$this->assertEquals($returnMetadata, $metadata, "Memcache::getl metadata");


	}
	
 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function est_Getl_Set($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();


		$metadata = METADATA_BIG;
		// same client
		$instance->set($testKey, $testValue, $testFlags);
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
		$success = $instance->set($testKey, $testValue, $testFlags);
   		$this->assertTrue($success, "Memcache::set (positive)");
		
		// different client
		$metadata = METADATA_SMALL;
		$instance->set($testKey, $testValue, $testFlags);
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
		$success = $instance2->set($testKey, $testValue, $testFlags);
   		$this->assertFalse($success, "Memcache::set (negative)");
		
		//release lock
		$returnValue = $instance->unlock($testKey);
	} 
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function est_Getl_Getl($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		$metadata = METADATA_BIG;
		
		// same client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$returnMetadata = METADATA_DUMMY;
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
   		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
		$this->assertEquals($returnMetadata, $metadata, "Memcache::getl metadata");
		
		// different client
		$metadata = METADATA_SMALL;
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$returnMetadata = METADATA_DUMMY;
		$instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
   		$returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlagsi, $returnMetadata);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
		$this->assertEquals($returnMetadata, $metadata, "Memcache::getl metadata");
		
		//release lock
		$instance->set($testKey, $testValue);
	}
	
	/**
     * @dataProvider keyProvider
    */
	public function test_Getl_GetNonExistingValue($testKey) {
		
		$instance = $this->sharedFixture;
		$metadata = null;
		
		// negative get test
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
		$this->assertNull($metadata, "Memcache::get (negative)");
		
		//again
		$returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
		$this->assertFalse($returnValue, "Memcache::get (negative)");
		$this->assertNull($metadata, "Memcache::get (negative)");
	}
	
	

	/**
     * @dataProvider keyProvider
     */
	public function est_Getl_GetNullOnKeyMissBadConnection($testKey) {

		$instance = $this->sharedFixture;
		
		// bogus connection
		$testHost = Connection::getHost();
		$instance = new Memcache;
		@$instance->addServer("192.168.168.192");
		@$instance->setproperty("NullOnKeyMiss", true);
				
   		// validate added value
		$returnMetadata = null;
		$returnFlag = null;
   		$returnValue = @$instance->getl($testKey, 0, $returnFlag, $returnMetadata);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		$this->assertNull($returnMetadata, "Memcache::get metadata)");
	} 

       /**
     * @dataProvider keyValueFlagsProvider
     */
        public function est_Getl_Unlock($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();
		$metadata = METADATA_BIG;

                // same client
                $instance->set($testKey, $testValue, $testFlags);
                $returnFlags = null;
                $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
		$instance->unlock($testKey);

	
		//set a new metadata
		$metadata = METADATA_SMALL;
                $returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $metadata);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue, $returnValue, "Memcache::get (value)");

		//getl again from same client
		$returnMetadata = METADATA_DUMMY;
                $returnValue = $instance->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata);
		$this->assertFalse($returnValue, "Memcache::getl (return)");
                $this->assertEquals($metadata, $returnMetadata, "Memcache::get (flag)");

                // different client
		$returnMetadata = METADATA_DUMMY;
                $returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags, $returnMetadata);
		$this->assertFalse($returnValue, "Memcache::getl (return)");
                $this->assertEquals($metadata, $returnMetadata, "Memcache::get (flag)");

		//unlock different client
                $returnValue = $instance2->unlock($testKey);
		$this->assertFalse($returnValue, "Memcache::unlock (negative)");
		
                //release lock
                $returnValue = $instance->unlock($testKey);
		$this->assertTrue($returnValue, "Memcache::unlock (Positive)");
        }

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function est_Getl_Get($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		$metadata = METADATA_SMALL;

   		// same client
		$instance->set($testKey, $testValue, $testFlags);
   		$returnFlags = null;
		$instance->getl($testKey,GETL_TIMEOUT, $returnFlags, $metadata); 
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
		
		$metadata = METADATA_SMALL;
		$instance->set($testKey, $testValue, $testFlags);
        $instance->getl($testKey,GETL_TIMEOUT, $returnFlags, $metadata);
	
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
	public function est_Getl_Delete_Same_Client($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();

		// same client
		$instance->set($testKey, $testValue);
                $instance->getl($testKey,GETL_TIMEOUT, $returnFlags, $metadata);
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
	public function est_Getl_Delete_Different_Client($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		// different client
		$instance->set($testKey, $testValue);
                $instance->getl($testKey,GETL_TIMEOUT, $returnFlags, $metadata);

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
    * TODO
     */
	public function est_Getl_Replace($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
		$instance2 = Connection::getMaster();
		
		$metadata = METADATA_SMALL;
		$testValue1 = $testValue;
		$testValue2 = serialize(array($testValue));
		
   		// same client 
   		$instance->set($testKey, $testValue1);
                $instance->getl($testKey,GETL_TIMEOUT, $returnFlags, $metadata);

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
	public function est_MultiSet_30s($testKey, $testValue, $testFlags) {
		
		$instance = $this->sharedFixture;
                $nokeys=100;
                $data = Data_generation::prepareHugeData($nokeys);
		$expire = 30;


                $setMulti1 = array();
                $get_setMulti1= array();
                for ($i = 0; $i < $nokeys; $i++){
                        $setMulti1[$data[0][$i]] = array(
                                                        "value" => $data[1][$i],
                                                        "shardKey" => 2,
                                                        "flag" => $testFlags,
                                                        "cas" =>  0,
                                                        "expire" => 30
                                                         );

			$get_setMulti1[$data[0][$i]] = 2;
                }
//              print_r($setMulti1);

                $instance->setMultiByKey($setMulti1);
		sleep($expire);
		$result=$instance->getMultiByKey($get_setMulti1);

	}

}


class Getl_TestCase_Full extends Getl_Metadata_TestCase{
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
