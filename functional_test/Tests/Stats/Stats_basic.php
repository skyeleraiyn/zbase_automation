<?php
	
abstract class Stats_basic_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider keyProvider
	*/

	public function test_Min_Data_Age($testKey) {
			
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"min_data_age", "15");
		$instance = Connection::getMaster();
		$instance->set($testKey,"test_value");
			// ensure key is not persisted before the min_data_age has reached
		sleep(12);
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_total_persisted"), "0", "set min_data_age(positive)");
			// ensure key is persisted after the min_data_age time
		sleep(4);
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_total_persisted"), "1", "set min_data_age(negative)");

	}
        /**
        * @dataProvider keyProvider
        */
	public function test_Queue_Age_Cap($testKey) {
	
		$instance = Connection::getMaster();
		// set a key and persist it
		$instance->set($testKey,"testvalue");
		
		// Allow the key to be persisted
		sleep(1);
		
		// change the value of min_data_age and queue_age_cap
        flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"min_data_age", "15");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"queue_age_cap", "20");
		
		for($attempt_count=0; $attempt_count<=3; $attempt_count++){
			$instance->set($testKey,"iter_$attempt_count");
			sleep(5);
		}
		sleep(1);
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_total_persisted"), 1, "key persisted after 15 secs with multiple sets");
		sleep(4);
        $this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_total_persisted"), 2, "key didn't persist after queue_age_cap 20 secs with multiple sets");
        
	}
        
        
	public function test_Expiry_Pager_Stime() {
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_num_expiry_pager_runs"), "0", "exp_pager_stime(negative)");
        flushctl_commands::set_flushctl_parameters(TEST_HOST_1,"exp_pager_stime", "10");
		Data_generation::add_keys(10);
		sleep(10);
		$this->assertEquals(stats_functions::get_all_stats(TEST_HOST_1,"ep_num_expiry_pager_runs"), "1", "exp_pager_stime(positive)");
	}

		
}


class Stats_basic_TestCase_Quick extends Stats_basic_TestCase {

	public function keyProvider() {
		return array(array("test_key"));
	}

}

?>

