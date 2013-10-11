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
	
abstract class Append_Prepend_TestCase extends ZStore_TestCase {


	/**
     * @dataProvider keyValueProvider
    */
	public function test_Append_NonExistingValue($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
   		// negative Append test
   		$success = $instance->append($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::append (negative)");
	}

	/**
     * @dataProvider keyValueProvider
    */
	public function test_Append_Expired_Key($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		$instance->set($testKey, $testValue, 0, 2);
		sleep(3);
   		// negative Append test
   		$success = $instance->append($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::append (negative)");
		
		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}
		
	/**
     * @dataProvider keyValueProvider
    */
	public function test_Append_Deleted_Key($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		$instance->set($testKey, $testValue);
		$instance->delete($testKey);
   		// negative Append test
   		$success = $instance->append($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::append (negative)");
		
		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}
	
 	/**
     * @dataProvider keyValueProvider
     */
	public function test_Append($testKey, $testValue) {
		
		$instance = $this->sharedFixture;
		$testFlags = 0;
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
	
	/**
     * @dataProvider keyValueProvider
    */
	public function test_Prepend_NonExistingValue($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
   		// negative prepend test
   		$success = $instance->prepend($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
	}

	/**
     * @dataProvider keyValueProvider
    */
	public function test_Prepend_Expired_Key($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		$instance->set($testKey, $testValue, 0, 2);
		sleep(3);
   		// negative Prepend test
   		$success = $instance->prepend($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
		
		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}

	/**
     * @dataProvider keyValueProvider
    */
	public function test_Prepend_Deleted_Key($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		$instance->set($testKey, $testValue);
		$instance->delete($testKey);
   		// negative Prepend test
   		$success = $instance->prepend($testKey, $testValue);
   		$this->assertFalse($success, "Memcache::prepend (negative)");
		
		$returnValue = $instance->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
	}	
	
 	/**
     * @dataProvider keyValueProvider
     */
	public function test_Prepend($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$testFlags = 0;
		
		// positive prepend test
		$instance->set($testKey, $testValue, $testFlags);
		$success = $instance->prepend($testKey, "testValue");
   		$this->assertTrue($success, "Memcache::prepend (positive)");
		
		   // validate added value
   		$returnFlags = null;
   		$returnValue = $instance->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals("testValue".$testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
	} 
		
}


class Append_Prepend_TestCase_Quick extends Append_Prepend_TestCase{

	public function keyValueProvider() {
		return array(array("test_key", "test_value"));
	}
	
}

?>

