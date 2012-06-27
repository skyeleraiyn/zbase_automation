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
			$instance->set($keys_list[$key_count],$value_list[$key_count]);
		}
		$returnValue = $instance->get($keys_list);
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
			$instance->set($keys_list[$key_count],$value_list[$key_count]);
		}
		$returnValue = $instance->get2($keys_list, $get2output);
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
