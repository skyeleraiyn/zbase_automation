<?php

abstract class ApacheLog_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_All_Sets($testKey, $testValue, $testFlag) {

		$set_time = 0;
		$pid = pcntl_fork();
		if ($pid == -1) {
			die('could not fork');
		} else if ($pid) {
			pcntl_wait($status); 
			$ap_output = Utility::parseApacheLogFile_temppath();
		} else {
			$set_time = All_Sets($testKey, $testValue, $testFlag);
		}
	}

	public function All_Sets($testKey, $testValue, $testFlag) {

		$settime = 0;
		$numValue = 100;
		$requestid = 0;

		$instance->add($testKey, $testValue, $testFlag, $expire);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$requestid = $output["requestid"];

		$instance->replace($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->set($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->append($testKey, $testValue,0,$expire);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->prepend($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->replace($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->set($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->increment($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->decrement($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->casByKey($testKey, $testValue, $testFlag, 0, $cas, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->addByKey($testKey, $testValue, $testFlag, 0, 0, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->appendByKey($testKey, $testValue2, 0, 0, 0, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->prependByKey($testKey, $testValue2, 0, 0, 0, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");



		$instance->setByKey($testKey, $numValue, $testFlag, 0, 0, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->incrementByKey($testKey, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

		$instance->decrementByKey($testKey, SHARD);
		$output = Utility::parseLoggerFile_temppath();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["res_time"], "Request id");

	}
}

class ApacheLog_TestCase_Quick extends ApacheLog_TestCase{


	public function keyValueFlagsProvider() {
		return Utility::provideKeyValueFlags();
	}

	public function flagsProvider() {
		return Utility::provideFlags();
	}
}
?>


