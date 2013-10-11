/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php
	
abstract class Re_entrant_TestCase extends ZStore_TestCase {

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_set_set($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "set";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}
	
	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_set_cas($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "cas";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_set_replace($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "replace";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_set_add($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "add";
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_set_append($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "append";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], $testvalue);

		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_serialize", "Memcache::get testkey_sleep");
													
		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_unserialize", "Memcache::get testkey_wakekup");		
										
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_set_prepend($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "prepend";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "prependvalue_serializetestvalue", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "prependvalue_unserializetestvalue", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_cas_set($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "set";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], "testvalue");
		$instance->get($testKey[0], $returnFlags, $returnCAS);
		$instance->cas($testKey[0], $testvalue, 0, 0, $returnCAS);
			
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	


	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_cas_cas($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "cas";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$instance->set($testKey[0], "testvalue");
		$instance->get($testKey[0], $returnFlags, $returnCAS);
		$instance->cas($testKey[0], $testvalue, 0, 0, $returnCAS);
			
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_cas_add($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "add";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], "testvalue");
		$instance->get($testKey[0], $returnFlags, $returnCAS);
		$instance->cas($testKey[0], $testvalue, 0, 0, $returnCAS);
			
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	
	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_cas_replace($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "replace";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
				
		$instance->set($testKey[0], "testvalue");
		$instance->get($testKey[0], $returnFlags, $returnCAS);
		$instance->cas($testKey[0], $testvalue, 0, 0, $returnCAS);
			
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	
	
	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_cas_append($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "append";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
				
		$instance->set($testKey[0], "testvalue");
		$instance->get($testKey[0], $returnFlags, $returnCAS);
		$instance->cas($testKey[0], $testvalue, 0, 0, $returnCAS);
			
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_cas_prepend($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "prepend";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");	
		
		$instance->set($testKey[0], "testvalue");
		$instance->get($testKey[0], $returnFlags, $returnCAS);
		$instance->cas($testKey[0], $testvalue, 0, 0, $returnCAS);
			
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "prependvalue_serializetestvalue", "Memcache::get testkey_sleep");
	
		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "prependvalue_unserializetestvalue", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_add_set($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "set";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->add($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_add_cas($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "cas";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->add($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_add_replace($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "replace";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->add($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_add_add($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "add";
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->add($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_add_append($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "append";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->add($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_serialize", "Memcache::get testkey_sleep");
											
		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_unserialize", "Memcache::get testkey_wakekup");		
										
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_add_prepend($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "prepend";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->add($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "prependvalue_serializetestvalue", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "prependvalue_unserializetestvalue", "Memcache::get testkey_wakekup");		
		
	}	

// replace
	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_replace_set($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "set";
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], "testvalue");
		$instance->replace($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_replace_cas($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "cas";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], "testvalue");
		$instance->replace($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_replace_replace($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "replace";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], "testvalue");
		$instance->replace($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_replace_add($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "add";
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		
		$instance->set($testKey[0], "testvalue");
		$instance->replace($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_serialize", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, $operation_in_blob."value_unserialize", "Memcache::get testkey_wakekup");		
		
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_replace_append($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "append";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		$instance->set($testKey[0], "testvalue");
		$instance->replace($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_serialize", "Memcache::get testkey_sleep");
											
		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "testvalueappendvalue_unserialize", "Memcache::get testkey_wakekup");		
										
	}	

	/**
     * @dataProvider keyProvider
    */
	public function test_reentrant_replace_prepend($testKey){
	
		$instance = $this->sharedFixture;
		$operation_in_blob = "prepend";
		$instance->set($testKey[1], "testvalue");
		$instance->set($testKey[2], "testvalue");
		
		$testvalue = new Blob_Object_Serialize_Unserialize($operation_in_blob);
		$instance->set($testKey[0], "testvalue");
		$instance->replace($testKey[0], $testvalue);
		$success = $instance->get2($testKey[0], $returnvalue);
		$this->assertTrue(is_object($returnvalue), "Memcache::get (value)");
		$this->assertEquals($returnvalue->value, $operation_in_blob, "Memcache::get test_key");

		$success = $instance->get2($testKey[1], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_sleep is object");
		$this->assertEquals($returnvalue, "prependvalue_serializetestvalue", "Memcache::get testkey_sleep");

		$success = $instance->get2($testKey[2], $returnvalue);
		$this->assertFalse(is_object($returnvalue), "Memcache:: testkey_wakeup is object");
		$this->assertEquals($returnvalue, "prependvalue_unserializetestvalue", "Memcache::get testkey_wakekup");		
		
	}	

}


class Re_entrant_TestCase_Quick extends Re_entrant_TestCase{

	public function keyProvider() {
			// The below keys are hard coded in Blob_Object_Serialize_Unserialize class under Data_generation
		return array(array(array("test_key", "testkey_sleep", "testkey_wakeup")));
	}
	
}

?>

