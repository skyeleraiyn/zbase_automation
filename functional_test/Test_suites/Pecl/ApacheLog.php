<?php
require_once 'PHPUnit/Framework.php';
require_once 'Include/Utility.php';
//error_reporting ( E_WARNING );

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
			pcntl_wait($status, WNOHANG); 
		} else {
			$set_time = self::All_Sets($testKey, $testValue, $testFlag);
			exit;
		}
	}

	public function All_Sets($testKey, $testValue, $testFlag) {

		$instance = Connection::getMaster();
		$instance->setLogName("LogToSyslog");
		$settime = 0;
		$numValue = 100;
		$requestid = 0;
		$expire = 0;
		$cas = 0;

		$instance->add($testKey, $testValue, $testFlag, $expire);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$requestid = $output["apache_pid"];

		$instance->replace($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->set($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->append($testKey, $testValue,0,$expire);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->prepend($testKey, $testValue);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->replace($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->set($testKey, $testValue, $testFlag);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->increment($testKey);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->decrement($testKey);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->casByKey($testKey, $testValue, $testFlag, 0, $cas, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->addByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->appendByKey($testKey, $testValue, 0, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->prependByKey($testKey, $testValue, 0, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");



		$instance->setByKey($testKey, $numValue, $testFlag, 0, 0, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->incrementByKey($testKey, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

		$instance->decrementByKey($testKey, SHARDKEY1);
		$output = Utility::parseLoggerFile_syslog();
		$settime+= $output["res_time"];
		$this->assertEquals($requestid, $output["apache_pid"], "Request id");

	}
}

class ApacheLog_TestCase_Quick extends ApacheLog_TestCase{

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}

	public function flagsProvider() {
		return Data_generation::provideFlags();
	}
}
?>


