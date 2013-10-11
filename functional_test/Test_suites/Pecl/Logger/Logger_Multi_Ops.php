/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php
abstract class Multi_Logger_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_SetMultiByKey_Get_Multi( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$instance->setLogName("Logger_Multi_Ops");
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
		}
		$instance->setMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("setMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
		}
		$instance->get($mb);
		$output = Utility::parseLoggerFile_temppath($size);
		array_multisort($mb, $output);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("getMulti", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			//$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Set_Get_CasMultiByKey_Positive( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);
			$mb[] = $k;
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->setMultiByKey($keys);
		$result = $instance->getMultiByKey($key_shard);
		foreach($result as $k => $v){
			$keys[$k]["cas"] = $v["cas"];
		}

		$instance->casMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("casMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Set_Get_CasMultiByKey_BadCAS( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);
			$mb[] = $k;
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->setMultiByKey($keys);
		$result = $instance->getMultiByKey($key_shard);
		foreach($result as $k => $v){
			$keys[$k]["cas"] = 100;
		}

		$instance->casMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("casMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_EXISTS, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_CasMultiByKey_NotFound( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);
			$mb[] = $k;
		}

		$instance->casMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("casMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(INVALID_CAS, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}	

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Get_Multi_Negative( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$mb[] = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
		}
		$instance->get($mb);
		$output = Utility::parseLoggerFile_temppath($size);

		array_multisort($mb, $output);	
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			//print "$k, ". $output["key"];
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("getMulti", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode"); //pecl limitation
			$this->assertEquals(0, $output["flags"], "flag");
		}
		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);	
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Get_Part_Multi( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			if ($i%2 == 0)
			$keys["$k"] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
		}

		$instance->setMultiByKey($keys);
		$instance->get($mb);
		$output = Utility::parseLoggerFile_temppath($size);

		array_multisort($mb, $output);	
		$bulkout = array_combine($mb, $output);

		$i = 0;
		foreach($bulkout as $k => $output) {
			//print "$k, ". $output["key"];
			if ($i%2 == 0) { //key has been set
				$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
				$this->assertEquals("getMulti", $output["command"], "Command");
				$this->assertEquals($k, $output["key"], "keyname");
				$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode"); //pecl limitation
				$this->assertEquals($testFlags, $output["flags"], "flag $k");
				//TODO $this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");
			}else{
				$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
				$this->assertEquals("getMulti", $output["command"], "Command");
				$this->assertEquals($k, $output["key"], "keyname");
				$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode"); //pecl limitation
				$this->assertEquals(0, $output["flags"], "flag");
			}
			$i++;
		}
		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_GetMultiByKey_Positive( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);
			$mb[] = $k;
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->setMultiByKey($keys);
		$result = $instance->getMultiByKey($key_shard);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("getMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			//$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_deleteMultiByKey_Positive( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);
			$mb[] = $k;
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->setMultiByKey($keys);
		$result = $instance->deleteMultiByKey($key_shard);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("deleteMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_DELETED, $output["res_code"], "respcode");
			$this->assertEquals(0, $output["flags"], "flag");
			//$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}


		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_deleteMultiByKey_NotFound( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);
			$mb[] = $k;
			$key_shard[$k] = SHARDKEY1;
		}
		//$instance->setMultiByKey($keys);
		$result = $instance->deleteMultiByKey($key_shard);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("deleteMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(CMD_FAILED, $output["res_code"], "respcode"); //TODO: CMD_FAILED??
			$this->assertEquals(0, $output["flags"], "flag");
			//$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_GetMultiByKey_NotFound( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			if ($i%2 == 0)
			$set_keys[$k] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->setMultiByKey($set_keys);
		$result = $instance->getMultiByKey($key_shard);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		$i=0;
		foreach($bulkout as $k => $output) {
			$this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode"); // missing keys logged as success too. Pecl limitation
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("getMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			if ($i++ % 2 == 0)
			$this->assertEquals($testFlags, $output["flags"], "flag");
			else
			$this->assertEquals(0, $output["flags"], "flag");
			//$this->assertEquals( $output["expire"], $expiry, "Expiry");
		}

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_AddmultiByKey( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys["$k"] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
		}
		$instance->addMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("addMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
			//			$this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");
		}
		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_AddmultiByKey_Negative( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys["$k"] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
		}
		$instance->setMultiByKey($keys);
		$instance->addMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("addMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_NOT_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
			//			$this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");
		}
		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);			
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Add_ReplacemultiByKey( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys["$k"] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
		}
		$instance->addMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("addMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
			//			$this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");
		}

		$instance->replaceMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("replaceMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
			//			$this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");
		}
		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);				
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Add_ReplacemultiByKey_NonExisting( $testKey, $testValue, $testFlags ) {

		$instance = $this->sharedFixture;
		$expiry = 30;
		$size=20;

		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$keys["$k"] = array(
			"value" => $testValue,
			"shardKey" => SHARDKEY1,
			"flag" => $testFlags,
			"cas" => 0,
			"expire" => $expiry);

			$mb[] = $k;
		}
		$instance->addMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("addMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");
			$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
			//			$this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");
		}

		$key_nonexist = $testKey."key_nex";
		$keys[$key_nonexist] = array(
		"value" => "somevalue",
		"shardKey" => SHARDKEY1,
		"flag" => $testFlags,
		"expire" => $expiry);
		$mb[] = "$key_nonexist";

		$instance->replaceMultiByKey($keys);
		$output = Utility::parseLoggerFile_temppath($size+1);
		$bulkout = array_combine($mb, $output);
		foreach($bulkout as $k => $output) {
			if($k == $key_nonexist){
				$this->assertEquals(MC_NOT_STORED, $output["res_code"], "respcode");
			}else{
				$this->assertEquals(MC_STORED, $output["res_code"], "respcode");
			}

			$this->assertEquals(TEST_HOST_1, $output["host"], "Server name");
			$this->assertEquals("replaceMultiByKey", $output["command"], "Command");
			$this->assertEquals($k, $output["key"], "keyname");

			$this->assertEquals($testFlags, $output["flags"], "flag");
			$this->assertTrue( ($output["expire"] == $expiry), "Expiry");
			//			$this->assertEquals(strlen(serialize($testValue)), $output["res_len"], "Length $k");

		}
		for ($i = 0; $i < $size; $i++) {
			$k = "$testKey". str_pad($i, 3, '0', STR_PAD_LEFT);
			$key_shard[$k] = SHARDKEY1;
		}
		$instance->deleteMultiByKey($key_shard);				
	}

}

class Logger_TestCase_Quick extends Multi_Logger_TestCase{

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}

}
?>

