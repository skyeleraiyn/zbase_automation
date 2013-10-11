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
/*
This testsuite assumes zbase is started with following params:
min_data_age=0;queue_age_cap=1800;max_size=524288000;ht_size=12582917;chk_max_items=100;chk_period=3600;keep_closed_chks=true;restore_file_checks=false;
restore_mode=false;inconsistent_slave_chk=false;ht_locks=100000;tap_keepalive=600;kvstore_config_file=/etc/sysconfig/memcached_multikvstore_config
*/

abstract class LRU_Basic_TestCase extends Zstore_TestCase {

	//Verify default LRU parameters when Zbase is started
	public function test_verify_default_params(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		$this->assertLessThan(52428800, stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_memory_size"), "eviction_memory_size is more than 50MB");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_min_blob_size"), 5, "eviction_min_blob_size is not equal to 5");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_policy"), "lru", "eviction_policy");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_max_queue_size"), 500000, "eviction_max_queue_size");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "lru_rebuild_percent"), 0.5, "lru_rebuild_percent");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "lru_mem_threshold_percent"), 0.5, "lru_mem_threshold_percent");
	}

	//Verify queue is not built until 50% of max_size memory is consumed
	//Verify LRU queue is built when RSS memory goes 50% above the max_size and no of evictable items is equal to curr_items
	public function test_LRU_Memory_Threshold(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(5000, NULL, 1, 10240);
		sleep(2);
			// Verify LRU queue is not built
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp"), 0, "evpolicy_job_start_timestamp is not 0. LRU queue is built");
		$this->assertLessThan(250, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS_memory_size is more than 250MB");
			// Add more keys and verify LRU queue is built
		Data_generation::add_keys(1000, NULL, 5001, 10240);
			// Allow the LRU queue to be built
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$this->assertNotEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp"), 0, "evpolicy_job_start_timestamp is not 0. LRU queue is built");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "lru_policy_evictable_items"), 6000, "evpolicy_job_start_timestamp is not 0. LRU queue is built");
	}
	
	// Verify adding keys within headroom limit queue is not rebuilt again and no eviction happens
	public function test_LRU_Queue_Rebuild_within_headroom_limit(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(12000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
			// Add the same set of keys 3 times to keep the memory below headroom
		for($i=0 ; $i<3 ; $i++){
			Data_generation::add_keys(12000, NULL, 1, 10240);
		}
		sleep(2);
		$evpolicy_job_start_timestamp_2 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$this->assertEquals($evpolicy_job_start_timestamp_1, $evpolicy_job_start_timestamp_2, "LRU queue is rebuilt even when memory is below headroom");
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted"), 0, "LRU queue is rebuilt even when memory is below headroom");
		$this->assertEquals(0, stats_functions::get_all_stats(TEST_HOST_1, "ep_tmp_oom_errors"), "ep_tmp_oom_errors is triggered");
		$this->assertEquals(0, stats_functions::get_all_stats(TEST_HOST_1, "ep_oom_errors"), "ep_oom_errors is triggered");
		
	}

	// Verify aggressive eviction even when RSS just crosses the border. This should evict all the keys from the active list and also rebuild the LRU queue
	//	Verify eviction fails if the queue is empty
	public function test_LRU_Agressive_Eviction_with_Queue_Rebuild(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(12000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
			// Add more keys to just cross the headroom border aggresively 
		Data_generation::add_keys(4000, NULL, 12000, 10240);	
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue is built");
		$this->assertGreaterThan(6000, stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted"), "LRU eviction failed");
		$this->assertGreaterThan(1, stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_failed_empty"), "LRU eviction failed");
		
	}	

	// Verify adding keys to cross the head room triggers eviction form the active list but doesn't rebuild the queue until active list is consumed
	// Verify active list has come down by the no of keys evicted
	public function test_LRU_Eviction_but_not_Queue_Rebuild(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(14500, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$lru_num_activelist_items_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_activelist_items");
			// Add more keys to just cross the headroom border very slowly
		for($i=14500 ; $i<16000 ; $i= $i + 100){
			Data_generation::add_keys(100, NULL, $i, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") <> 0) break;
		}
		$this->assertFalse(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue is built");
		$eviction_keys_evicted = stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted");
		$this->assertNotEquals($eviction_keys_evicted, 0, "LRU eviction failed");
		$lru_num_activelist_items_2 = stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_activelist_items");
		$lru_num_activelist_items_2 = $lru_num_activelist_items_1 - $lru_num_activelist_items_2;
		$this->assertEquals($eviction_keys_evicted, $lru_num_activelist_items_2, "Evicted key count is not equal to count drop in the active list count");
		
	}

	// Verify queue rebuild happens only after 50% of the active queue list is consumed.
	// Verify lru_num_inactivelist_items is consumed only after lru_num_activelist_items is consumed.
	public function test_LRU_Queue_Rebuild(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(9000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		Data_generation::add_keys(5000, NULL, 9000, 10240);
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$lru_num_activelist_items_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_activelist_items");
			// Add more keys to just cross the headroom border very slowly
		for($icount=14000 ; $icount<16000 ; $icount= $icount + 500){
			Data_generation::add_keys(500, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") <> 0) break;
		}
		$icount= $icount + 500;
		// Add more until 50% of lru_num_activelist_items_1 has crossed
		$no_of_keys_to_be_evicted = round($lru_num_activelist_items_1 / 2);
		for($icount=$icount ; $icount<24000 ; $icount= $icount+500){
			Data_generation::add_keys(500, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") > $no_of_keys_to_be_evicted) break;
		}
		$icount= $icount + 500;
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue is not built");
		$evpolicy_job_start_timestamp_2 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$this->assertNotEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_inactivelist_items"), 0, "LRU eviction failed");
		// verify lru_num_inactivelist_items to lru_num_activelist_items swap happens only after remaining items in lru_num_activelist_items are consumed
		// verify queue is not rebuilt only swap happens
		for($icount=$icount ; $icount<30000 ; $icount= $icount+500){
			Data_generation::add_keys(500, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_inactivelist_items") == 0) break;
		}
		$this->assertGreaterThan(9000, stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted"), "LRU eviction failed");
		$this->assertFalse(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_2), "LRU queue is not built");
	}
	
	// Verify queue rebuild is not triggered until the specified percentage is met (80%)
	public function test_LRU_Queue_Rebuild_80(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "lru_rebuild_percent", 20);
		
		Data_generation::add_keys(9000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		Data_generation::add_keys(5000, NULL, 9000, 10240);
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$lru_num_activelist_items_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_activelist_items");
			// Add more keys to just cross the headroom border very slowly
		for($icount=14000 ; $icount<30000 ; $icount= $icount + 300){
			Data_generation::add_keys(300, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_inactivelist_items") <> 0) break;
		}
		$no_of_keys_tobe_evicted = 9000 * 0.8;
		$eviction_keys_evicted = stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted");
		$this->assertGreaterThan($no_of_keys_tobe_evicted, $eviction_keys_evicted, "LRU eviction failed");
		$this->assertLessThan(9000, $eviction_keys_evicted, "LRU eviction failed");
	}	

	// Verify BG fetch is successful and triggers eviction when RSS crosses the headroom
	public function test_BG_fetch() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(9000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		Data_generation::add_keys(5500, NULL, 9000, 10240);
			// Add more keys to just cross the headroom border very slowly
		for($icount=14500 ; $icount<16000 ; $icount= $icount + 500){
			Data_generation::add_keys(500, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") <> 0) break;
		}
		
		$instance = Connection::getMaster();
		$eviction_keys_evicted_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted");
		
		for($ikey=1 ; $ikey<$eviction_keys_evicted_1 + 1; $ikey++){
			if($instance->get("testkey_".$ikey) == False){
				$this->assertTrue(False, "Failed to fetch key from disk testkey_".$ikey);
			}
			usleep(700);
		}
		sleep(2);
			
		$eviction_keys_evicted_2 = stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted");
		$this->assertGreaterThan($eviction_keys_evicted_1 / 2, stats_functions::get_all_stats(TEST_HOST_1, "ep_bg_fetched"), "bg fetch (positive)");
		$this->assertGreaterThanorEqual($eviction_keys_evicted_1, $eviction_keys_evicted_2, "LRU eviction failed");
	}	
	
	// Verify eviction on deleted keys
	public function test_eviction_on_deleted_keys() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		$instance = Connection::getMaster();
		Data_generation::add_keys(9000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		Data_generation::delete_keys(2000, 1);
		Data_generation::add_keys(5500, NULL, 9000, 10240);
			// Add more keys to just cross the headroom border very slowly
		for($icount=14500 ; $icount<20000 ; $icount= $icount + 500){
			Data_generation::add_keys(500, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") <> 0) break;
		}
		$this->assertGreaterThan(1998, stats_functions::get_all_stats(TEST_HOST_1, "eviction_failed_key_absent"), "eviction_failed_key_absent not equal to keys deleted");

	}	
	
	// Verify eviction on expiried keys 
	public function test_eviction_on_expiried_keys() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "10");
		$instance = Connection::getMaster();
			// add 5 keys with expiry
		for($ikey=1; $ikey<11 ; $ikey++){
			$instance->set("testkey_$ikey", "testvaluetestvaluetestvalue", 0, 3);
		}
		for($ikey=11; $ikey<21 ; $ikey++){
			$instance->set("testkey_$ikey", "testvaluetestvaluetestvalue", 0, 30);
		}		
		Data_generation::add_keys(8980, NULL, 21, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$this->assertEquals(8990, stats_functions::get_all_stats(TEST_HOST_1, "lru_policy_evictable_items"), "lru_policy_evictable_items");
		Data_generation::add_keys(5500, NULL, 9000, 10240);
		sleep(30); // wait for keys to expiry
			// Add more keys to just cross the headroom border very slowly
		for($icount=14500 ; $icount<16000 ; $icount= $icount + 500){
			Data_generation::add_keys(500, NULL, $icount, 10240);
			sleep(3);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") <> 0) break;
		}
		$this->assertEquals(10, stats_functions::get_all_stats(TEST_HOST_1, "eviction_failed_key_absent"), "eviction_failed_key_absent not equal to keys expiried");

	}	
	
	// Verify deleted keys are omitted from next rebuild	
	public function test_deleted_keys_next_rebuild() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(9000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		Data_generation::add_keys(6000, NULL, 9000, 10240);
		Data_generation::delete_keys(2000, 9000);
		Data_generation::add_keys(6000, NULL, 15000, 10240);
		$this->assertLessThanorEqual(10000, stats_functions::get_all_stats(TEST_HOST_1, "lru_policy_evictable_items"), "lru_policy_evictable_items didn't exclude deleted items");
	
	}	
	
	// verify expiried keys are omitted from the next rebuild
	// Add 23000 keys out of which 2000 keys are set with 9s expiry. Wait for expiry_pager to run. Ensure rebuild computes only 21000 keys. 
	public function test_expiried_keys_next_rebuild() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "exp_pager_stime", "10");		
		Data_generation::add_keys(9000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$instance = Connection::getMaster();
		$testvalue = Data_generation::generate_data(10240);
		for($ikey=9000; $ikey<11000 ; $ikey++){
			$instance->set("testkey_$ikey", $testvalue, 0, 9);
		}		
		sleep(10);
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		Data_generation::add_keys(12000, NULL, 11000, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue building failed");
		
		$eviction_keys_evicted = stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted");
		$lru_num_activelist_items = stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_activelist_items");
		$lru_num_inactivelist_items = stats_functions::get_eviction_stats(TEST_HOST_1, "lru_num_inactivelist_items");
		$total_keys = $lru_num_inactivelist_items + $lru_num_activelist_items + $eviction_keys_evicted;
		$this->assertLessThan(22000, $total_keys, "Expiried keys were considered LRU rebuild"); // To avoid duplicate keys active and inactive list 1000 extra is considered
		
	}	

	// Verify Min blob size. 
	// Set min blob size to 11k. Add keys to cross the headroom limit. Ensure eviction doesn't happen. Lower the value to 9k. Ensure eviction happens
	public function test_Min_Blob_Size() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "evict_min_blob_size", "11264");
		Data_generation::add_keys(14000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		for($icount=14000 ; $icount<16000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
		}
		$this->assertGreaterThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS_memory_size is less than 250MB");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_keys_evicted"), 0, "Eviction of keys succeded");
		$this->assertNotEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_failed_empty"), 0, "eviction_failed_empty should not be 0");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "evict_min_blob_size", "9216");
		for($icount=16000 ; $icount<19000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
		}
		$this->assertNotEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_keys_evicted"), 0, "Eviction failed after reseting min blob size to 5");
		$this->assertLessThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS_memory_size is less than 250MB");
			
	}	

	// Verify max_queue_size dynamic
	// set max_evict_entries to 1000 and ensure list is built max of 1000. Ensure changing the value, rebuild list is not 1000 anymore.
	public function test_Max_Queue_Size() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "max_evict_entries", "1000");
		Data_generation::add_keys(14000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_max_queue_size"), 1000, "eviction_max_queue_size is not equal to 1000");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "max_evict_entries", "10000");
		Data_generation::add_keys(4000, NULL, 14000, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue building failed");
		$this->assertGreaterThan(1000, stats_functions::get_all_stats(TEST_HOST_1, "eviction_max_queue_size"), "eviction_max_queue_size is not greater than 1000");	
	}	

	// verify lru_rebuild_stime
	// Set lru_rebuild_stime to 5 and ensure background swap happens every 5s. And ensure it doesn't run within 5s when set to higher value.
	public function test_LRU_rebuild_time() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "lru_rebuild_stime", "5");
		Data_generation::add_keys(14000, NULL, 1, 10240);
		$lru_policy_background_swaps_1 = stats_functions::get_all_stats(TEST_HOST_1, "lru_policy_background_swaps");
		sleep(11);
		$this->assertGreaterThan($lru_policy_background_swaps_1, stats_functions::get_all_stats(TEST_HOST_1, "lru_policy_background_swaps"), "background swap didn't increase after 5s");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "lru_rebuild_stime", "600");
		$lru_policy_background_swaps_1 = stats_functions::get_all_stats(TEST_HOST_1, "lru_policy_background_swaps");
		for($icount=$lru_policy_background_swaps_1; $icount<$lru_policy_background_swaps_1 + 3 ; $icount++){
			sleep(6);
			$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1, "lru_policy_background_swaps"), $lru_policy_background_swaps_1, "background swap increased after 10s");
		}		
	}	
    
	//Verify prune_lru_age    
	// Add keys to build the LRU list. Prune few keys. Verify LRU queue is built again after prune
	public function test_Prune_LRU_Age() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(2000, NULL, 1, 10240);
		$now = time();
		sleep(1);
		Data_generation::add_keys(6000, NULL, 2000, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "prune_lru_age", $now);
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1, 20), "LRU queue building failed");
		$this->assertGreaterThan(0, stats_functions::get_all_stats(TEST_HOST_1, "eviction_num_keys_pruned"));
		$this->assertLessThan(2001, stats_functions::get_all_stats(TEST_HOST_1, "eviction_num_keys_pruned"));
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_prune_runs"), 1);
		
	}    	
	
	// Verify prune fails on already evicted keys
	public function test_Prune_on_Already_Evicted_Keys() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(2000, NULL, 1, 10240);
		$now = time();
		sleep(1);
		Data_generation::add_keys(6000, NULL, 2000, 10240);
		for($icount=8000 ; $icount<19000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			if(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted") > 2000) break;
		}

		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "prune_lru_age", $now);
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue building failed");
		$this->assertEquals(0, stats_functions::get_all_stats(TEST_HOST_1, "eviction_num_keys_pruned"));
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_prune_runs"), 1);
		
	}

	// Verify disable_inline_eviction parameter. Eviction doesn't happen if this is enabled
	public function test_disable_inline_eviction_param() {
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "disable_inline_eviction", 1); 
		Data_generation::add_keys(14000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		for($icount=14000 ; $icount<19000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			if(zbase_function::get_zbase_memory(TEST_HOST_1, "MB") > 460) break;
		}
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted"), 0, "disable_inline_eviction param  Failed");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "disable_inline_eviction", 0); 
		Data_generation::add_keys(1, NULL, 1, 10240);
		$this->assertNotEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_keys_evicted"), 0, "disable_inline_eviction param  Failed");
		
	}

	//Verify varying eviction_headroom triggers eviction accordingly
	// Set to 150MB and verify memory doesn't touch 375MB
	public function test_eviction_headroom() {
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_headroom", 157286400); 
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1, "eviction_headroom"), 157286400, "eviction_headroom is not set with 150MB");
		Data_generation::add_keys(6000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		for($icount=6000 ; $icount<14000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			$this->assertLessThan(375, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "Memory crossed 450MB");
		}
	}	
	
	// Add few keys to build LRU queue. Stop peristance and add more keys until all the keys in LRU list is evicted. Verify further eviction fails.
	// Start persistance and add more keys. Verify eviction happens successfully.
	public function test_Eviction_Dirty_Keys() {  
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(6000, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
			// Stop peristance and verify eviction happens only for 6k keys and fails for rest
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "stop");
		for($icount=6000 ; $icount<14000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			if(stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted") >= 5999 ) break;
		}
		$eviction_keys_evicted = stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted");
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		for($icount=$curr_items ; $icount<21000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			if(zbase_function::get_zbase_memory(TEST_HOST_1, "MB") > 450 ) break;
		}
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1), "LRU queue building failed");
		$this->assertNotEquals(stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_failed_dirty"), 0, "eviction_failed_dirty is 0 with persistance stopped");
		$this->assertLessThan(6000, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted"), "Eviction succeded with persistance stopped");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1);
		
			// Start persistance and verify eviction happens 
		$eviction_failed_dirty = stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_failed_dirty");
		flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "start");	
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
			// wait until all the keys persist
		for($i=0 ; $i<200 ; $i++){
			if(stats_functions::get_all_stats(TEST_HOST_1, "ep_queue_size") == 0 && stats_functions::get_all_stats(TEST_HOST_1, "ep_flusher_todo") == 0) break;
			sleep(1);
		}
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		for($icount=$curr_items ; $icount<$curr_items + 6000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			if(zbase_function::get_zbase_memory(TEST_HOST_1, "MB") > 450 ) break;
		}
		$this->assertEquals(stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_failed_dirty"), $eviction_failed_dirty, "eviction_failed_dirty has increased with persistance started");
		$this->assertGreaterThan($eviction_keys_evicted, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted"), "Eviction failed with persistance started");		
		
	}
	
	
	//Disable eviction_job parameter and verify LRU queue is not built when headroom is crossed
	//Enable it back and verify LRU queue is built
	public function test_enable_eviction_job_param() {
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "enable_eviction_job", 0);
		Data_generation::add_keys(14000, NULL, 1, 10240);
		for($icount=14000 ; $icount<21000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			sleep(1);
			if(zbase_function::get_zbase_memory(TEST_HOST_1, "MB") > 450 ) break;
		}		
		$this->assertFalse(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		
		// Enable it back and verify LRU queue is built
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "enable_eviction_job", 1);
		Data_generation::add_keys(1, NULL, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		
	}	
	
	
    // Verify switching eviction_policy eviction behaves as desired
	//Set policy to bgeviction and add keys until mem_used crosses ep_mem_high_wat. Verify eviction happens. Verify RSS memory is above 450MB.
	//Switch policy to LRU, add few more keys. Eviction is triggered and RSS comes down below 450MB.
	public function test_eviction_policy(){
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_policy", "bgeviction");
		$this->assertNotEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_policy"), "bgeviction", "eviction_policy doesn't match to bgeviction");
		Data_generation::add_keys(20000, NULL, 1, 10240);
		for($icount=20000 ; $icount<30000 ; $icount= $icount + 1000){
			Data_generation::add_keys(1000, NULL, $icount, 10240);
			if(stats_functions::get_all_stats(TEST_HOST_1, "bg_evictions") > 0) break;
			sleep(1);
		}		
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$this->assertLessThan(393216000,stats_functions::get_all_stats(TEST_HOST_1, "mem_used"), "mem_used didn't come down below high wat");
		$this->assertGreaterThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS memory is not greater than 450MB");
		$evpolicy_job_start_timestamp_1 = stats_functions::get_eviction_stats(TEST_HOST_1, "evpolicy_job_start_timestamp");
		
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_policy", "lru");
		$this->assertNotEquals(stats_functions::get_eviction_stats(TEST_HOST_1, "eviction_policy"), "lru", "eviction_policy doesn't match to lru");
		
		$curr_items = stats_functions::get_all_stats(TEST_HOST_1, "curr_items");
		for($icount=$curr_items ; $icount<$curr_items + 6000 ; $icount= $icount + 100){
			Data_generation::add_keys(100, NULL, $icount, 10240);
			if(zbase_function::get_zbase_memory(TEST_HOST_1, "MB") < 450 ) break;
			sleep(1);
		}		
		
		zbase_function::wait_for_LRU_queue_build(TEST_HOST_1, $evpolicy_job_start_timestamp_1);
		$this->assertLessThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS memory is not greater than 450MB");
		$this->assertGreaterThan(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted"), "Eviction failed after switching the policy");	

	}
	

	// Verify eviction fails when all items are in 1 closed + 1 open checkpoint
	public function test_Eviction_In_Checkpoint_One_Closed_One_Open() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "9000");
		Data_generation::add_keys(16000, 9000, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$this->assertEquals(16002, stats_functions::get_checkpoint_stats(TEST_HOST_1,  "num_checkpoint_items"), "Eviction succeded with items still in checkpoint");
		$this->assertEquals(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted"), "Eviction succeded with items still in checkpoint");
		$this->assertGreaterThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS memory is not greater than 450MB");
		$this->assertGreaterThan(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_failed_in_checkpoints"), "eviction_failed_in_checkpoints is zero");
	}

	// Verify eviction fails when all items are in 1 closed + 1 open checkpoint with cursor registered
	public function test_Eviction_In_Checkpoint_One_Closed_One_Open_with_cursor() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "9000");
		tap_commands::register_backup_tap_name(TEST_HOST_1);
		Data_generation::add_keys(16000, 9000, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$this->assertEquals(16002, stats_functions::get_checkpoint_stats(TEST_HOST_1,  "num_checkpoint_items"), "Eviction succeded with items still in checkpoint");
		$this->assertEquals(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted"), "Eviction succeded with items still in checkpoint");
		$this->assertGreaterThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS memory is not greater than 450MB");
		$this->assertGreaterThan(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_failed_in_checkpoints"), "eviction_failed_in_checkpoints is zero");
	}	
	
	// Verify eviction fails when all items are in open checkpoint
	public function test_Eviction_In_Open_Checkpoint() { 
		zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "17000");
		Data_generation::add_keys(16000, 17000, 1, 10240);
		$this->assertTrue(zbase_function::wait_for_LRU_queue_build(TEST_HOST_1), "LRU queue building failed");
		$this->assertEquals(16001, stats_functions::get_checkpoint_stats(TEST_HOST_1,  "num_checkpoint_items"), "Eviction succeded with items still in checkpoint");
		$this->assertEquals(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_keys_evicted"), "Eviction succeded with items still in checkpoint");
		$this->assertGreaterThan(450, zbase_function::get_zbase_memory(TEST_HOST_1, "MB"), "RSS memory is not greater than 450MB");
		$this->assertGreaterThan(0, stats_functions::get_eviction_stats(TEST_HOST_1,  "eviction_failed_in_checkpoints"), "eviction_failed_in_checkpoints is zero");
	}	
	

}

class LRU_Basic_TestCase_Full extends LRU_Basic_TestCase {
	public function keyProvider() {  
		return Utility::provideKeys();
	}
}
?>

