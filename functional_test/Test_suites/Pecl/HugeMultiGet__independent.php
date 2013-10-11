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
abstract class HugeMultiGet_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider FlagsProvider
	*/
	public function test_Multiget_multi_non_existant($testFlags){

		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$nokeys=200;
		$data = Data_generation::PrepareHugeData($nokeys);

		$setMulti1 = array();
		$get_setMulti1= array();
		for ($i = 0; $i < $nokeys; $i++){
			$shard = $i%3;
			$setMulti1[$data[0][$i]] = array(
			"value" => $data[1][$i],
			"shardKey" => $shard,
			"flag" => $testFlags,
			"cas" =>  0,
			"expire" => 0
			);

			$get_setMulti1[$data[0][$i]] = $sharedkey1; //Bug 8448

		}

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);

		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}

	/**
	* @dataProvider FlagsProvider
	*/
	public function test_Multiget_multishard($testFlags){

		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$nokeys=200;
		$data = Data_generation::PrepareHugeData($nokeys);


		$setMulti1 = array();
		$get_setMulti1= array();
		for ($i = 0; $i < $nokeys; $i++){
			$shard = $i%3;
			$setMulti1[$data[0][$i]] = array(
			"value" => $data[1][$i],
			"shardKey" => $shard,
			"flag" => $testFlags,
			"cas" =>  0,
			"expire" => 0
			);

			$get_setMulti1[$data[0][$i]] = $shard;
			//$get_setMulti1[$data[0][$i]] = $sharedkey1; Bug 8448

		}

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);

		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}


	/**
	* @dataProvider FlagsProvider
	*/
	public function test_Multiget_oneshard_evictsome($testFlags){

		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$poolsize=3;
		$nokeys=200;
		$data = Data_generation::PrepareHugeData($nokeys);

		$setMulti1 = array();
		$get_setMulti1= array();
		for ($i = 0; $i < $nokeys; $i++){
			$setMulti1[$data[0][$i]] = array(
			"value" => $data[1][$i],
			"shardKey" => $sharedkey1,
			"flag" => $testFlags,
			"cas" =>  0,
			"expire" => 0
			);

			$get_setMulti1[$data[0][$i]] = $sharedkey1;

		}

		$destserverix = $sharedkey1 % $poolsize;
		for($i=0; $i < $nokeys; $i++){
			if ((((crc32($data[0][$i]) >> 16) & 0x7fff) % $poolsize) == $destserverix)
			//evict half
			if($i%2)
			flushctl_commands::evictKeyFromMemory(TEST_HOST_1, $data[0][$i], 0);
		}

		$instance->setMultiByKey($setMulti1);
		$result=$instance->getMultiByKey($get_setMulti1);

		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}
	}

	/**
	* @dataProvider FlagsProvider
	*/
	public function test_Multiget_oneshard_evictall($testFlags){

		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$poolSize=3;
		$nokeys=200;
		$data = Data_generation::PrepareHugeData($nokeys);

		$setMulti1 = array();
		$get_setMulti1= array();
		for ($i = 0; $i < $nokeys; $i++){
			$setMulti1[$data[0][$i]] = array(
			"value" => $data[1][$i],
			"shardKey" => $sharedkey1,
			"flag" => $testFlags,
			"cas" =>  0,
			"expire" => 0
			);

			$get_setMulti1[$data[0][$i]] = $sharedkey1;

		}

		$destserverix = $sharedkey1 % $poolSize;
		for($i=0; $i < $nokeys; $i++){
			if ((((crc32($data[0][$i]) >> 16) & 0x7fff) % $poolSize) == $destserverix)
			//TODO: get tchosen host n port from  pool
			flushctl_commands::evictKeyFromMemory(TEST_HOST_1, $data[0][$i], 0);
		}

		$instance->setMultiByKey($setMulti1);
		$result=$instance->getMultiByKey($get_setMulti1);
		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}


	/**
	* @dataProvider FlagsProvider
	*/
	public function test_Multiget_oneshard($testFlags){

		$instance = Connection::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$nokeys=200;
		$data = Data_generation::PrepareHugeData($nokeys);


		$setMulti1 = array();
		$get_setMulti1= array();
		for ($i = 0; $i < $nokeys; $i++){
			$setMulti1[$data[0][$i]] = array(
			"value" => $data[1][$i],
			"shardKey" => $sharedkey1,
			"flag" => $testFlags,
			"cas" =>  0,
			"expire" => 0
			);

			$get_setMulti1[$data[0][$i]] = $sharedkey1;

		}

		$instance->setMultiByKey($setMulti1);
		$result=$instance->getMultiByKey($get_setMulti1);

		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}
}

class HugeMultiGet_TestCase_Quick extends HugeMultiGet_TestCase{

	public function flagsProvider() {
		return Data_generation::provideFlags();
	}
}

?>



