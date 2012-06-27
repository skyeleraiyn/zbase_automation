<?php
error_reporting ( E_WARNING );

// Issue 1) Non_existant_server_Quick::test_Get_Dummy_server
// Memcache::get(): php_network_getaddresses: getaddrinfo failed: Name or service not known
// comes for $instance_dummy_Server_connection only for the first testcase, rest are ignored

abstract class Non_existant_server_test extends ZStore_TestCase {

		// non existant server
	public function test_Get_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();	
		$testKey = "testKey";

		$instance_dummy_Server_connection->get($testKey);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("get", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		
	}		

	public function test_Get2_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		// negative get2 sigstop
		$instance_dummy_Server_connection->get2($testKey, $value);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("get2", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		
	}		

		
	public function test_Getl_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		$instance_dummy_Server_connection->getl($testKey);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("getl", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(0, $output["res_len"], "res_len".$output["res_len"]);
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		$this->assertEquals(NULL, $output["flag"], "flag ".$output["flag"]);
		
	}		
	
		
	public function test_Set_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		$instance_dummy_Server_connection->set($testKey, "testvalue");
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("set", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}		

		public function test_Increment_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		$instance_dummy_Server_connection->increment($testKey, 3);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("increment", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}		
	
		public function test_Decrement_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";


		$instance_dummy_Server_connection->decrement($testKey, 3);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("decrement", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}		
	public function test_Add_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		$instance_dummy_Server_connection->add($testKey, "testvalue");
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("add", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}	

	public function test_Replace_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		$instance_dummy_Server_connection->replace($testKey, "testvalue");
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("replace", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}

	public function test_Delete_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

		$instance_dummy_Server_connection->delete($testKey);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("delete", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}	
	
	public function test_Unlock_Dummy_server() {

		$instance_dummy_Server_connection = Connection::getDummyConnection();		
		$testKey = "testKey";

	
		$instance_dummy_Server_connection->unlock($testKey);
		$output = Utility::parseLoggerFile_temppath();

		$this->assertEquals(SERVER_NO_RESP_DUMMY_HOSTNAME, $output["host"], "host".$output["host"]);
		$this->assertEquals("unlock", $output["command"], "command".$output["command"]);
		$this->assertEquals($testKey, $output["key"], "key ".$output["key"] );
		$this->assertEquals(SERVER_NO_RESP, $output["res_code"], "res_code".$output["res_code"]);
		
	}	
	
}

class Non_existant_server_Quick extends Non_existant_server_test{
	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}
?>

