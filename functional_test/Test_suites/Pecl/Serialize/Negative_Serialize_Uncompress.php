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

//  Negative testcase to ensure php-pecl doesn't crash
	
abstract class Negative_Serialize_Compress extends ZStore_TestCase {

	/**
	 * @dataProvider keyValueFlagsProviderCompress		
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get_Uncompressed_Data_with_Flag_Compressed($testKey, $testValue, $testFlags) {
	
				// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);		
		
		$instance = $this->sharedFixture;
		$instance->get($testKey);

	}	

	/**
	 * @dataProvider keyValueFlagsProviderCompress	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get2_Uncompressed_Data_with_Flag_Compressed($testKey, $testValue, $testFlags) {

		// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);

		$instance = $this->sharedFixture;
		$instance->get2($testKey, $value);

	}	

	/**
	 * @dataProvider keyValueFlagsProviderCompress		
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Uncompressed_Data_with_Flag_Compressed($testKey, $testValue, $testFlags) {

				// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);
		
		$instance = $this->sharedFixture;
		$instance->getl($testKey);

	}
	
	/**
	 * @dataProvider keyValueFlagsProviderCompress		
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_MultiGet_Uncompressed_Data_with_Flag_Compressed($testKey, $testValue, $testFlags) {

		$testKey1 = $testKey.$testKey;
		$testKey2 = $testKey1.$testKey1;
		
		// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);
		
		$instance = $this->sharedFixture;

		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		$instance->get($getkey_list);

	}	

	/**
	 * @dataProvider keyValueFlagsProviderCompress		
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_MultiGet2_Uncompressed_Data_with_Flag_Compressed($testKey, $testValue, $testFlags) {

		$testKey1 = $testKey.$testKey;
		$testKey2 = $testKey1.$testKey1;
		
		// set one key with flag set to compressed
		Utility::netcat_execute($testkey, $testFlags, $testValue, TEST_HOST_1);
		
		$instance = $this->sharedFixture;

		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		$instance->get2($getkey_list, $value);

	}

	/**
	 * @dataProvider keyValueFlagsProviderCompress		
     */
	public function test_MultiGetl_Uncompressed_Data_with_Flag_Compressed($testKey, $testValue, $testFlags) {

		$testKey1 = $testKey.$testKey;
		$testKey2 = $testKey1.$testKey1;
		
		// set one key with flag set to compressed
		Utility::netcat_execute($testKey, $testFlags, $testValue, TEST_HOST_1);
		
		$instance = $this->sharedFixture;

		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		$instance->getl($getkey_list);

	}
	
	/**
	 * @dataProvider keyValueFlagsProviderSerialize
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get_Unserialized_Data_with_Flag_Set_Serialize($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags, $testFlags);
		$instance->get($testKey);

	}

	/**
	 * @dataProvider keyValueFlagsProviderSerialize
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Get2_Unserialized_Data_with_Flag_Set_Serialize($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags, $testFlags);
		$instance->replace($testKey, $testValue, $testFlags, $testFlags);
		$instance->get2($testKey, $value);

	}

	/**
	 * @dataProvider keyValueFlagsProviderSerialize
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_Getl_Unserialized_Data_with_Flag_Set_Serialize($testKey, $testValue, $testFlags) {

		$instance = $this->sharedFixture;
		$instance->add($testKey, $testValue, $testFlags, $testFlags);
		$instance->getl($testKey);

	}
	
	/**
	 * @dataProvider keyValueFlagsProviderSerialize	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_MultiGet_Unserialized_Data_with_Flag_Set_Serialize($testKey, $testValue, $testFlags) {

		$testKey1 = $testKey.$testKey;
		$testKey2 = $testKey1.$testKey1;
		
		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags, $testFlags);
		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		
		$instance->get($getkey_list);

	}

	/**
	 * @dataProvider keyValueFlagsProviderSerialize	
	 * @expectedException PHPUnit_Framework_Error
     */
	public function test_MultiGet2_Unserialized_Data_with_Flag_Set_Serialize($testKey, $testValue, $testFlags) {

		$testKey1 = $testKey.$testKey;
		$testKey2 = $testKey1.$testKey1;
		
		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags, $testFlags);
		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		
		$instance->get2($getkey_list, $value);

	}
	
	/**
	 * @dataProvider keyValueFlagsProviderSerialize	
     */
	public function test_MultiGetl_Unserialized_Data_with_Flag_Set_Serialize($testKey, $testValue, $testFlags) {

		$testKey1 = $testKey.$testKey;
		$testKey2 = $testKey1.$testKey1;
		
		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlags, $testFlags);
		$instance->set($testKey1, $testValue, $testFlags);
		$instance->set($testKey2, $testValue, $testFlags);
		$getkey_list = array($testKey, $testKey1, $testKey2);
		
		$instance->getl($getkey_list);

	}

		
}


class Negative_Serialize_Compress_Quick extends Negative_Serialize_Compress{

	public function keyValueFlagsProviderSerialize() {
		return array(array("test_key", "test_value", 1));
	}	
	public function keyValueFlagsProviderCompress() {
		return array(array("test_key", "test_value", 2));
	}
}

?>

