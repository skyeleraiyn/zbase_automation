<?php

abstract class Out_of_memory_server_test extends ZStore_TestCase {
	
	/**
     * @dataProvider keyValueProvider
    */
	public function test_OutofMemoryServerConnection_Set($testKey, $testValue) {

		$instance_Out_of_Memory_Server_connection = Connection::getOutofMemoryServerConnection();
		$time_start = microtime(true);
		$instance_Out_of_Memory_Server_connection->set($testKey, $testValue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_SERVER_MEM_ERROR, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
        $this->assertTrue( ($output["expire"] == 0), "Expiry");		
				
	}	

	/**
     * @dataProvider keyValueProvider
    */
	public function test_OutofMemoryServerConnection_Add($testKey, $testValue) {

		$instance_Out_of_Memory_Server_connection = Connection::getOutofMemoryServerConnection();		
		$instance_Out_of_Memory_Server_connection->add($testKey, $testValue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_SERVER_MEM_ERROR, $output["res_code"], "respcode");	
				
	}		
	
}

class Out_of_memory_server_Quick extends Out_of_memory_server_test{
	public function keyValueProvider() {
		return array(array("test_key", "test_value"));
	}
}
?>

