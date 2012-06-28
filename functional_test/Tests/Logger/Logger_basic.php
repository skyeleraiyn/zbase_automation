<?php
abstract class Logger_TestCase extends ZStore_TestCase {

	/**
     * @dataProvider keyValueFlagsProvider
    */
	public function test_Set_Get($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$instance->setLogName("Logger_basic");
		$expiry = 30;
		if (!(@unserialize($testValue))){
			$testValue_Length = serialize($testValue);
		}
		else{
			$testValue_Length = $testValue;
		}	
		$testValue_Length = strlen($testValue_Length);
		
   		// positive set
		$time_start = microtime(true);
   		$instance->set($testKey, $testValue, $testFlag,$expiry);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("set", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
		$this->assertLessThanorEqual($testValue_Length, intval($output["res_len"]), "resp length");
		$this->assertEquals($testFlag, $output["flags"], "flag");
		$this->assertTrue( ($output["expire"] == 30), "Expiry");
		$this->assertNotEquals(0, $output["res_time"], "res_time");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, 	$time_end,  $output['res_time'])));


		// postive get
		$time_start = microtime(true);
		$instance->get($testKey);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("get", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertGreaterThanorEqual($output["res_len"], $testValue_Length, "resp length");
		$this->assertEquals($testFlag, $output["flags"], "flag");	
		$this->assertNotEquals(0, $output["res_time"], "res_time");		
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
			
	}


	public function test_Get_delayed_fetch() {

		$instance = $this->sharedFixture;
		$testKey = "testkey"; 
		$testValue = "testvalue";
		$testFlag = 0;
		$testValue_Length = strlen($testValue);
		
   		// positive set
   		$instance->set($testKey, $testValue, $testFlag);
	
		// evict key and set bg_fetch to 6 secs
		Utility::EvictKeyFromMemory_Master_Server($testKey);
		Utility::Set_bg_fetch_delay_Master_Server(6);
		
		// postive get
		$instance->get($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("get", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertGreaterThanorEqual($output["res_len"], $testValue_Length, "resp length");
		$this->assertEquals($testFlag, $output["flags"], "flag");	
		$this->assertEquals(6, round(intval($output["res_time"]) / 1000000,0), "res_time".$output["res_time"]);	
			
	}
	

	public function test_Get_Negative() {
		
		$instance = $this->sharedFixture;
		$testKey = "testKey";
		$testValue = "testValue";
		
		// negative get
		$time_start = microtime(true);
		$instance->get("dummykey");
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_SUCCESS, $output["res_len"], "resp length");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
		
	}	



	
	/**
     * @dataProvider keyValueFlagsProvider
    */
	public function test_Get2($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;		
		if (!(@unserialize($testValue))){
			$testValue_Length = serialize($testValue);
		}
		else{
			$testValue_Length = $testValue;
		}	
   		$instance->set($testKey, $testValue, $testFlag);

		// postive get2
		$time_start = microtime(true);
		$instance->get2($testKey, $value);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("get2", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertLessThanorEqual(strlen($testValue_Length), $output["res_len"], "response length");
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "resp code");		
		$this->assertEquals($testFlag, $output["flags"], "flag");		
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
        $this->assertTrue( ($output["expire"] == 0), "Expiry");

	}

	
	/**
     * @dataProvider SimpleKeyValueProvider
    */
	public function test_Delete($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		// negative delete
		$time_start = microtime(true);
		$instance->delete($testKey);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("delete", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "resp code");		
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
        $this->assertTrue(($output["expire"] == 0), "Expiry");
		
				// postive delete
   		$instance->set($testKey, $testValue);
		$time_start = microtime(true);
		$instance->delete($testKey);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("delete", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_DELETED, $output["res_code"], "resp code");				
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");
	
	}

	/**
     * @dataProvider keyValueFlagsProvider
    */	
	public function test_Replace($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		
		// negative replace
		$time_start = microtime(true);
		$instance->replace($testKey, $testValue, $testFlag);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
		
   		// positive set
   		$instance->set($testKey, $testValue, $testFlag);
		$time_start = microtime(true);
		$instance->replace($testKey, $testValue, $testFlag);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("replace", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
		$this->assertEquals($testFlag, $output["flags"], "flag");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");
		
	}
	
	/**
     * @dataProvider keyValueFlagsProvider
    */	
	public function test_Add($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		$expire = 30;
		
		// postive add
		$time_start = microtime(true);
		$instance->add($testKey, $testValue, $testFlag, $expire);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("add", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
                $this->assertTrue( ($output["expire"] == 30), "Expiry");
		$this->assertEquals($testFlag, $output["flags"], "flag");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
		
   		// negative add
   		$instance->set($testKey, $testValue, $testFlag);
		$time_start = microtime(true);
		$instance->add($testKey, $testValue, $testFlag);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
		$this->assertTrue( ($output["expire"] == 0), "Expiry");
	
	}

	
	/**
     * @dataProvider simpleNumericKeyValueProvider
     * @expectedException PHPUnit_Framework_Error
    */

	public function test_Increment($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		// negative increment
		$time_start = microtime(true);
		$instance->increment($testKey, $testValue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");
		
   		// positive increment
   		$instance->set($testKey, $testValue);
		$time_start = microtime(true);
		$instance->increment($testKey, $testValue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("increment", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");

		// negative increment
		$time_start = microtime(true);
		$instance->increment($testKey, "testValue");
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");

	}
	
	
	/**
     * @dataProvider simpleNumericKeyValueProvider
     * @expectedException PHPUnit_Framework_Error
    */
	
	public function test_Decrement($testKey, $testValue) {

		$instance = $this->sharedFixture;
		
		// negative increment
		$time_start = microtime(true);
		$instance->decrement($testKey, $testValue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");
		
   		// positive increment
   		$instance->set($testKey, $testValue);
		$time_start = microtime(true);
		$instance->decrement($testKey, $testValue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("decrement", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");

		// negative increment
		$time_start = microtime(true);
		$instance->decrement($testKey, "testValue");
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");

	}
	
	
	/**
     * @dataProvider keyValueFlagsProvider
    */	
	public function test_CAS($testKey, $testValue, $testFlag) {

		$instance = $this->sharedFixture;
		
		$expire=30;
		// postive CAS
		$instance->set($testKey, $testValue, $testFlag);
		$instance->get($testKey, $flagvalue, $casvalue);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals($casvalue, $output["cas"], "casvalue");
		$time_start = microtime(true);
		$instance->cas($testKey, $testValue, $testFlag, $expire, $casvalue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("cas", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
		$this->assertEquals($testFlag, $output["flags"], "flag");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 30), "Expiry");
		
		
   		// negative CAS
		$time_start = microtime(true);
		$instance->cas($testKey, $testValue, $testFlag, MC_SUCCESS, 123);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_EXISTS, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");

	
	
	}
	
	/**
     * @dataProvider simpleKeyValueProvider
    */	
	public function test_Append($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$expire = 30;
		
		// negative append
		$time_start = microtime(true);
		$instance->append($testKey, $testValue,0,$expire);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertEquals( $output["expire"], 30, "Expiry");
		
   		// positive append
   		$instance->set($testKey, $testValue, 0);
		$time_start = microtime(true);
		$instance->append($testKey, $testValue, 0, $expire);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("append", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
        $this->assertTrue( ($output["expire"] == 30), "Expiry");
	
	}

	/**
     * @dataProvider simpleKeyValueProvider
    */	
	public function test_Prepend($testKey, $testValue) {

		$instance = $this->sharedFixture;
		$expire = 30;
		
		// negative prepend
		$time_start = microtime(true);
		$instance->prepend($testKey, $testValue);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals(MC_NOT_STORED, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");
		
   		// positive prepend
   		$instance->set($testKey, $testValue);
		$time_start = microtime(true);
		$instance->prepend($testKey, $testValue, 0, $expire);
		$time_end = microtime(true);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("prepend", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
		$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 30), "Expiry");
			
	}

	 /**
     * @dataProvider keyValueFlagsProvider
    */
        public function test_getl($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();
		$instance2->setLogName("Logger_basic");
				if (!(@unserialize($testValue))){
					$testValue_Length = serialize($testValue);
				}
				else{
					$testValue_Length = $testValue;
				}
				$testValue_Length = strlen($testValue_Length);
                $casvalue=0;
                //negative getl
				$time_start = microtime(true);
				$instance->getl($testKey);
				$time_end = microtime(true);
				$output = Utility::parseLoggerFile_temppath();
                                $this->assertLessThanorEqual($testValue_Length, $output["res_len"], "res length");
				$this->assertEquals(MC_NOT_FOUND, $output["res_code"], "respcode");
				$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
				$this->assertTrue( ($output["expire"] == 0), "Expiry");

				// postive getl
				$instance->set($testKey, $testValue, $testFlag);
				$instance->get($testKey, $flagvalue,$casvalue);
				$casvalue++;
				$time_start = microtime(true);
				$instance->getl($testKey, $flagvalue);
				$time_end = microtime(true);
				$output = Utility::parseLoggerFile_temppath();
				$this->assertEquals("Logger_basic", $output["logname"], "log name");
				$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
				$this->assertEquals("getl", $output["command"], "Command");
				$this->assertEquals($testKey, $output["key"], "keyname");
				$this->assertLessThanorEqual($testValue_Length, $output["res_len"], "res length");
				$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
				$this->assertEquals($testFlag, $output["flags"], "flag");
				$this->assertEquals($casvalue, $output["cas"], "cas value");
				$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
				$this->assertTrue( ($output["expire"] == 0), "Expiry");

				//negative getl
				$time_start = microtime(true);
				$instance2->getl($testKey);
				$time_end = microtime(true);
				$output = Utility::parseLoggerFile_temppath();
				$this->assertLessThanorEqual($testValue_Length, $output["res_len"], "res length");
				$this->assertEquals(MC_LOCK_ERROR, $output["res_code"], "respcode");
				$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", 		array($time_start, $time_end,  $output['res_time'])));
				$this->assertTrue( ($output["expire"] == 0), "Expiry");
				$instance->unlock($testKey);

        }

 	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Unlock($testKey, $testValue, $testFlag) {
	
		$instance = $this->sharedFixture;
		$instance->set($testKey, $testValue, $testFlag);
		$instance->getl($testKey);
		$instance->unlock($testKey);
		$output = Utility::parseLoggerFile_temppath();
		$this->assertEquals("Logger_basic", $output["logname"], "log name");
		$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
		$this->assertEquals("unlock", $output["command"], "Command");
		$this->assertEquals($testKey, $output["key"], "keyname");
		$this->assertEquals(MC_UNLOCKED, $output["res_code"], "respcode");

	} 

		//***** Serialize/ Deserialize ****/
        /**
     * @dataProvider keyValueFlagsProvider
    */
        public function test_Set_Get_Complex($testKey, $testValue0, $testFlag) {

                $instance = $this->sharedFixture;
                $expiry = 30;
	
				$testValue = new ComplexObject($testValue0);

                // positive set
                $time_start = microtime(true);
                $instance->set($testKey, $testValue, $testFlag,$expiry);
                $time_end = microtime(true);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals("Logger_basic", $output["logname"], "log name");
                $this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
                $this->assertEquals("set", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");
                $this->assertEquals($testFlag, $output["flags"], "flag");
                $this->assertTrue( ($output["expire"] == 30), "Expiry");
                $this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["serialize_time"]/MICRO_TO_SEC >= 5 ), "Serialize time");

                // postive get
                $time_start = microtime(true);
                $instance->get($testKey);
                $time_end = microtime(true);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals("Logger_basic", $output["logname"], "log name");
                $this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
                $this->assertEquals("get", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                //$this->assertTrue(Utility::compare_compress_length($testValue, $testFlag,$output["res_len"]), "res length" .strlen($testValue). "  " . $output["res_len"] . "  " . $testFlag);
                //$this->assertEquals($testFlag + 1, $output["flags"], "flag");
                $this->assertTrue( ($output["serialize_time"]/MICRO_TO_SEC >= 5 ), "Serialize time");

        }

        /**
     * @dataProvider keyValueFlagsProvider
    */
        public function test_Get2_Complex($testKey, $testValue0, $testFlag) {

                $instance = $this->sharedFixture;
                $expiry = 30;

                $testValue = new ComplexObject($testValue0);


                $instance->set($testKey, $testValue, $testFlag);

                // postive get2
                $time_start = microtime(true);
                $instance->get2($testKey, $value);
                $time_end = microtime(true);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals("Logger_basic", $output["logname"], "log name");
                $this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
                $this->assertEquals("get2", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
//                $this->assertTrue(Utility::compare_compress_length($testValue, $testFlag,$output["res_len"]), "res length" .strlen($testValue). "  " . $output["res_len"] . "  " . $testFlag);
                $this->assertEquals(MC_SUCCESS, $output["res_code"], "resp code");
                //$this->assertEquals($testFlag + 1, $output["flags"], "flag");
                $this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["expire"] == 0), "Expiry");
                $this->assertTrue( ($output["serialize_time"]/MICRO_TO_SEC >= 5 ), "Serialize time");


        }

        /**
     * @dataProvider keyValueFlagsProvider
    */
        public function test_Replace_Complex($testKey, $testValue0, $testFlag) {

			$instance = $this->sharedFixture;
			$expiry = 30;

			$testValue = new ComplexObject($testValue0);


			// positive set
			$instance->set($testKey, $testValue, $testFlag);
			$time_start = microtime(true);
			$instance->replace($testKey, $testValue, $testFlag);
			$time_end = microtime(true);
			$output = Utility::parseLoggerFile_temppath();
			$this->assertEquals("Logger_basic", $output["logname"], "log name");
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("replace", $output["command"], "Command");
			$this->assertEquals($testKey, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlag, $output["flags"], "flag");
			$this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
			$this->assertTrue( ($output["expire"] == 0), "Expiry");
			$this->assertTrue( ($output["serialize_time"]/MICRO_TO_SEC >= 5 ), "Serialize time");

        }
	
        /**
     * @dataProvider keyValueFlagsProvider
    */
        public function test_Add_Complex($testKey, $testValue0, $testFlag) {

                $instance = $this->sharedFixture;
                $expire = 30;
                $testValue = new ComplexObject($testValue0);

                // postive add
                $time_start = microtime(true);
                $instance->add($testKey, $testValue, $testFlag, $expire);
                $time_end = microtime(true);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals("Logger_basic", $output["logname"], "log name");
                $this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
                $this->assertEquals("add", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");
                $this->assertTrue( ($output["expire"] == 30), "Expiry");
                $this->assertEquals($testFlag, $output["flags"], "flag");
                $this->assertTrue(Utility::time_compare($time_start, $time_end,  $output["res_time"]), "Resp time " . implode(" , ", array($time_start, $time_end,  $output['res_time'])));
                $this->assertTrue( ($output["serialize_time"]/MICRO_TO_SEC >= 5 ), "Serialize time");
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
                $this->assertEquals(MC_EXISTS, $output["res_code"], "respcode");

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
     * @dataProvider simpleNumericKeyValueProvider
     */
        public function test_incrementByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;

                $instance->addByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
                $instance->incrementByKey($testKey, SHARDKEY1);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
                $this->assertEquals("incrementbykey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider simpleNumericKeyValueProvider
     */
        public function test_decrementByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;

                $instance->addByKey($testKey, $testValue, $testFlag, 0, 0, SHARDKEY1);
                $instance->decrementByKey($testKey, SHARDKEY1);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
                $this->assertEquals("decrementbykey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider simpleKeyValueProvider
     */
        public function test_appendByKey($testKey, $testValue, $testFlag) {

			$instance = $this->sharedFixture;
			$testValue1 = $testValue;
			$testValue2 = "testValue2";

			$instance->addByKey($testKey, $testValue1, 0, 0, 0, SHARDKEY1);
			$instance->appendByKey($testKey, $testValue2, 0, 0, 0, SHARDKEY1);
			$output = Utility::parseLoggerFile_temppath();
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("appendByKey", $output["command"], "Command");
			$this->assertEquals($testKey, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider simpleKeyValueProvider
     */
        public function test_prependByKey($testKey, $testValue, $testFlag) {

			$instance = $this->sharedFixture;
			$testValue1 = $testValue;
			$testValue2 = "testValue2";

			$instance->addByKey($testKey, $testValue1, 0, 0, 0, SHARDKEY1);
			$instance->prependByKey($testKey, $testValue2, 0, 0, 0, SHARDKEY1);
			$output = Utility::parseLoggerFile_temppath();
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("prependByKey", $output["command"], "Command");
			$this->assertEquals($testKey, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");

        }


}

class Logger_TestCase_Quick extends Logger_TestCase{
	public function simpleKeyValueProvider() {
		return array(array("test_key", "test_value", 0));
	}
	public function simpleNumericKeyValueProvider() {
		return array(array("test_key", 5, 0));
	}	

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	
}
?>

