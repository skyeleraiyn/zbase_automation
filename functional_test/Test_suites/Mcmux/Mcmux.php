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

abstract class Mcmux_TestCase extends ZStore_TestCase {
	
		
	public function test_long_multiget_mcmux(){
	// Test case for checking whether Mcmux works for long_multiget ( new default limit 32k)
	// added with mcmux version 1.0.3.4	
		$instance = $this->sharedFixture;
		
		$no_of_keys = 125;
		$key_size = 256;
		$getData = Data_generation::PrepareHugeData($no_of_keys, $key_size);
				
		$key_list = $getData[0];
		$value_list = $getData[1];
		
		for($key_count = 0 ; $key_count < 125 ; $key_count++){
			$instance->set($key_list[$key_count],$value_list[$key_count]);
		}
		$returnValue = $instance->get($key_list);
		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		
	}

	public function test_long_multiget2_mcmux(){
	// Test case for checking whether Mcmux works for long_multiget ( new default limit 32k)
	// added with mcmux version 1.0.3.4	
		$instance = $this->sharedFixture;
		
		$no_of_keys = 125;
		$key_size = 256;
		$getData = Data_generation::PrepareHugeData($no_of_keys, $key_size);
				
		$key_list = $getData[0];
		$value_list = $getData[1];
		
		for($key_count = 0 ; $key_count < 125 ; $key_count++){
			$instance->set($key_list[$key_count],$value_list[$key_count]);
		}
		$returnValue = $instance->get2($key_list, $get2output);
		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		
	}
	
}

class Mcmux_TestCase_Full extends Mcmux_TestCase{

	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}

?>
