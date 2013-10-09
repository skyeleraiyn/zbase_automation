<?php

static $SHARDS = array("SHARDKEY2", "SHARD2", "SHARD3"); //These map to the 3 hosts
abstract class Getl_TestCase extends ZStore_TestCase {

	public function _setUp() {
		$instance = Connection::getServerPool();
		@$instance->setByKey($this->data[0][0], array("sdfds"), $this->data[2], 0, 0, $GLOBALS['SHARDS'][0]);
		@$instance->setByKey($this->data[0][1], array("sdfds"), $this->data[2], 0, 0, $GLOBALS['SHARDS'][1]);
		@$instance->setByKey($this->data[0][2], array("sdfds"), $this->data[2], 0, 0, $GLOBALS['SHARDS'][2]);

		$ret = @$instance->deleteByKey($this->data[0][0],$GLOBALS['SHARDS'][0], 0);
		$ret = @$instance->deleteByKey($this->data[0][1],$GLOBALS['SHARDS'][1], 0);
		$ret = @$instance->deleteByKey($this->data[0][2],$GLOBALS['SHARDS'][2], 0);
	}

	private function makeGetlInput($atestKey, $atestValue, $testFlags, $opts, $shardArray=null) {

		if (!$shardArray)
		$shardArray = $GLOBALS['SHARDS'];

		$keyArr = array();
		switch($opts) {
		case Data_Generation::$GetlInputs['GetlFormatBasic']:
			$keyArr[$atestKey[0]] = $shardArray[0];
			$keyArr[$atestKey[1]] = $shardArray[1];
			$keyArr[$atestKey[2]] = $shardArray[2];
			break;

		case Data_Generation::$GetlInputs["GetlNoTimeout"]:
			$keyArr = array(
			$atestKey[0] => array(
			'shardKey' =>$shardArray[0],
			'lockMetaData' => METADATA,
			),
			$atestKey[1] => array(
			'shardKey' =>$shardArray[1],
			'lockMetaData' => METADATA,
			),
			$atestKey[2] => array(
			'shardKey' =>$shardArray[2],
			'lockMetaData' => METADATA,
			)
			);
			break;
		case Data_Generation::$GetlInputs["GetlNoMetadata"]:
			$keyArr = array(
			$atestKey[0] => array(
			'shardKey' =>$shardArray[0],
			'timeout' => GETL_TIMEOUT,
			),
			$atestKey[1] => array(
			'shardKey' =>$shardArray[1],
			'timeout' => GETL_TIMEOUT,
			),
			$atestKey[2] => array(
			'shardKey' =>$shardArray[2],
			'timeout' => GETL_TIMEOUT,
			)
			);
			break;
		case Data_Generation::$GetlInputs["GetlFormatFull"]:
			$keyArr = array(
			$atestKey[0] => array(
			'shardKey' =>$shardArray[0],
			'lockMetaData' => METADATA,
			'timeout' => GETL_TIMEOUT,
			),
			$atestKey[1] => array(
			'shardKey' =>$shardArray[1],
			'lockMetaData' => METADATA,
			'timeout' => GETL_TIMEOUT,
			),
			$atestKey[2] => array(
			'shardKey' =>$shardArray[2],
			'lockMetaData' => METADATA,
			'timeout' => GETL_TIMEOUT,
			)
			);
			break;

		}
		return $keyArr;
	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_NotFound($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		foreach( $atestKey as $k) {
			$this->assertFalse($ret[$k], "GetlByKey (returnValue) ");
			$this->assertEquals(0, count($vals));
		}
	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_PartFound($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, 0, 0, $GLOBALS['SHARDS'][0]);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) ");
		$this->assertEquals($atestValue[0], $vals[$atestKey[0]], "GetlByKey (returnValue) ");

		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) ");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) ");

		self::clearKeys($instance,$atestKey);
	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_Unreacheable($atestKey, $atestValue, $testFlags, $opts) {

		$instance = new Memcache;
		$instance->addServer(TEST_HOST_1);
		$instance->addServer("192.168.1.1");

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = @$instance->getlMultiByKey($in, $vals);

		foreach( $atestKey as $k) {
			$this->assertFalse($ret[$k], "GetlByKey (returnValue) ");
			$this->assertEquals(0, count($vals));
		}
		self::clearKeys($instance,$atestKey);
	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_GetlByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);

		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$new_in = json_decode(str_replace(METADATA, "garbage", json_encode($in)), true);
		$f=null; $m = null;
		$ret = $instance->getlMultiByKey($new_in, $vals, $f, $m);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Negative");

		$this->assertEquals(count($vals), 0,  "GetlByKey (Value) Negative");

		if (strstr( "lockMetadata", json_encode($new_in))){
			$this->assertEquals(METADATA, $m[$atestKey[0]], "GetlByKey (Metadata) LockMetadata");
			$this->assertEquals(METADATA, $m[$atestKey[1]], "GetlByKey (Metadata) LockMetadata");
			$this->assertEquals(METADATA, $m[$atestKey[2]], "GetlByKey (Metadata) LockMetadata");
		}


		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyOptsProvider
	*/
	public function test_GetlByKey_LockExpire_GetlByKey($atestKey, $opts) {

		$atestValue = array( array("Value"), array("Value"), array("Value"));
		$testFlags = 0;
		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 300);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);

		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$f=null; $m = null;

		if(strstr(json_encode($in), "timeout")){
			sleep(GETL_TIMEOUT + 1);
		}else{
			sleep(16); //timeout not provided. Lock will be held for default timeout - 15s
		}
		$instance->unlockMultiByKey($getmbk);
		$ret = $instance->getlMultiByKey($in, $vals, $f, $m);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_GetlByKey_Different_Client($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$instance2 = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);

		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$new_in = json_decode(str_replace(METADATA, "garbage", json_encode($in)));
		$f=null; $m = null;
		$ret = $instance2->getlMultiByKey($in, $vals, $f, $m);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Negative");

		$this->assertEquals(count($vals), 0,  "GetlByKey (Value) Negative");

		if (strstr("lockMetadata", json_encode($in))){
			$this->assertEquals(METADATA, $m[$atestKey[0]], "GetlByKey (Metadata) LockMetadata");
			$this->assertEquals(METADATA, $m[$atestKey[1]], "GetlByKey (Metadata) LockMetadata");
			$this->assertEquals(METADATA, $m[$atestKey[2]], "GetlByKey (Metadata) LockMetadata");
		}

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function st_GetlByKey_Set($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$i++;
		}


		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;

		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Negative");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Negative");

		//$instance->setMultiByKey($keys);
		//Do a set on the appropriate shard
		$ret = self::getInstanceForShardKey($GLOBALS['SHARDS'][0])->set($atestKey[0], $atestValue[0]);
		$this->assertTrue($ret, "Set (returnCode)");
		$ret = self::getInstanceForShardKey($GLOBALS['SHARDS'][1])->set($atestKey[1], $atestValue[1]);
		$this->assertTrue($ret, "Set (returnCode)");
		$ret = self::getInstanceForShardKey($GLOBALS['SHARDS'][2])->set($atestKey[2], $atestValue[2]);
		$this->assertTrue($ret, "Set (returnCode)");

		$ret = $instance->getlMultiByKey($in, $vals, $f, $m);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_SetMultiByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;


		}

		$instance->setMultiByKey($keys);

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");
		$ret = $instance->setMultiByKey($keys);
		$this->assertTrue($ret[$atestKey[0]], "setMultiByKey (returnStatus) Negative");
		$this->assertTrue($ret[$atestKey[1]], "setMultiByKey (returnStatus) Negative");
		$this->assertTrue($ret[$atestKey[2]], "setMultiByKey (returnStatus) Negative");

		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $keys[$atestKey[0]]['value'], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $keys[$atestKey[1]]['value'], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $keys[$atestKey[2]]['value'], "setMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}    

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_SetMultiByKey_Different_Client($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$instance2 = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$i++;

		}

		$instance->setMultiByKey($keys);

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");

		$ret = $instance2->setMultiByKey($keys);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Negative");

		self::clearKeys($instance, $atestKey);

	}    


	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_SetByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);

		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");        
		$ret = $instance->setByKey($atestKey[0], $keys[$atestKey[0]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][0]);
		$this->assertTrue($ret, "SetByKey (returnVal)");
		$ret = $instance->setByKey($atestKey[1], $keys[$atestKey[1]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][1]);
		$this->assertTrue($ret, "SetByKey (returnVal)");
		$ret = $instance->setByKey($atestKey[2], $keys[$atestKey[2]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][2]);
		$this->assertTrue($ret, "SetByKey (returnVal)");

		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $keys[$atestKey[0]]['value'], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $keys[$atestKey[1]]['value'], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $keys[$atestKey[2]]['value'], "setMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider 
	*/
	public function test_GetlByKey_SetByKey_Different_Client($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$instance2 = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);

		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");        
		$ret = $instance2->setByKey($atestKey[0], $keys[$atestKey[0]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][0]);
		$this->assertFalse($ret, "SetByKey (returnVal)");
		$ret = $instance2->setByKey($atestKey[1], $keys[$atestKey[1]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][1]);
		$this->assertFalse($ret, "SetByKey (returnVal)");
		$ret = $instance2->setByKey($atestKey[2], $keys[$atestKey[2]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][2]);
		$this->assertFalse($ret, "SetByKey (returnVal)");

		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $atestValue[0], "getMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $atestValue[1], "getMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $atestValue[2], "getMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider 
	*/
	public function test_GetlByKey_ReplaceByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);

		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");        
		$ret = $instance->replaceByKey($atestKey[0], $keys[$atestKey[0]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][0]);
		$this->assertFalse($ret, "replaceByKey (returnVal)");
		$ret = $instance->replaceByKey($atestKey[1], $keys[$atestKey[1]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][1]);
		$this->assertFalse($ret, "replaceByKey (returnVal)");
		$ret = $instance->replaceByKey($atestKey[2], $keys[$atestKey[2]]['value'], $testFlags, 0, 0, $GLOBALS['SHARDS'][2]);
		$this->assertFalse($ret, "replaceByKey (returnVal)");
		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $atestValue[0], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $atestValue[1], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $atestValue[2], "setMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_ReplaceMultiByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);        
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");        
		$ret = $instance->replaceMultiByKey($keys);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Negative");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Negative");


		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $atestValue[0], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $atestValue[1], "setMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $atestValue[2], "setMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_CasMbk_Cas2Mbk($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		//Hack to get the getl cas value. Problem is that after a getl, the cas value is temporarilly made -1. Also getl itself doesnt return 
		//the cas value. Solution is to get the cas previous to getl and increment by 1
		$get_ret = $instance->getMultiByKey($getmbk);        

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);        
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		// set the correct cas
		foreach($get_ret as $k => $v){
			$keys[$k]["cas"] = $v["cas"] + 1;
		}
		//set a different value
		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");        

		$ret = $instance->casMultiByKey($keys);

		$this->assertTrue($ret[$atestKey[0]], "casMultiByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "casMultiByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "casMultiByKey (returnStatus) Positive");

		// CasMultiByKey doesnt return updates cas value. Uncomment when it does
		// $this->assertEquals($keys[$atestKey[0]]['cas'], $get_ret[$atestKey[0]]['cas'] +2, "casMultiByKey (cas) Positive");
		// $this->assertEquals($keys[$atestKey[1]]['cas'], $get_ret[$atestKey[1]]['cas'] +2, "casMultiByKey (cas) Positive");
		// $this->assertEquals($keys[$atestKey[2]]['cas'], $get_ret[$atestKey[2]]['cas'] +2, "casMultiByKey (cas) Positive");

		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $keys[$atestKey[0]]['value'], "getMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $keys[$atestKey[0]]['value'], "getMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $keys[$atestKey[0]]['value'], "getMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}  

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_CasBk_Cas2Bk($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);
		//Hack to get the getl cas value. Problem is that after a getl, the cas value is temporarilly made -1. Also getl itself doesnt return 
		//the cas value. Solution is to get the cas previous to getl and increment by 1
		$get_ret = $instance->getMultiByKey($getmbk);        

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);        
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		// set the correct cas
		foreach($get_ret as $k => $v){
			$keys[$k]["cas"] = $v["cas"] + 1;
		}
		//set a different value
		$keys[$atestKey[0]]['value'] = array("NewValue");
		$keys[$atestKey[1]]['value'] = array("NewValue");
		$keys[$atestKey[2]]['value'] = array("NewValue");        

		$ret = $instance->casByKey($atestKey[0], $keys[$atestKey[0]]['value'], $testFlags, 0, $keys[$atestKey[0]]["cas"], $GLOBALS['SHARDS'][0]);
		$this->assertTrue($ret, "casByKey (returnStatus) Positive");
		$ret = $instance->casByKey($atestKey[1], $keys[$atestKey[1]]['value'], $testFlags, 0, $keys[$atestKey[1]]["cas"], $GLOBALS['SHARDS'][1]);
		$this->assertTrue($ret, "casByKey (returnStatus) Positive");
		$ret = $instance->casByKey($atestKey[2], $keys[$atestKey[1]]['value'], $testFlags, 0, $keys[$atestKey[2]]["cas"], $GLOBALS['SHARDS'][2]);
		$this->assertTrue($ret, "casByKey (returnStatus) Positive");

		// CasMultiByKey doesnt return updates cas value. Uncomment when it does
		// $this->assertEquals($keys[$atestKey[0]]['cas'], $get_ret[$atestKey[0]]['cas'] +2, "casMultiByKey (cas) Positive");
		// $this->assertEquals($keys[$atestKey[1]]['cas'], $get_ret[$atestKey[1]]['cas'] +2, "casMultiByKey (cas) Positive");
		// $this->assertEquals($keys[$atestKey[2]]['cas'], $get_ret[$atestKey[2]]['cas'] +2, "casMultiByKey (cas) Positive");

		$ret = $instance->getMultiByKey($getmbk);
		$this->assertEquals($ret[$atestKey[0]]['value'], $keys[$atestKey[0]]['value'], "getMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[1]]['value'], $keys[$atestKey[0]]['value'], "getMultiByKey (value)");
		$this->assertEquals($ret[$atestKey[2]]['value'], $keys[$atestKey[0]]['value'], "getMultiByKey (value)");

		self::clearKeys($instance, $atestKey);

	}  


	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_DeleteByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$ret = @$instance->deleteByKey($atestKey[0],$GLOBALS['SHARDS'][0], 0);
		$this->assertFalse($ret, "deleteByKey (returnVal)");
		$ret = @$instance->deleteByKey($atestKey[1],$GLOBALS['SHARDS'][1], 0);
		$this->assertFalse($ret, "deleteByKey (returnVal)");
		$ret = @$instance->deleteByKey($atestKey[2],$GLOBALS['SHARDS'][2], 0);
		$this->assertFalse($ret, "deleteByKey (returnVal)");

		self::clearKeys($instance, $atestKey);

	}


	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_DeleteMultiByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$ret = @$instance->deleteMultiByKey($getmbk);
		$this->assertFalse($ret[$atestKey[0]], "deleteByKey (returnVal)");
		$this->assertFalse($ret[$atestKey[1]], "deleteByKey (returnVal)");
		$this->assertFalse($ret[$atestKey[2]], "deleteByKey (returnVal)");

		self::clearKeys($instance, $atestKey);

	}


	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider 
	*/
	public function test_GetlByKey_UnlockByKey_GetlByKey($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		//var_dump($in);
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$ret = $instance->unlockMultiByKey($getmbk);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");        

		self::clearKeys($instance, $atestKey);

	}

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_UnlockByKey_Different_Client($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$instance2 = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		$instance->setMultiByKey($keys);

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$ret = $instance2->unlockMultiByKey($getmbk);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		self::clearKeys($instance, $atestKey);

	}    

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_UnlockByKey_Negative($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();

		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$i++;

		}

		// unlock when keys are not present
		$ret = $instance->unlockMultiByKey($getmbk);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		$instance->setMultiByKey($keys);

		// unlock with keys not locked
		$ret = $instance->unlockMultiByKey($getmbk);
		$this->assertFalse($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertFalse($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertFalse($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		self::clearKeys($instance, $atestKey);

	}   

	/**
	* @dataProvider ArrayKeyArrayValueFlagsOptsProvider
	*/
	public function test_GetlByKey_UnlockByKey_WithCas($atestKey, $atestValue, $testFlags, $opts) {

		$instance = Connection::getServerPool();
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => $atestValue[$i],
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => 30);
			$getmbk[$atestKey[$i]] = $GLOBALS['SHARDS'][$i];
			$unlockmbk[$atestKey[$i]] = array(
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"cas" =>0);
			$i++;

		}

		$instance->setMultiByKey($keys);
		//Hack to get the getl cas value. Problem is that after a getl, the cas value is temporarilly made -1. Also getl itself doesnt return 
		//the cas value. Solution is to get the cas previous to getl and increment by 1
		$get_ret = $instance->getMultiByKey($getmbk);        

		$in = self::makeGetlInput($atestKey, $atestValue, $testFlags, $opts);
		$vals = null;
		$ret = $instance->getlMultiByKey($in, $vals);        
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		// set the correct cas
		foreach($get_ret as $k => $v){
			$unlockmbk[$k]["cas"] = $v["cas"] + 1;
		}

		$ret = $instance->unlockMultiByKey($unlockmbk);
		$this->assertTrue($ret[$atestKey[0]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[1]], "GetlByKey (returnStatus) Positive");
		$this->assertTrue($ret[$atestKey[2]], "GetlByKey (returnStatus) Positive");

		self::clearKeys($instance, $atestKey);        
	}

	private function clearKeys($instance,$atestKey){
		$i=0;
		while( $i < count($atestKey)) {
			$keys[$atestKey[$i]] = array(
			"value" => "v",
			"shardKey" => $GLOBALS['SHARDS'][$i],
			"flag" => 0,
			// "cas" => 0,
			"expire" => 30);
			$i++;
		}

		$deletekeys = array(
		$atestKey[0] => $GLOBALS['SHARDS'][0],
		$atestKey[1] => $GLOBALS['SHARDS'][1],
		$atestKey[2] => $GLOBALS['SHARDS'][2],
		);
		$instance->setMultiByKey($keys);
		@$instance->deleteMultiByKey($deletekeys);
	}

	private function getInstanceForShardKey($shardKey) {
		$hash = crc32($shardKey);
		$shift = ($hash >> 16) & 0x7fff;
		$serverIndex = $shift % 3;

		switch($serverIndex) {
		case 0: return Connection::getMaster();
		case 1: return Connection::getSlave();
		case 2: return Connection::getSlave2();
		}
	}

}


class Getl_TestCase_Full extends Getl_TestCase{

	public function flagsProvider() {
		return Data_generation::provideFlags(); 
	}

	public function ArrayKeyArrayValueFlagsOptsProvider() {
		return Data_generation::provideArrayKeyArrayValueFlagsOpts();
	}

	public function ArrayKeyOptsProvider() {
		return Data_generation::provideArrayKeyOpts();
	}
}

?>
