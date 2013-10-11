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
abstract class Non_existant_keys_test extends ZStore_TestCase {


//	non existant key

	/**
	* @dataProvider keyProvider
	*/
	public function test_NonExistantKey_get($testKey) {

		$instance = $this->sharedFixture;
		$instance->setLogName("Logger_non_existant_keys");	// need to include this for the first testcase
		// get non-existant key
		$instance->get($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_ONLY_END, $output["res_code"], "resp code");			
	}	 

	/**
	* @dataProvider keyProvider
	*/	
	public function est_NonExistantKey_multiget($testKey) { // no support for multiget 

		$testKey1 = "dummykey1";
		$testKey2 = "dummykey2";
		$testKey3 = "dummykey3";
		$instance = $this->sharedFixture;
		// get non-existant key
		$instance->get(array($testKey1, $testKey2, $testKey3));
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_ONLY_END, $output["res_code"], "resp code");			
	}

	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_getl($testKey) {

		$instance = $this->sharedFixture;
		//negative getl
		$instance->getl($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "resp code");				
	}	 

	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_unlock($testKey) {

		$instance = $this->sharedFixture;
		$instance->unlock($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(CMD_FAILED, $output["res_code"], "resp code");				
	}

	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_get2($testKey) {

		$instance = $this->sharedFixture;
		// get2 dummy key
		$instance->get2($testKey, $value);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_ONLY_END, $output["res_code"], "resp code");			
	}	 

	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_increment($testKey) {

		$instance = $this->sharedFixture;
		$instance->increment($testKey, 2);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "resp code");			
	}	

	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_decrement($testKey) {

		$instance = $this->sharedFixture;
		$instance->decrement($testKey, 2);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "resp code");			
	}
	
	/**
	* @dataProvider keyProvider
	*/
	public function test_NonExistantKey_replace($testKey) {

		$instance = $this->sharedFixture;
		$instance->replace($testKey, "replace");
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "resp code");			
	
	}		

	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_append($testKey) {

		$instance = $this->sharedFixture;
		$instance->append($testKey, "replace");
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "resp code");			
	
	}
	
	/**
	* @dataProvider keyProvider
	*/	
	public function test_NonExistantKey_prepend($testKey) {

		$instance = $this->sharedFixture;
		$instance->prepend($testKey, "replace");
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "resp code");			
	
	}	
	
	/**
	* @dataProvider keyProvider
	*/
	public function test_NonExistantKey_delete($testKey) {

		$instance = $this->sharedFixture;
		$instance->delete($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "resp code");			
	
	}	
	
}

class Non_existant_keys_Quick extends Non_existant_keys_test{
	public function keyProvider() {
		return array(array("dummykey"));
	}
}
?>

