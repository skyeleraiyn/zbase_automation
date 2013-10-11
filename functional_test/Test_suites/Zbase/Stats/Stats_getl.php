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
	
abstract class Stats_getl_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider keyProvider
	*/

	public function test_getl_stat($testKey) {
		
		$cmd_getl_stat = stats_functions::get_all_stats(TEST_HOST_1,"cmd_getl");
		$getl_hits_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_hits");
		$getl_misses_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_misses");
		
		$instance = Connection::getMaster();
		// positive getl
		$instance->set($testKey,"test_value");
		$instance->getl($testKey);
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"cmd_getl"), $cmd_getl_stat + 1, "cmd_getl increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_hits"), $getl_hits_stat + 1, "cmd_getl increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_misses"), $getl_misses_stat, "cmd_getl increment");
		
		// getl on dummy key should not increment getl_hits and increments cmd_getl , getl_misses
		$cmd_getl_stat = stats_functions::get_all_stats(TEST_HOST_1,"cmd_getl");
		$getl_hits_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_hits");
		$getl_misses_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_misses");
		$getl_misses_notfound_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_misses_notfound");
		
		$instance->delete("getldummykey");
		$instance->getl("getldummykey");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"cmd_getl"), $cmd_getl_stat + 1, "cmd_getl increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_hits"), $getl_hits_stat, "getl_hits increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_misses"), $getl_misses_stat + 1, "getl_misses increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_misses_notfound"), $getl_misses_notfound_stat + 1, "getl_misses_notfound increment");
		
				// getl lock error
		$cmd_getl_stat = stats_functions::get_all_stats(TEST_HOST_1,"cmd_getl");
		$getl_hits_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_hits");
		$getl_misses_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_misses");
		$getl_misses_locked_stat = stats_functions::get_all_stats(TEST_HOST_1,"getl_misses_locked");
		
		$instance->getl($testKey);
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"cmd_getl"), $cmd_getl_stat + 1, "cmd_getl increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_hits"), $getl_hits_stat, "cmd_getl increment");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"getl_misses"), $getl_misses_stat + 1, "cmd_getl increment");		

	}

}


class Stats_getl_TestCase_Quick extends Stats_getl_TestCase {

	public function keyProvider() {
		return array(array("test_key"));
	}

}

?>

