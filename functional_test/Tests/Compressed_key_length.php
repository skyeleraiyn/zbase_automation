<?php

abstract class Compressed_key_length_TestCase extends ZStore_TestCase {
	
	
	/** 
    * @dataProvider KeyValueFlagsProvider
    */	
	public function test_set_length($testKey,$testValue,$testFlags){

		$instance=$this->sharedFixture;
		$testValue_length=0;
		
		$instance->set($testKey,$testValue,$testFlags,0,0,0,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length Set");
    }

	/** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_add_length($testKey,$testValue,$testFlags){	
	
		$instance=$this->sharedFixture;
		$testValue_length=0;

		$instance->add($testKey,$testValue,$testFlags,0,0,0,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length Add");

	}
	
	/** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_cas_length($testKey,$testValue,$testFlags){
	
		$instance=$this->sharedFixture;
		$testValue_length=0;
		$cas_value=0;
		
		$instance->set($testKey,$testValue,$testFlags);
		$instance->get($testKey,$testFlags,$cas_value);
		$instance->cas($testKey,$testValue,$testFlags,0,$cas_value,0,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length CAS");

	}
	
	 /** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_replace_length($testKey,$testValue,$testFlags){
	
		$instance = $this->sharedFixture;
		$testValue_length=0;
		
		$instance->set($testKey,$testValue,$testFlags);
		$instance->replace($testKey,$testValue,$testFlags,0,0,0,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length CAS");

	}
	
}


class Compressed_key_length_TestCase_Quick extends Compressed_key_length_TestCase{

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyAsciiValueFlags();
	}
	
}

?>

