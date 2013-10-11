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

	/** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_addbykey_length($testKey,$testValue,$testFlags){
	
		$instance = $this->sharedFixture;
		$testValue_length=0;
		$sharedkey1=1;
	
		$instance->addByKey($testKey,$testValue,$testFlags,0,0,$sharedkey1,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length AddByKey");

	}

	 /** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_setbykey_length($testKey,$testValue,$testFlags){
	
		$instance=$this->sharedFixture;
		$testValue_length=0;
		$sharedkey1=1;

		$instance->setByKey($testKey,$testValue,$testFlags,0,0,$sharedkey1,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length SetByKey");

	}

	 /** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_casbykey_length($testKey,$testValue,$testFlags)
	{
		$instance=$this->sharedFixture;
		$testValue_length=0;
		$cas_value = 0;
		$sharedkey2 = 2;
		
		$instance->setByKey($testKey,$testValue,$testFlags,0,0,$sharedkey2);
		$instance->getByKey($testKey,$sharedkey2,$testKeyval,$testFlags,$cas);
		$instance->casByKey($testKey,$testValue,$testFlags,0,0,$sharedkey2,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length CASByKey");

	}

	/** 
	* @dataProvider KeyValueFlagsProvider
	*/
	public function test_replacebykey_length($testKey,$testValue,$testFlags){
	
		$instance=$this->sharedFixture;
		$testValue_length=0;
		$sharedkey2=2;
		
		$instance->setByKey($testKey,$testValue,$testFlags,0,0,$sharedkey2);
		$instance->replaceByKey($testKey,$testValue,$testFlags,0,0,$sharedkey2,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		$this->assertGreaterThanorEqual($testValue_length,$compressed_value,"Compressed length ReplaceByKey");

	}

	/**
	* @dataProvider KeyValueFlagsProvider
	*/
	
	public function test_addmultibykey_length($testKey,$testValue,$testFlags)
	{
		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$testkey1="testkey1".$testKey;
		$testkey2="testkey2".$testKey;
		$testkey3="testkey3".$testKey;
		
		$setMulti = array(
					$testkey1 => array(
							"value" => $testValue,
							"shardKey" => $sharedkey1,
							"flag" => $testFlags,
							"cas" =>  0,
							"expire" => 0
							),
					$testkey2 => array(
							"value" => $testValue,
							"shardKey" => $sharedkey2,
							"flag" => $testFlags,
							"cas" =>  0,
							"expire" => 0
							),
					$testkey3 => array(
							"value" => $testValue,
							"shardKey" => $sharedkey3,
							"flag" => $testFlags,
							"cas" =>  0,
							"expire" => 0
							)
				);
		
		$instance->addMultiByKey($setMulti,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		foreach($testValue_length as $length){
			$this->assertGreaterThanorEqual($length,$compressed_value,"Compressed length AddMultiByKey");
		}
	}

	/**
	* @dataProvider KeyValueFlagsProvider
	*/

	public function test_setmultibykey_length($testKey,$testValue,$testFlags)
	{	
		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$testkey4="testkey4".$testKey;
		$testkey5="testkey5".$testKey;
		$testkey6="testkey6".$testKey;
		
		$setMulti1 = array(
						$testkey4 => array(
										"value" => $testValue,
										"shardKey" => $sharedkey1,
										"flag" => $testFlags,
										"cas" =>  0,
										"expire" => 0
										),
						$testkey5 => array(
										"value" => $testValue,
										"shardKey" => $sharedkey2,
										"flag" => $testFlags,
										"cas" =>  0,
										"expire" => 0
										),
						$testkey6 => array(
										"value" => $testValue,
										"shardKey" => $sharedkey3,
										"flag" => $testFlags,
										"cas" =>  0,
										"expire" => 0
										)
						);

		$instance->setMultiByKey($setMulti1,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		foreach($testValue_length as $length){
				$this->assertGreaterThanorEqual($length,$compressed_value,"Compressed length SetMultiByKey");
		}
	}

	/**
	* @dataProvider KeyValueFlagsProvider
	*/

	public function test_CASmultibykey_length($testKey,$testValue,$testFlags){
        
		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$testkey4="testkey4".$testKey;
		$testkey5="testkey5".$testKey;
		$testkey6="testkey6".$testKey;
		
		$setMulti1 = array(
						$testkey4 => array(
										"value" => $testValue,
										"shardKey" => $sharedkey1,
										"flag" => $testFlags,
										"cas" =>  0,
										"expire" => 0
										),
						$testkey5 => array(
										"value" => $testValue,
										"shardKey" => $sharedkey2,
										"flag" => $testFlags,
										"cas" =>  0,
										"expire" => 0
										),
						$testkey6 => array(
										"value" => $testValue,
										"shardKey" => $sharedkey3,
										"flag" => $testFlags,
										"cas" =>  0,
										"expire" => 0
										)
						);

		$instance->setMultiByKey($setMulti1);		
		$get_setMulti1=array( 	$testkey4 => $sharedkey1,
								$testkey5 => $sharedkey2,
								$testkey6 => $sharedkey3 );
		$result=$instance->getMultiByKey($get_setMulti1);

		$setMulti1 = array(	$testkey4 => array(	"value" => $testValue,
												"shardKey" => $sharedkey1,
												"flag" => $testFlags,
												"cas" =>  $result[$testkey4]["cas"],
												"expire" => 0
												),
							$testkey5 => array(
												"value" => $testValue,
												"shardKey" => $sharedkey2,
												"flag" => $testFlags,
												"cas" =>  $result[$testkey5]["cas"],
												"expire" => 0
												),
							$testkey6 => array(
												"value" => $testValue,
												"shardKey" => $sharedkey3,
												"flag" => $testFlags,
												"cas" => $result[$testkey6]["cas"],
												"expire" => 0
												)
						);

		$instance->casMultiByKey($setMulti1,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		foreach($testValue_length as $length){
			$this->assertGreaterThanorEqual($length,$compressed_value,"Compressed length CASMultiByKey");
		}
    }
	
	/**
	* @dataProvider KeyValueFlagsProvider
	*/

	public function test_replacemultibykey_length($testKey,$testValue,$testFlags){
		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$testkey4="testkey4".$testKey;
		$testkey5="testkey5".$testKey;
		$testkey6="testkey6".$testKey;
		
		$setMulti1 = array(
								$testkey4 => array(
												"value" => $testValue,
												"shardKey" => $sharedkey1,
												"flag" => $testFlags,
												"cas" =>  0,
												"expire" => 0
												),
								$testkey5 => array(
												"value" => $testValue,
												"shardKey" => $sharedkey2,
												"flag" => $testFlags,
												"cas" =>  0,
												"expire" => 0
												),
								$testkey6 => array(
												"value" => $testValue,
												"shardKey" => $sharedkey3,
												"flag" => $testFlags,
												"cas" =>  0,
												"expire" => 0
												)
						);

		$instance->replaceMultiByKey($setMulti1,$testValue_length);
		$compressed_value = Utility::check_compressed_length($testValue, $testFlags);
		foreach($testValue_length as $length){
			$this->assertGreaterThanorEqual($length,$compressed_value,"Compressed length ReplaceMultiByKey");
		}
	}
	
}


class Compressed_key_length_TestCase_Quick extends Compressed_key_length_TestCase{

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyAsciiValueFlags();
	}
	
}

?>

