<?php

abstract class Logger_TestCase extends ZStore_TestCase {

	/**
     * @dataProvider keyValuearrayProvider
     */
	public function test_Get_MultiKey_large($getData) {
		$instance = Connection::getMaster();
		$keys = array();
		$getkeys = array();
		for($i=0; $i<$noKeys; $i++){

			$keys[$getData[0][$i]] = array(	
				"value" => $getData[1][$i],
				"shardKey" => SHARDKEY2,
				"flag" => 0,
				"cas" => 0,
				"expire" => 1209600
			);
			$getkeys[$getData[0][$i]] = SHARDKEY2;
		}

		$instance->setMultiByKey($keys);
		$return_array = $instance->getMultiBykey($getkeys);


		$success = True;
		$errcnt=0;
		$printmessage =  "Memcache::getmulti Large (positive)";

		foreach ($keys as $getKey =>$val){
			if(!(	$val["value"] == $return_array[$getKey]["value"])) {
				$success = false;
				$errcnt ++;
			}
		}
		$printmessage = $printmessage . $errcnt;
		$this->assertTrue(false, $printmessage);
	}

	/**
     * @dataProvider keyValuearrayProvider
     */
	public function test_Get_MultiKey_large_evict($getData) {
		$instance = Connection::getMaster();
		$keys = array();
		$getkeys = array();
		for($i=0; $i<$noKeys; $i++){

			$keys[$getData[0][$i]] = array(	
				"value" => $getData[1][$i],
				"shardKey" => SHARDKEY2,
				"flag" => $testFlags,
				"cas" => 0,
				"expire" => 1209600
			);
			$getkeys[$getData[0][$i]] = SHARDKEY2;
		}

		$instance->setMultiByKey($keys);
		Utility::EvictKeyFromMemory_Master_Server($getData[0]);	
		$return_array = $instance->getMultiBykey($getkeys);

		$success = True;
		$errcnt=0;
		$printmessage =  "Memcache::getmulti Large (positive) ";

		foreach ($keys as $getKey =>$val){
			if(!(	$keys[$getKey]["value"] == $return_array[$getKey]["value"])) {
				$success = false;
				$errcnt ++;
			}
		}
		$printmessage = $printmessage . $errcnt;
		$this->assertTrue(false, $printmessage);
	}

}

class Logger_TestCase_Quick extends Logger_TestCase{
	public function keyValuearrayProvider() {
		return Data_generation::PrepareHugeData(10);
	}
}
?>

