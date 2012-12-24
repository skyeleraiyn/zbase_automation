<?php

error_reporting ( E_WARNING );

// Issue SERVER_NO_RESP_RES_TIME is 0 as against 10secs

abstract class Sig_stop_server_test extends ZStore_TestCase {

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_SigStopServerConnection_set($testKey, $testValue, $testFlag){
	
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
		$time_start = microtime(true);
		$instance_SigStop_Server_connection->set($testKey, $testValue);
		$time_end = microtime(true);
		//TODO: set here throws readline exception
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("set", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(strlen($testValue), $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(SERVER_NO_RESP_RES_TIME, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);	
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
		
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_get($testKey, $testValue, $testFlag){

		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
	
		$time_start = microtime(true);
		$instance_SigStop_Server_connection->get($testKey);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("get", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_getl($testKey, $testValue, $testFlag){
		
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
	
		$instance_SigStop_Server_connection->getl($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("getl", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
		
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_get2($testKey, $testValue, $testFlag){
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
	
		$instance_SigStop_Server_connection->get2($testKey, $get2_output);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("get2", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
		
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_add($testKey, $testValue, $testFlag){
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
	
		$instance_SigStop_Server_connection->add($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("add", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(strlen($testValue), $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(SERVER_NO_RESP_RES_TIME, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_replace($testKey, $testValue, $testFlag){
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
		
		$instance_SigStop_Server_connection->replace($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("replace", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(strlen($testValue), $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(SERVER_NO_RESP_RES_TIME, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_delete($testKey, $testValue, $testFlag){
		
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
		
		$instance_SigStop_Server_connection->delete($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("delete", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_prepend($testKey, $testValue, $testFlag){
		
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();

		$instance_SigStop_Server_connection->prepend($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("prepend", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(strlen($testValue), $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(SERVER_NO_RESP_RES_TIME, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);

	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_append($testKey, $testValue, $testFlag){
		
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();

		$instance_SigStop_Server_connection->append($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("append", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(strlen($testValue), $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(SERVER_NO_RESP_RES_TIME, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);

	}

	 /**
     * @dataProvider simpleKeyNumericValueFlagProvider
     */	
	public function test_SigStopServerConnection_increment($testKey, $testValue, $testFlag){

		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
		
		$instance_SigStop_Server_connection->increment($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("increment", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);

	}

	/**
     * @dataProvider simpleKeyNumericValueFlagProvider
     */
	public function test_SigStopServerConnection_decrement($testKey, $testValue, $testFlag){
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
		
		$instance_SigStop_Server_connection->decrement($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("decrement", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */	
	public function test_SigStopServerConnection_cas($testKey, $testValue, $testFlag){
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
		
		$instance_SigStop_Server_connection->cas($testKey, $testValue, 0, 0, 123);;
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("cas", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(strlen($testValue), $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(123, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(SERVER_NO_RESP_RES_TIME, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
			
	}

	 /**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_SigStopServerConnection_unlock($testKey, $testValue, $testFlag){
	
		$instance_SigStop_Server_connection = Connection::getSigStopServerConnection();
	
		$instance_SigStop_Server_connection->unlock($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(SERVER_NO_RESP_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("unlock", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		$this->assertEquals(0, $output["cas"], "cas".$output["cas"]);
		$this->assertEquals(0, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);
		
	}	
	
}

class Sig_stop_server_Quick extends Sig_stop_server_test{
	public function simpleKeyValueFlagProvider() {
		return array(array("test_key", "test_value", 0));
	}
	public function simpleKeyNumericValueFlagProvider() {
		return array(array("test_key", 5, 0));
	}	
}
?>

