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

abstract class Leak_TestCase extends ZStore_TestCase {
	
	public function test_memcache_pconnect() {
		
		$start = memory_get_usage();
		for($j = 0; $j < 5000; $j++) {
			$conn = memcache_pconnect(TEST_HOST_1, ZBASE_PORT_NO);
			memcache_close($conn);
		}
		$end = memory_get_usage();
		if (($end-$start) > 204800){
			$success = false;
		} else {
			$success = true;
		}

		$this->assertTrue($success, "memcache_pconnect (positive)");
	}

	public function test_Operations() {

		$instance = $this->sharedFixture;

		$start = memory_get_usage();
		$key = array("key1", "key2");
		for ($i = 0 ; $i < 100000 ; $i++){
			$instance->set("key1",70);
			$instance->increment("key1", 10);
			$instance->decrement("key1", 10);
			$instance->add("key1", "value");
			$instance->delete("key1");
			$instance->add("key1", "value");
			$instance->set("key2",70);
			$instance->replace("key2", "value");
			$instance->append("key2", 07);
			$instance->prepend("key2", 90);
			$instance->get($key);
			$instance->getl("key1");
			$instance->unlock("key1");
		}	

		$end = memory_get_usage();
		if (($end-$start) < 2000 ){
			$success = true ;
		} else	{
			$success = false ;
		}

		$this->assertTrue($success, "Memcache::set (positive)");
	}

		
}


class Leak_TestCase_Quick extends Leak_TestCase
{
	public function keyProvider() {
		return array(array("test_key"));
	}

	public function keyValueProvider() {
		return array(array("test_key", "test_value"));
	}

	public function keyValueFlagsProvider() {
		return array(array("test_key", "test_value", 0));
	}
	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}


?>

