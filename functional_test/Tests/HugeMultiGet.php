<?php

abstract class HugeMultiGet_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider FlagsProvider
	*/

	public function est_Multiget_multi_non_existant($testFlags){

		$instance = Utility::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$nokeys=500;
		$data = Utility::prepareHugeData($nokeys);


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
		//              print_r($setMulti1);

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);
		echo "Time = " .($end - $start). "\n";

		print_r($result);
		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}

	/**
* @dataProvider FlagsProvider
*/

	public function est_Multiget_multishard($testFlags){

		$instance = Utility::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$nokeys=500;
		$data = Utility::prepareHugeData($nokeys);


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
		//		print_r($setMulti1);

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);
		echo "Time = " .($end - $start). "\n";

		//		print_r($result);
		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}


	/**
* @dataProvider FlagsProvider
*/

	public function test_Multiget_oneshard_evictsome($testFlags){

		$instance = Utility::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$poolsize=3;
		$nokeys=500;
		$data = Utility::prepareHugeData($nokeys);


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
			//TODO: get tchosen host n port from  pool
			//evict half
			if($i%2)
			@shell_exec($GLOBALS['flushctl_path']." ".$GLOBALS['testHost'].":".$GLOBALS['testHostPort']." evict ".$data[0][$i]. " > /dev/null");
		}
		//evict;

		//              print_r($setMulti1);

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);
		echo "Time = " .($end - $start). "\n";

		//                print_r($result);
		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}
	}

	/**
* @dataProvider FlagsProvider
*/

	public function est_Multiget_oneshard_evictall($testFlags){

		$instance = Utility::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$poolsize=3;
		$nokeys=500;
		$data = Utility::prepareHugeData($nokeys);


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

		$destserverix = $shardkey1 % $poolsize;
		for($i=0; $i < $nokeys; $i++){
			if ((((crc32($data[0][$i]) >> 16) & 0x7fff) % $poolSize) == $destserverix)
			//TODO: get tchosen host n port from  pool
			shell_exec($GLOBALS['flushctl_path']." ".$GLOBALS['testHost'].":".$GLOBALS['testHostPort']." evict ".$Key);
		}
		//evict;

		//              print_r($setMulti1);

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);
		echo "Time = " .($end - $start). "\n";

		print_r($result);
		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}


	/**
* @dataProvider FlagsProvider
*/

	public function est_Multiget_oneshard($testFlags){

		$instance = Utility::getServerPool();
		$testValue_length=0;
		$sharedkey1=1;
		$sharedkey2=2;
		$sharedkey3=3;
		$nokeys=500;
		$data = Utility::prepareHugeData($nokeys);


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
		//		print_r($setMulti1);

		$instance->setMultiByKey($setMulti1);
		$start = microtime(true);
		$result=$instance->getMultiByKey($get_setMulti1);
		$end = microtime(true);
		echo "Time = " .($end - $start). "\n";

		print_r($result);
		foreach($result as $r){
			$this->assertEquals($r['status'], true , "Failed");
		}

	}
}

class HugeMultiGet_TestCase_Quick extends HugeMultiGet_TestCase {

	public function flagsProvider() {
		return Utility::provideFlags();
	}
}

?>



