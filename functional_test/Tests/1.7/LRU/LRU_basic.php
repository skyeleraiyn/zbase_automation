<?php
abstract class LRU_Basic_TestCase extends Zstore_TestCase {

	// Aim : To test basic LRU functionality
	// Output: Observer Queue built, keys evicted, oldest key evicted and bg_fetches happening
	public function test_Basic_LRU_Evict() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(699, 100, 2, 10240);
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0, "Build LRU Queue (positive)");
		Data_generation::add_keys(184,100,701,10240);
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"), 0, "Keys Evicted (positive)");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "key_resident", "testkey_1"), 0, "Oldest Key Evicted (positive)");
		$val = Data_generation::get_key("testkey_1");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "ep_bg_fetched"), 1, "Evicted Key fetched from memory (positive)");
	}
	
	//Aim : To test lru_mem_threshold_percent param
	// Output: Observe queue is built as soon as mem_threshold percent is hit
	public function test_LRU_mem_threshold_dynamic() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"lru_mem_threshold_percent", "20");
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(279, 100, 2, 10240);
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0, "Build LRU Queue (positive)");
	}
	
	//Aim: To Observe the size of the queue
	//Output: Observe that  the queue is built with the expected size.
	public function test_LRU_Queue_Size() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), "700", "LRU Queue size (positive)");
	}					

	//Aim: To test Rebuild functionality when queue_size is smaller
	//Output: Observe the queue geting rebuilt
	public function test_LRU_Queue_Rebuild() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(699, 100, 2, 10240);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		Data_generation::add_keys(184,100,701,10240);
		$start_time = (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$id = 885;
		while(1) {
			if((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_evictable_items") <= ($queue_size/2))
			break;
			Data_generation::add_keys(1, 100, $id, 10240);
			$id++;
		}
		$this->assertGreaterThan($start_time, (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp"), "LRU Queue Rebuild (positive)");
	}
	
	// Aim : To test lru_rebuild_percent params
	// Output: Observe queue rebuild in conformance with the parameter
	public function test_LRU_Queueu_Rebuild_dynamic() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"lru_rebuild_percent", "80");
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(699, 100, 2, 10240);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		Data_generation::add_keys(184,100,701,10240);
		$start_time = (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$id = 885;
		while(1) {
			if((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_evictable_items") <= ($queue_size*0.8))
			break;
			Data_generation::add_keys(1, 100, $id, 10240);
			$id++;
		}
		sleep(10);
		$this->assertGreaterThan($start_time, (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp"), "LRU Queue Rebuild Dynamic (positive)");
	}
	
	// Aim: To test bg_fetches are successful
	// Output: Observe bg_fetches succeed without going OOM
	public function test_bg_fetch() { 
		$get_error = False;
		$oom_error = False;
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(999, 100, 2, 10240);
		$this->assertLessThan(90, (int)stats_functions::get_stat(TEST_HOST_1, "vb_active_perc_mem_resident"), "percentage resident (positive)");
		for($key = 1; $key <= 1000; $key++) {
			$res = Data_generation::get_key("testkey_".$key);
			if($res == False) {
				$get_error = True;		
				break;
			}
			if((int)stats_functions::get_stat(TEST_HOST_1, "mem_used") >= 21911044) {
				$oom_error = True;
				break;
			}
		}
		$this->assertFalse($get_error, "get failed on key $key ");
		$this->assertFalse($oom_error, "Went OOM on getting key $key");
		$this->assertGreaterThan(100, (int)stats_functions::get_stat(TEST_HOST_1, "ep_bg_fetched"), "bg fetch (positive)");
	}	
	
	// Aim: To test memory under watermark operations
	// Output: Observe normal operations without going OOM
	public function test_mem_below_watermark() { 
		$get_error = False;
		$key_evicted_error = False;
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(759, 100, 2, 10240);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "vb_active_perc_mem_resident"), 100, "active_perc_mem_resident (positive)");
		for($key = 1; $key <= 760; $key++) {
			$res = Data_generation::get_key("testkey_".$key);
			if($res == False) {
				$get_error = True;
				break;
			}
			if((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted") <> 0) {
				$key_evicted_error = True;
				break;
			}
		}
		$this->assertFalse($get_error, "get failed on key $key ");
		$this->assertFalse($key_evicted_error, "Key evicted when operating under watermark"); 
		$this->assertLessThan(17288920, (int)stats_functions::get_stat(TEST_HOST_1, "mem_used"), "Went above watermark");
	}

	// Aim: to Test whether deleted items are omitted in the next queue build
	// Output: Observe that deleted items are not present in the next queue rebuild
	public function test_Deleted_Items_In_Queue() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(699, 100, 2, 10240);
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0, "Build LRU Queue (positive)");
		Data_generation::delete_keys(350, 100, 1);
		Data_generation::add_keys(650, 100, 701, 10240);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_key_absent"), 350, "Deleted items in Queue (positive)");
	}
	
	// Aim: Verify that eviction fails when all items are in checkpoints
	// Output: Observe eviction_failed_in_checkpoints and that oldest key is not evicted
	public function test_Items_In_Checkpoints() {
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "1000");
		Data_generation::add_keys(1, 1000, 1, 10240);
		Data_generation::add_keys(699, 1000, 2, 10240);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		$this->assertNotEquals($queue_size, 0, "Build LRU Queue (positive)");
		Data_generation::add_keys(183, 1000, 701, 10240);
		Data_generation::add_keys(100, 1000, 884, 10240);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"), 0, "First Key Not Evicted (positive)");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_in_checkpoints"), $queue_size, "Failed policy ineligible (positive)");
	}

	//Aim: To verify eviction fails if the queue is empty
	// Output: Observe failed_empty and failed_in_checkpoints counter incerementing as expected.
	public function test_Eviction_Queue_Empty() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", "1000");
		Data_generation::add_keys(1, 1000, 1, 10240);
		Data_generation::add_keys(699, 1000, 2, 10240);
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0, "Build LRU Queue (positive)");
		Data_generation::add_keys(183,1000,701,10240);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		$failed_empty = (int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_empty");
		Data_generation::add_keys(1,1000,884,10240);
		sleep(5);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_empty"), ($failed_empty+1), "eviction_failed_empty counter (positive)");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_in_checkpoints"), $queue_size, "eviction_failed_in_checkpoints (positive)");
		$job_time_stamp_2 = (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp");
		Data_generation::add_keys(1,1000,885,10240);
		sleep(5);
		$job_time_stamp_3 = (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_empty"), ($failed_empty+2), "test_Eviction_Queue_Empty (positive)");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_in_checkpoints"), $queue_size, "test_Eviction_Queue_Empty (positive)");
		$this->assertGreaterThan($job_time_stamp_2, $job_time_stamp_3, "Queue Rebuild 2 (positive)");
	}
	
	//Aim: To verify min_blob_size parameter is honoured
	//Output: Veriry that evictions fail if the itesm are lesser in size that min_blob_size
	public function test_Min_Blob_Size() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "evict_min_blob_size", "1048576");
		Data_generation::add_keys(1, 100, 1, 10240);
		Data_generation::add_keys(699, 100, 2, 10240);
		Data_generation::add_keys(184, 100, 701, 10240);
		Data_generation::add_keys(200, 100, 885, 10240);
		$this->assertGreaterThanOrEqual((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_empty"), 205, "Eviction failed (positive)");
		$this->assertLessThan((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_empty"), 195, "Eviction failed (positive)");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0,  "Queue not built (positive)");
	}
	
	// Aim: To Toggle from bg eviction to lru and ensure things are stable.
	// Expected Output: Stable behaviour after on the fly changes in policy
	public function test_bg_to_lru() {
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_policy", "bgeviction");
		sleep(10);
		$this->assertEquals(stats_functions::get_stat(TEST_HOST_1, "eviction_policy"), "bgeviction", "Eviction policy lru (positive)");
		Data_generation::add_keys(1100, 100, 1, 10240);
		for($key = 1; $key <= 1100; $key++) {
			Data_generation::get_key("testkey_".$key);
			if($key == 500) {
				flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_policy", "lru");
				sleep(15);
				$this->assertEquals(stats_functions::get_stat(TEST_HOST_1, "eviction_policy"), "lru", "Eviction policy bgeviction (positive)");
				$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0,  "lru Queue built (positive)");
			}
		}
		Data_generation::add_keys(10, 100, 1101, 10240);
		$this->assertLessThan(90, (int)stats_functions::get_stat(TEST_HOST_1, "vb_active_perc_mem_resident"), "percentage resident (positive)");
	}	

	// Aim: To Toggle from lru  to bg eviction and ensure things are stable.
	// Expected Output: Stable behaviour after on the fly changes in policy
	public function test_lru_to_bg() {
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		$high_wat_err = False;
		$low_wat_err = False;
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_policy", "lru");
		sleep(10);
		$this->assertEquals(stats_functions::get_stat(TEST_HOST_1, "eviction_policy"), "lru", "Eviction policy lru (positive)");
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 0,  "lru Queue built (positive)");
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		Data_generation::add_keys(183, 100, 701, 10240);
		sleep(5);
		for($key = 1; $key <= 883; $key++) {
			Data_generation::get_key("testkey_".$key);
			if($key == 500) {
				flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "eviction_policy", "bgeviction");
				sleep(15);
				$this->assertEquals(stats_functions::get_stat(TEST_HOST_1, "eviction_policy"), "bgeviction", "Eviction policy lru (positive)");
				Data_generation::add_keys(300, 100, 884, 10240);
			}
			if($key > 500) {
				if((int)stats_functions::get_stat(TEST_HOST_1, "mem_used")<16433283) {
					$low_wat_err = True;
					break;
				}
				if((int)stats_functions::get_stat(TEST_HOST_1, "mem_used")>20541604) {
					$high_wat_err = True;
					break;
				}
			}
		}
		$this->assertFalse($low_wat_err,"Lesser than low water mark in bgeviction");
		$this->assertFalse($high_wat_err, "Greater than high watermark in bg_eviction");
	}

	// Aim: To test whether max_evict_entries are honoured
	// Output: To verify that queue_size 
	public function test_Verify_Max_Queue_Size() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "max_evict_entries", "100");
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 100,  "Queue built with limited queue_size (positive)");
	}
	
	// Aim: To test whether a change in max_evict_entries is honoured
	// Output: Observe the queue is rebuilt with the queue_size parameter
	public function test_Verify_Max_Queue_Size_Dynamic() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 700,  "First Queue built (positive)");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "max_evict_entries", "100");
		Data_generation::add_keys(550, 100, 701, 10240);
		sleep(10);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), 100,  "Second Queue built (positive)");
	}
	
	//Aim: To test queue_rebuil_stime parameter
	//  Output: Observe that the queue is rebuilt within stime
	public function test_Queue_Rebuild_Frequency() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "lru_rebuild_stime","2");
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		$job_time_1 = (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp");
		sleep(2);	
		$this->assertGreaterThan($job_time_1, (int)stats_functions::get_stat(TEST_HOST_1, "evpolicy_job_start_timestamp"), " lru rebuild stime (positive)");
	}
	
	// Aim: To test prune funcionality
	// Output: Observe that the older keys are pruned
	public function test_Prune_Functionality_And_Already_Evicted() { 
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		Data_generation::add_keys(183, 100, 701, 10240);
		$now= time();
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "prune_lru_age", $now);
		sleep(10);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		$this->assertEquals($queue_size,(int)stats_functions::get_stat(TEST_HOST_1, "eviction_num_keys_pruned"), "Prune Functionality (positive)");
		$this->assertEquals($queue_size, (int)stats_functions::get_stat(TEST_HOST_1, "vb_active_num_non_resident"), "Non Resident Keys (positive)");
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		Data_generation::add_keys(183, 100, 701, 10240);
		$this->assertEquals($queue_size,(int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_policy_ineligible"), "Failed because ineligible (positive)");
	}
	
	// Aim: To test disable_inline_eviction parameter
	//Output: To verify expected behaviour after toggling disable_inline_eviction parameter
	public function test_disable_inline_eviction_param() {
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "disable_inline_eviction", 1); 
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		$this->assertEquals($queue_size,700, "queue not built");
		Data_generation::add_keys(300, 100, 701, 10240);
		$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"), 0, "disable_inline_eviction param (positive)");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "disable_inline_eviction", 0);
		sleep(5);
		Data_generation::add_keys(1, 100, 1001, 10240);
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"), 0, "disable_inline_eviction param (positive)");
	}
	
	//Aim: To test enable_eviction_job parameter
	// Output: To verify expected behaviour after toggling eviction
	public function test_enable_eviction_job_param() {
		membase_function::reset_membase_servers(array(TEST_HOST_1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "lru_rebuild_stime", 10);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "enable_eviction_job", 0);
		Data_generation::add_keys(1, 100, 1, 10240);
		sleep(6);
		Data_generation::add_keys(699, 100, 2, 10240);
		sleep(10);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		$this->assertEquals($queue_size, 0, "queue built with enable_eviction_job set to false");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "enable_eviction_job", 1);
		Data_generation::add_keys(183, 100, 701, 10240);
		sleep(10);
		Data_generation::add_keys(100, 100, 884, 10240);
		sleep(10);
		$queue_size = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
		$this->assertNotEquals($queue_size, 0, "queue not built after toggling enable_eviction_job");
		$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"), 0, "Keys Evicted (positive)");
	}	




	// Need to find out
	/*

public function test_Expired_Items_In_Queue() { echo "test 
membase_function::reset_membase_servers(array(TEST_HOST_1));
echo "start";
echo shell_exec("date");
Data_generation::add_keys(700,100,1,10240,90);
echo shell_exec("date");
sleep(10);
$queue = (int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size");
echo "queue built $queue";
if($queue == 0)
exit;
sleep(90);
Data_generation:_:add_keys(183,100,701,10240);
echo "\nwater mark";
sleep(6);
Data_generation::add_keys(($queue/2),100,884,10240);
echo "\nqueue by 2 keys added";
sleep(5);
$this->assertGreaterThanOrEqual((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"),(($queue/2) - 5) , "Keys Evicted (positive)");		
$this->assertLessThan((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"),(($queue/2) + 5), "Keys Evicted (positive)");
$this->assertGreaterThanOrEqual((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), (($queue/2) + 178), "Queue Rebuild (positive)");
$this->assertLessThan((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size"), (($queue/2) + 187), "Queue Rebuild (positive)");
}	


public function test_Eviction_Dirty_Keys() { echo "test 
membase_function::reset_membase_servers(array(TEST_HOST_1));
Data_generation::add_keys(1, 100, 1, 10240);
sleep(6);
Data_generation::add_keys(99, 100, 2, 10240);
sleep(6);
Data_generation::add_keys(100, 100, 101, 10240);
sleep(6);
Data_generation::add_keys(100, 100, 201, 10240);
sleep(6);
Data_generation::add_keys(400, 100, 301, 10240);
sleep(10);
echo (int)stats_functions::get_stat(TEST_HOST_1, "mem_used");
if ((int)stats_functions::get_stat(TEST_HOST_1, "lru_policy_ev_queue_size") == 0) {
echo "Queue not built";
return 0;
}
else
echo "queue built";
return 1;
Data_generation::add_keys(183, 100, 701, 10240);
sleep(6);
$this->assertEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted"), 0, "Watermark (positive)");
Data_generation::add_keys(100, 100, 884, 10240);
sleep(6);
$evicts = (int)stats_functions::get_stat(TEST_HOST_1, "eviction_keys_evicted");
$this->assertGreaterThanOrEqual($evicts, 105, "Keys Evicted (positive)");
$this->assertLessThan($evicts, 95, "Keys Evicted (positive)");
flushctl_commands::Start_Stop_Persistance(TEST_HOST_1, "stop");
Data_generation::add_keys($evicts, 100, 101, 10240);
sleep(6);
Data_generation::add_keys($evicts, 100, 984, 10240);
$this->assertNotEquals((int)stats_functions::get_stat(TEST_HOST_1, "eviction_failed_dirty"), 100, "Dirty Keys Found (positive)");
}

*/

}

class LRU_Basic_TestCase_Full extends LRU_Basic_TestCase {
	public function keyProvider() {  
		return Utility::provideKeys();
	}
}
?>
