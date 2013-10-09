<?php
require_once 'PHPUnit/Framework.php';
require_once 'Include/Utility.php';
//error_reporting ( E_WARNING );


abstract class Bykey_logger_TestCase extends ZStore_TestCase {
	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_setByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$instance->setLogName("ByKey_logger");

		$instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("setByKey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_setByKey_getByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;

		$instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("setByKey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

		$instance->getByKey($testKey,SHARDKEY1, $testValue, $cas);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("getBykey", trim($output["command"]), "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_casByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;

		$instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
		$instance->getByKey($testKey,SHARDKEY1, $testValue, $cas);
		$instance->casByKey($testKey, $testValue, $testFlag, 0, $cas, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("casByKey", trim($output["command"]), "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		//$this->assertEquals(MC_EXISTS, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_addByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;

		$instance->addByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("addByKey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_incrementByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$testValue1 = strlen(serialize($testValue));

		$instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARDKEY1);
		$instance->incrementByKey($testKey, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("incrementbykey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_decrementByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$testValue1 = strlen(serialize($testValue));

		$instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARDKEY1);
		$instance->decrementByKey($testKey, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("decrementbykey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_appendByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$testValue1 = "testValue1";
		$testValue2 = "testValue2";

		$instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARDKEY1);
		$instance->appendByKey($testKey, $testValue2, 0, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("appendByKey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_prependByKey($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$testValue1 = "testValue1";
		$testValue2 = "testValue2";

		$instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARDKEY1);
		$instance->prependByKey($testKey, $testValue2, 0, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("prependByKey", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

	}

}

class Logger_TestCase_Quick extends Bykey_logger_TestCase{

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}

	public function flagsProvider() {
		return Data_generation::provideFlags();
	}
}
?>
