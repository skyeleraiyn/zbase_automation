<?php

abstract class Python_Scripts_TestCase extends Zstore_TestCase {
	
	// Tap administration script Testcases
	
	public function test_tap_registration() {
		$this->assertTrue(Management_scripts::verify_tap_admin("-r replication -l 0 -b"),"mbadm-tap-registration(registering) failed"); 	
	}
	public function test_tap_deregistration() {
		$this->assertTrue(Management_scripts::verify_tap_admin("-d replication"),"mbadm-tap-registration(deregistering) failed"); 	
	}

	// mbflushctl script Testcases
	
	public function test_start_persistence() {
		$this->assertTrue(Management_scripts::verify_mbflusctl("start"),"mbflushctl start failed"); 	
	}
	public function test_stop_persistence() {
		$this->assertTrue(Management_scripts::verify_mbflusctl("stop"),"mbflushctl stop failed"); 	
	}
	public function test_evict_key() {
		$instance= $this->sharedFixture;
		$instance->set("test_key","test_value");
		sleep(1);
		$this->assertTrue(Management_scripts::verify_mbflusctl("evict test_key"),"mbflushctl evict failed"); 	
	}
	public function test_set_min_data_age () {
		$this->assertTrue(Management_scripts::verify_mbflusctl("set min_data_age 600"), "set min_data_age failed");
	}
	public function test_set_queue_age_cap () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set queue_age_cap 600"),"set queue_age_cap failed");
	}
	public function test_set_bg_fetch_delay () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set bg_fetch_delay 1"),"set bg_fetch_delay failed");
	}
	public function test_set_mem_high_wat () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set mem_high_wat 123456"),"set mem_high_wat failed");
	}
	public function test_set_mem_low_wat () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set mem_low_wat 12000"),"set mem_low_wat failed");
	}
	public function test_set_exp_pager_stime () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set exp_pager_stime 100"),"set exp_pager_stime failed");
	}
/*	LRU testcases

	public function test_set_lru_rebuild_stime () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set lru_rebuild_stime 360"),"set lru_rebuild_stime failed");
	}
	public function test_set_max_evict_entries () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set max_evict_entries 2000"),"set max_evict_entries failed");
	}
	public function test_set_enable_eviction_job () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set enable_eviction_job 1"),"set enable_eviction_job failed");
	}
	public function test_set_eviction_policy () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set eviction_policy lru"),"set eviction_policy failed");
	}
	public function test_set_eviction_headroom () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set eviction_headroom 100000"),"set eviction_headroom failed");
	}
	public function test_set_disable_inline_eviction () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set disable_inline_eviction 0"),"set disable_inline_eviction failed");
	}
	public function test_set_prune_lru_age () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set prune_lru_age 100"),"set prune_lru_age failed");
	}
	public function test_set_lru_rebuild_percent () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set lru_rebuild_percent 71"),"set lru_rebuild_percent failed");
	}
	public function test_set_lru_mem_threshold_percent () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set lru_mem_threshold_percent 65"),"set lru_mem_threshold_percent failed");
	}
	public function test_set_evict_min_blob_size () {
			$this->assertTrue(Management_scripts::verify_mbflusctl("set evict_min_blob_size 10"),"set evict_min_blob_size failed");
	}
*/	
	// mbstats script Testcases
	
	public function test_stat_all() {
		$this->assertTrue(Management_scripts::verify_stat("all"),"mbstats all failed"); 	
	}
	public function test_stat_checkpoint() {
		$this->assertTrue(Management_scripts::verify_stat("checkpoint"),"mbstats checkpoint failed"); 	
	}
	public function test_stat_timings() {
		$this->assertTrue(Management_scripts::verify_stat("timings"),"mbstats timings failed"); 	
	}
	public function test_stat_tap() {
		$this->assertTrue(Management_scripts::verify_stat("tap"),"mbstats tap failed"); 	
	}
	
	// tap_exmaple.py
	public function test_fetch_keys(){
		$instance= $this->sharedFixture;
		$instance->set("test_key_fetch","test_value");
		sleep(1);	
		$output = tap_example::fetch_key(TEST_HOST_1);
		$this->assertNotContains("exception", $output, $output);
		$this->assertContains("test_key_fetch", $output, $output);
	}
}



class Python_Scripts_TestCase_Full extends Python_Scripts_TestCase{

	public function keyProvider() {
			return Utility::provideKeys();
	}

}
