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


abstract class Replication_TestCase extends ZStore_TestCase
{
   	/**
     * @dataProvider keyValueFlagsProvider
     */

	public function test_Replication_Set_Get($testKey, $testValue, $testFlags) {

	
	$instance = Connection::getMaster();
	$instanceslave = Connection::getSlave();
	$instanceslave2 = Connection::getSlave2();
		// add key 
	$instance->set($testKey, $testValue, $testFlags);
	sleep(2);
	
		// validate added value
	$returnFlags = null;
	$returnValue = $instanceslave->get($testKey, $returnFlags);
	$this->assertNotEquals($returnValue, false, "Memcache slave1::get (positive)");
	$this->assertEquals($testValue, $returnValue, "Memcache slave1::get (value)");
	$this->assertEquals($testFlags, $returnFlags, "Memcache slave1::get (flag)");

	$returnFlags2 = null;
	$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
	$this->assertNotEquals($returnValue2, false, "Memcache slave2::get (positive)");
	$this->assertEquals($testValue, $returnValue2, "Memcache slave2::get (value)");
	$this->assertEquals($testFlags, $returnFlags2, "Memcache slave2::get (flag)");
	}	 
	
	 /**
     * @dataProvider keyValueSerializeFlagsProvider 
     */

	public function test_Replication_Base64_Encode_Serialize($testKey, $testValue, $testFlags) {
	$instance = Connection::getMaster();
	$instanceslave = Connection::getSlave();
	$instanceslave2 = Connection::getSlave2();
	
	if (!(@unserialize($testValue))){
		$testValue = serialize($testValue);
	}
	
	$testValue = base64_encode($testValue);
		// add key 
	$instance->set($testKey, $testValue, $testFlags);
	sleep(2);
	
		// validate added value
	$returnFlags = null;
	$returnValue = $instanceslave->get($testKey, $returnFlags);
	$this->assertNotEquals($returnValue, false, "Memcache slave1::get (positive)");
	$this->assertEquals($testValue, $returnValue, "Memcache slave1::get (value)");
	$this->assertEquals(base64_decode($testValue), base64_decode($returnValue), "Memcache slave1::get (value)");
	$this->assertEquals($testFlags, $returnFlags, "Memcache slave1::get (flag)");

	$returnFlags2 = null;
	$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
	$this->assertNotEquals($returnValue2, false, "Memcache slave2::get (positive)");
	$this->assertEquals($testValue, $returnValue2, "Memcache slave2::get (value)");
	$this->assertEquals(base64_decode($testValue), base64_decode($returnValue2), "Memcache slave2::get (value)");
	$this->assertEquals($testFlags, $returnFlags2, "Memcache slave2::get (flag)");	
	}

	/**
     * @dataProvider keyValueProvider
     */
	public function test_Replication_Delete($testKey, $testValue) {

		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();

		// set reference value
		$instance->set($testKey, $testValue);
   		$success = $instance->delete($testKey);  
		sleep(2);	
  
   		$returnValue = $instanceslave->get($testKey);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
		$returnValue2 = $instanceslave2->get($testKey);
   		$this->assertFalse($returnValue2, "Memcache::get (negative)");
	} 
	
	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_Replace($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		
		$testValue1 = serialize(array($testValue));
		$testValue2 = $testValue;
		
   		$instance->set($testKey, $testValue1);
		$instance->replace($testKey, $testValue2, $testFlags);
   		sleep(2);
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
		
		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertNotEquals($returnValue2, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2, $returnValue2, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flags)");
	}


	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_Add($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags);
   		sleep(2);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertNotEquals($returnValue2, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue2, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag)");
	}
	
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Replication_Increment($testKey, $testValue) {
   		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		
   		$testValue1 = strlen($testValue);
		
   		// set initial value
   		$instance->set($testKey, $testValue1);
		
   		// positive increment test
   		$instance->increment($testKey, $testValue1);
		sleep(2);
		$returnValue = $instanceslave->get($testKey);
   		$this->assertEquals($returnValue, 2 * $testValue1,  "Memcache::increment (positive)");
		$returnValue2 = $instanceslave2->get($testKey);
   		$this->assertEquals($returnValue2, 2 * $testValue1,  "Memcache::increment (positive)");
	} 
	
	/**
     * @dataProvider keyValueProvider
     */
	public function test_Replication_Decrement($testKey, $testValue) {
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		
   		$testValue1 = strlen($testValue);
   		
   		// set initial value
   		$instance->set($testKey, $testValue1 * 2);

   		// positive decrement test
   		$instance->decrement($testKey, $testValue1);
		sleep(2);
		$returnValue = $instanceslave->get($testKey);
   		$this->assertEquals($returnValue, $testValue1,  "Memcache::decrement (positive)");
		$returnValue2 = $instanceslave2->get($testKey);
   		$this->assertEquals($returnValue2, $testValue1,  "Memcache::decrement (positive)");
	}

	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_SetTTL($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		
		$testTTL = 900; // Need this value for replication to happen
		
		// positive set test
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		sleep(2);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		
		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertNotEquals($returnValue2, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue2, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag)");
	}

	
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_Get2TTL($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		
		$testTTL = 900;
		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);
   		sleep(2);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = null;
   		$success = $instanceslave->get2($testKey, $returnValue, $returnFlags);
   		$this->assertTrue($success, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get2 (flag)");
   		$returnFlags2 = null;
   		$returnValue2 = null;
   		$success2 = $instanceslave2->get2($testKey, $returnValue2, $returnFlags2);
   		$this->assertTrue($success2, "Memcache::get2 (positive)");
   		$this->assertEquals($testValue, $returnValue2, "Memcache::get2 (value)");
   		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get2 (flag)");		
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_AddTTL($testKey, $testValue, $testFlags) {

		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();

		$testTTL = 900;
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags, $testTTL);
   		sleep(2);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
   		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertNotEquals($returnValue2, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue, $returnValue2, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag)");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_ReplaceTTL($testKey, $testValue, $testFlags) {

		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();

		$testTTL = 900;
		
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		
   		// positive add test 
   		$success = $instance->replace($testKey, $testValue, $testFlags, $testTTL);
   		sleep(2);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");
		$this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
   		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertNotEquals($returnValue2, false, "Memcache::get (positive)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag)");
		$this->assertEquals($testValue, $returnValue2, "Memcache::get (value)");		
	}
   		
	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_SetTTLExpired($testKey, $testValue, $testFlags) {     // SEG-10769 expiry mutation is replicated after 2mins
		// Aim: Set a key on master with expiry. Wait till the expiry pager runs on the master and fetch the key from slave
		// Expected: Key should not be present after the expiry pager has run
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "10");
		
		$testTTL = 3;		
		// positive set test
		$success = $instance->set($testKey, $testValue, $testFlags, $testTTL);   		
   		sleep(130); // Sleep until expiry pager runs

   		// validate set value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
   		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertFalse($returnValue2, "Memcache::get (negative)");		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "3600");
	}

   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_AddTTLExpired($testKey, $testValue, $testFlags) { // SEG-10769 expiry mutation is replicated after 2mins

		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "10");
		
		$testTTL = 3;
		
   		// positive add test 
   		$success = $instance->add($testKey, $testValue, $testFlags, $testTTL);
   		sleep(130);
   		
   		// validate added value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
   		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertFalse($returnValue2, "Memcache::get (negative)");		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "3600");
	}
	
   	/**
     * @dataProvider keyValueFlagsProvider
     */
	public function test_Replication_ReplaceTTLExpired($testKey, $testValue, $testFlags) { // SEG-10769 expiry mutation is replicated after 2mins

		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		$instanceslave2 = Connection::getSlave2();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "10");
		
		$testTTL = 3;
		
		$instance->set($testKey, $testValue, $testFlags, $testTTL);
		
   		// positive add test 
   		$success = $instance->replace($testKey, $testValue, $testFlags, $testTTL);
   		sleep(130);
   		
   		// validate replaced value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertFalse($returnValue, "Memcache::get (negative)");
   		$returnFlags2 = null;
   		$returnValue2 = $instanceslave2->get($testKey, $returnFlags2);
   		$this->assertFalse($returnValue2, "Memcache::get (negative)");		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "3600");
	}

	   	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Replication_Set_Delete_Multiple_times($testKey, $testValue, $testFlags) {

		// test to check vbucketmigrator doesn't break the connection
		
	$instance = Connection::getMaster();
	$instanceslave = Connection::getSlave();
	$instanceslave2 = Connection::getSlave2();
	
	$instance->set("keysetbeforestartingtest", "valuesetbeforestartingtest", 0);	
	for ($iCount = 0 ; $iCount < 100000 ; $iCount++ )
	{	
		$instance->set($testKey, $testValue, $testFlags);
		$instance->delete($testKey);
	}	
	
		// validate added value
	$returnFlags = null;
	$returnValue = $instanceslave->get("keysetbeforestartingtest");
	$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
	$this->assertEquals("valuesetbeforestartingtest", $returnValue, "Memcache::get (value)");
	$returnFlags2 = null;
	$returnValue2 = $instanceslave2->get("keysetbeforestartingtest");
	$this->assertNotEquals($returnValue2, false, "Memcache::get (positive)");
	$this->assertEquals("valuesetbeforestartingtest", $returnValue2, "Memcache::get (value)");	

	}		

// Append Prepend
   	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Replication_append($testKey, $testValue, $testFlags) {

		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
   		   		
   		// positive append test
   		$success = $instance->append($testKey, $testValue2, $testFlags);
   		sleep(2);
   		
   		// validate appended value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue1.$testValue2, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}

   	/**
     * @dataProvider simpleKeyValueFlagProvider
     */
	public function test_Replication_prepend($testKey, $testValue, $testFlags) {
		
		$instance = Connection::getMaster();
		$instanceslave = Connection::getSlave();
		
		$testValue1 = $testValue;
		$testValue2 = strrev($testValue1);
		
   		// positive add test 
   		$instance->set($testKey, $testValue1);
   		   		
   		// positive prepend test
   		$success = $instance->prepend($testKey, $testValue2, $testFlags);
   		sleep(2);
   		
   		// validate prepended value
   		$returnFlags = null;
   		$returnValue = $instanceslave->get($testKey, $returnFlags);
   		$this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
   		$this->assertEquals($testValue2.$testValue1, $returnValue, "Memcache::get (value)");
   		$this->assertEquals($testFlags, $returnFlags, "Memcache::get (flags)");
	}
	
	
	// set 1000 keys with negative expiry time. verify replication doesn't break
	public function test_verify_replication_disconnect_with_negative_expiry_time(){
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		
		$output_1 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_1 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_1));
		
		$instance = Connection::getMaster();
		$pid_arr = array();
		$time_now = time();
		for($ithread=0; $ithread<20 ; $ithread++){
			$pid = pcntl_fork();
			if ($pid == 0){
				$start_time = time();
				while(1){
					for($ikey=0 ; $ikey<1000000 ; $ikey++){
						@$instance->set("test_key_negative_expiry_$ikey", "testvalue", 0, $time_now);	// When multiple threads are spwaned, chances are high that some requests 
						@$instance->set("test_key_without_expiry_$ikey", "testvalue");					// will not be served resulting in server error, which in turn causes the 
						@$instance->set("test_key_small_expiry_$ikey", "testvalue", 0, rand(1, 2));		// phpunit framework to trigger an error. Either have expected exception annotation
						@$instance->set("test_key_large_expiry_$ikey", "testvalue", 0, rand(5, 10));	// or put @ before the request to capture the std error 
						@$instance->set("test_key_same_key_mutation", "testvalue", 0);
						if((time() - $start_time) > 300) exit;
					}
				}				
				exit;
			} else{
				$pid_arr[] = $pid;
			}
		}
		
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
			sleep(1);
		}
		
		$output_2 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_2 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_2));
		$this->assertEquals($output_1, $output_2, "replication:disconnects has increased");		

	}

	// set and delete multiple times. Verify replcation does'nt break due to this.
	public function test_verify_replication_with_set_and_delete(){
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		
		$output_1 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_1 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_1));
		
		$instance = Connection::getMaster();
		$pid_arr = array();
		$start_time = time();
		for($ithread=0; $ithread<20 ; $ithread++){
			$pid = pcntl_fork();
			if ($pid == 0){
				while(1){
					$ikey = rand(1, 10000);
					@$instance->set("test_key_delete_$ikey", "testvalue");
					usleep(rand(0,1000));
					@$instance->delete("test_key_delete_$ikey");
					if((time() - $start_time) > 300) break;
				}
				exit;
			} else {
				$pid_arr[] = $pid;
			}
		}
		
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
			sleep(1);
		}			
		
		$output_2 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_2 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_2));
		$this->assertEquals($output_1, $output_2, "replication:disconnects has increased");		
	}	
		// Added for bug  SEG-8985 Zbase 1.7 - Tap connection gets dropped by zbase on greyhound bubble 
		
	public function test_zero_byte_value(){
	
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "stop");
		sleep(1);
		$output_1 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_1 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_1));
		
			// set the keys
		$testvalue = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa".
					"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";		

		Utility::netcat_execute("key", 0, $testvalue, TEST_HOST_1);
		Utility::netcat_execute("01", 0, "", TEST_HOST_1);
		
			// attach vbucketmigrator and verify it doesn't disconnect
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(1);
		$output_2 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_2 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_2));
		$this->assertEquals($output_1, $output_2, "replication:disconnects has increased");
	//		remote_function::remote_execution(TEST_HOST_1, "echo -ne 'verbosity 3\r\n' | nc 0 11211");
	}
	
	public function test_zero_byte_value_delete(){
	
		vbucketmigrator_function::kill_vbucketmigrator(TEST_HOST_1);
		zbase_setup::reset_zbase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "stop");		
		sleep(1);
		$output_1 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_1 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_1));

			// set and delete the key
		$test_key_1 = "abcdefghijabcdefghijabcdefgh"; // 28 bytes key
		$temp_value = "abcdefghij";
		$test_key_2 = "";	// 100 bytes key
		for($i=0 ; $i<10 ; $i++){
			$test_key_2 = $test_key_2.$temp_value;
		}
		$testvalue_2 = "";	//1200 bytes value
		for($i=0 ; $i<120 ; $i++){
			$testvalue_2 = $test_key_2.$temp_value;
		}
	
		Utility::netcat_execute($test_key_1, 0, "", TEST_HOST_1);
		Utility::netcat_execute($test_key_2, 0, $testvalue_2, TEST_HOST_1);
		Utility::netcat_execute($test_key_1, 0, "", TEST_HOST_1, "delete");
			// attach vbucketmigrator and verify it doesn't disconnect
		vbucketmigrator_function::vbucketmigrator_service(TEST_HOST_1, "start");
		sleep(1);
		$output_2 = trim(stats_functions::get_stats_netcat(TEST_HOST_1, "replication:disconnects", "tap"));
		$output_2 = trim(str_replace("STAT eq_tapq:replication:disconnects", "", $output_2));
		$this->assertEquals($output_1, $output_2, "replication:disconnects has increased");
	
	}	
	
}


class Replication_TestCase_Full extends Replication_TestCase{

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	public function keyValueSerializeFlagsProvider() {
		return Data_generation::provideKeyValueserializeFlags();
	}	
	public function simpleKeyValueFlagProvider() {
		return array(array("test_key", "test_value", 0));
	}	

}
