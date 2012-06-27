<?php

class Performance_function{

	public function run_performance_test($data_sample){
		mcmux_function::kill_mcmux_process("localhost");
		
			// Execute performance test for all the data sample size
		foreach ($data_sample as $data_size => $total_no_of_keys) {
			
			general_function::setup_data_folder($data_size);
			echo "\n\n";
			log_function::result_log("Running test for $data_size bytes with $total_no_of_keys keys");
			while(1){			// Resets if ep_item_commit_failed starts increasing while running set_get.php
				file_function::clear_log_files(array(MASTER_SERVER, SLAVE_SERVER_1));
				membase_function::reset_membase_servers(array(MASTER_SERVER, SLAVE_SERVER_1));
				flushctl_commands::Run_flushctl_command(MASTER_SERVER, " set eviction_headroom 6442450944");
				flushctl_commands::Run_flushctl_command(SLAVE_SERVER_1, " set eviction_headroom 6442450944");
				vbucketmigrator_function::attach_vbucketmigrator(MASTER_SERVER, SLAVE_SERVER_1);
				if(self::set_get($total_no_of_keys, $data_size)) break;
				sleep(1);
			}
			self::capture_stats_log_files();
			
			// Slave fresh replication time
			self::reset_slave_and_reattach_it_master_server($total_no_of_keys);
			self::capture_stats_log_files_fresh_replication();
			
			file_function::clear_log_files(array(MASTER_SERVER, SLAVE_SERVER_1));
			log_function::result_log("Rebooting Master server to capture warm-up time");
			self::restart_master_membase_to_capture_warmup_time();
			self::capture_stats_log_files_warmup();
		}
	}

	public function install_base_files(){				
		membase_function::copy_memcached_files(array(MASTER_SERVER));	
		vbucketmigrator_function::copy_vbucketmigrator_files(array(MASTER_SERVER));
		membase_function::copy_slave_memcached_files(array(SLAVE_SERVER_1));	
	}
	
	// This function skips installation if the rpm is already installed from the previous run. 
	// However it verifies that machines are updated to the latest rpm combination
	public function install_rpm_combination($rpm_array){
		// Global array to maintain installed rpms
		global $list_of_installed_rpms;
		if(!(isset($list_of_installed_rpms))){
			$list_of_installed_rpms[] = array();
		}
		foreach($rpm_array as $rpm_name){			
			switch (true) {
			  case strstr($rpm_name, "php-pecl"):
				self::verify_and_install_rpm("localhost", $rpm_name, PHP_PECL_PACKAGE_NAME);
				break;
			  case strstr($rpm_name, "mcmux"):
				self::verify_and_install_rpm("localhost", $rpm_name, MCMUX_PACKAGE_NAME);
				break;
			  case strstr($rpm_name, "membase"):
				self::verify_and_install_rpm(MASTER_SERVER, $rpm_name, MEMBASE_PACKAGE_NAME);
				self::verify_and_install_rpm(SLAVE_SERVER_1, $rpm_name, MEMBASE_PACKAGE_NAME);
				break;
			default:
				log_function::exit_log_message("rpm_function not defined for $rpm_name");	
			}
		}
	}
	
	private function verify_and_install_rpm($remote_machine_name, $rpm_name, $packagename){
		global $list_of_installed_rpms;
		
		$output = rpm_function::get_installed_component_version($rpm_name, $remote_machine_name);
		if(!(strstr($rpm_name, $output)) or !(in_array($remote_machine_name.$output, $list_of_installed_rpms))){
			rpm_function::clean_install_rpm($remote_machine_name, BUILD_FOLDER_PATH.$rpm_name, $packagename);
			$list_of_installed_rpms[] = rpm_function::get_installed_component_version($rpm_name, $remote_machine_name);
		} else {
			log_function::debug_log("Build $output is already installed, skipping installation.");
		}	
	}	
	
	public function restart_master_membase_to_capture_warmup_time(){
		
		membase_function::kill_membase_server(MASTER_SERVER);
		membase_function::start_memcached_service(MASTER_SERVER);
		for($iTime = 0 ; $iTime < 1080 ; $iTime++){
			$output = stats_functions::get_warmup_time_ascii(MASTER_SERVER);
			if (stristr($output, "ep_warmup_time")){
				$output = explode(" ", $output);
				log_function::result_log("Warmup time: ".round($output[2]/1000000),2);
				log_function::result_log("Membase memory of ".MASTER_SERVER." after warmup: ".membase_function::get_membase_memory(MASTER_SERVER)."GB");
				return 1;
			}	
			else
				sleep(10);
		}	
		log_function::debug_log("Unable to connect to membase server on ".MASTER_SERVER." after restart");		
	}


	public function reset_slave_and_reattach_it_master_server($total_no_of_keys) {
		
		$remote_machine_array_list = array(SLAVE_SERVER_1);
		while(1){
			membase_function::reset_membase_servers($remote_machine_array_list);
			flushctl_commands::Run_flushctl_command(SLAVE_SERVER_1, " set eviction_headroom 6442450944");
			
			for($iTime = 0 ; $iTime < 60 ; $iTime++){
			$output = stats_functions::get_warmup_time_ascii(SLAVE_SERVER_1);
			if (stristr($output, "ep_warmup_time"))
				break;
			else 
				sleep(1);
			}
			if(MEMBASE_VERSION <> 1.6){
				tap_commands::deregister_replication_tap_name(MASTER_SERVER);
				tap_commands::register_replication_tap_name(MASTER_SERVER, " -b -l 0 ");
			}
			vbucketmigrator_function::start_vbucketmigrator_service(MASTER_SERVER);
			if(self::get_replication_time($total_no_of_keys)) break;	// Added a check if slave gets persistance issue
		}
	}

	public function capture_stats_log_files(){
		global $data_folder; 
		
		membase_function::copy_membase_log_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER);
		membase_function::copy_membase_log_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1);
		vbucketmigrator_function::copy_vbucketmigrator_log_file(MASTER_SERVER, $data_folder."/".SLAVE_SERVER_1);
		
		stats_commands::capture_timings_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER);
		stats_commands::capture_all_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER);
		stats_commands::capture_checkpoint_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER);
		stats_commands::capture_eviction_stat_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER);
		stats_commands::capture_timings_stats_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1);
		stats_commands::capture_all_stats_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1);
		stats_commands::capture_checkpoint_stats_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1);
		stats_commands::capture_eviction_stat_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1);
		self::collect_replication_graphs();
	}

	public function capture_stats_log_files_fresh_replication(){
		global $data_folder; 
		
		membase_function::copy_membase_log_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_freshreplication");
		membase_function::copy_membase_log_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1."_freshreplication");
		vbucketmigrator_function::copy_vbucketmigrator_log_file(MASTER_SERVER, $data_folder."/".SLAVE_SERVER_1."_freshreplication");
		
		stats_commands::capture_timings_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_freshreplication");
		stats_commands::capture_all_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_freshreplication");
		stats_commands::capture_checkpoint_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_freshreplication");
		stats_commands::capture_timings_stats_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1."_freshreplication");
		stats_commands::capture_all_stats_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1."_freshreplication");
		stats_commands::capture_checkpoint_stats_to_file(SLAVE_SERVER_1, $data_folder."/".SLAVE_SERVER_1."_freshreplication");
		self::collect_fresh_replication_graphs();
	}

	public function capture_stats_log_files_warmup(){

		global $data_folder; 
		
		membase_function::copy_membase_log_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_warmup");
		stats_commands::capture_timings_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_warmup");
		stats_commands::capture_all_stats_to_file(MASTER_SERVER, $data_folder."/".MASTER_SERVER."_warmup");
		self::collect_warmup_graphs();
	}

	public function collect_replication_graphs(){
		global $data_folder; 
		
		$master_graphs_replication = array(	"cpu_overview" => "cpu-0",
								"disk_octets" => "disk-xvda",
								"disk_ops" => "disk-xvda",
								"if_octets-eth0" => "interface",	
								"if_packets-eth0" => "interface",
								"load" => "load",
								"gauge-curr_items" => "membase",
								"gauge-ep_flush_duration" => "membase",
								"gauge-ep_flusher_todo" => "membase",
								"gauge-ep_num_eject_failures" => "membase",
								"gauge-ep_num_non_resident" => "membase",
								"gauge-ep_oom_errors" => "membase",
								"gauge-ep_queue_size" => "membase",
								"gauge-ep_total_cache_size" => "membase",
								"gauge-mem_used" => "membase",
								"counter-ep_bg_fetched"  => "membase",
								"counter-ep_tap_bg_fetched" => "membase",							
								"memory-used" => "memory",
								"swap-used" => "swap"
								);							
		$slave_graphs_replication = array(	"cpu_overview" => "cpu-0",
								"disk_octets" => "disk-xvda",
								"disk_ops" => "disk-xvda",
								"if_octets-eth0" => "interface",	
								"if_packets-eth0" => "interface",
								"load" => "load",
								"gauge-curr_items" => "membase",
								"gauge-ep_flush_duration" => "membase",
								"gauge-ep_flusher_todo" => "membase",
								"gauge-ep_num_eject_failures" => "membase",
								"gauge-ep_num_non_resident" => "membase",
								"gauge-ep_oom_errors" => "membase",
								"gauge-ep_queue_size" => "membase",
								"gauge-ep_total_cache_size" => "membase",
								"gauge-mem_used" => "membase",
								"counter-ep_bg_fetched"  => "membase",
								"counter-ep_tap_bg_fetched" => "membase",							
								"memory-used" => "memory",
								"swap-used" => "swap"
								);
		graph_functions::get_RightScale_graph(MASTER_SERVER, $master_graphs_replication, $data_folder."/".MASTER_SERVER."/replication");
		graph_functions::get_RightScale_graph(SLAVE_SERVER_1, $slave_graphs_replication, $data_folder."/".SLAVE_SERVER_1."/replication");
	}

	public function collect_fresh_replication_graphs(){
		global $data_folder; 
		
		$master_graphs_replication = array(	"cpu_overview" => "cpu-0",
								"disk_octets" => "disk-xvda",
								"disk_ops" => "disk-xvda",
								"if_octets-eth0" => "interface",	
								"if_packets-eth0" => "interface",
								"load" => "load",
								"gauge-mem_used" => "membase",
								"counter-ep_tap_bg_fetched" => "membase",							
								"memory-used" => "memory",
								);							
		$slave_graphs_replication = array(	"cpu_overview" => "cpu-0",
								"disk_octets" => "disk-xvda",
								"disk_ops" => "disk-xvda",
								"if_octets-eth0" => "interface",	
								"if_packets-eth0" => "interface",
								"load" => "load",
								"gauge-mem_used" => "membase",
								"counter-ep_tap_bg_fetched" => "membase",							
								"memory-used" => "memory"
								);
		graph_functions::get_RightScale_graph(MASTER_SERVER, $master_graphs_replication, $data_folder."/".MASTER_SERVER."/fresh_replication");
		graph_functions::get_RightScale_graph(SLAVE_SERVER_1, $slave_graphs_replication, $data_folder."/".SLAVE_SERVER_1."/fresh_replication");
	}


	public function collect_warmup_graphs(){
		global $data_folder; 
		
		$master_warmup_graphs = array(	"cpu_overview" => "cpu-0",
								"disk_octets" => "disk-xvda",
								"disk_ops" => "disk-xvda",
								"load" => "load",
								"memory-used" => "memory"
								);
														
		graph_functions::get_RightScale_graph(MASTER_SERVER, $master_warmup_graphs, $data_folder."/".MASTER_SERVER."/warmup");
				
	}

	 
	public function set_get($total_no_of_keys, $data_size){

		global $result_file; 
		
		$no_of_parallel_threads = 4;
		$no_of_keys = $total_no_of_keys / $no_of_parallel_threads;
		
		$mc_master = new Memcache();
		$mc_master->addserver(MASTER_SERVER, MEMBASE_PORT_NO);
		$mc_slave_1 = new Memcache();
		$mc_slave_1->addserver(SLAVE_SERVER_1, MEMBASE_PORT_NO);
		
		$initial_sleep_for_get = 120;
		
		$main_pid_count = 0;
		$main_pid_arr = array();
		$main_pid = pcntl_fork();
		if ($main_pid == 0){

			$master_server_curr_items_time;
			$slave_server_1_curr_items_time;
			$master_server_get_time;
			if(QUICK_RUN) $master_server_get_time = False;
			$master_server_ep_total_persisted_time;
			$slave_server_1_ep_total_persisted_time;
			$time_log = "";

			while(1){
				$master_output = $mc_master->getStats();
				$slave_1_output = $mc_slave_1->getStats();

				if(is_numeric($master_output["curr_items"])){
					if(!(isset($master_server_curr_items_time)) && $master_output["curr_items"] > 0){
						$master_server_curr_items_time = time();
					}
					if(isset($master_server_curr_items_time) && $master_server_curr_items_time){
						if($master_output["curr_items"] >= $total_no_of_keys){
							$master_server_curr_items_time = time() - $master_server_curr_items_time;
							$time_log = $time_log."Time taken for set in master_server ".MASTER_SERVER.":".$master_server_curr_items_time."\r\n";
							$time_log = $time_log."Set rate on master_server: ".floor($total_no_of_keys / $master_server_curr_items_time)."\r\n";
							$master_server_curr_items_time = False;
						}	
					}
				}
				
				if(is_numeric($master_output["cmd_get"])){
					if(!(isset($master_server_get_time)) && $master_output["cmd_get"] > 0){
						$master_server_get_time = time();
					}
					if(isset($master_server_get_time) && $master_server_get_time){
						if($master_output["cmd_get"] >= $total_no_of_keys * 3){
							$master_server_get_time = time() - $master_server_get_time;
							$time_log = $time_log."Time taken for get in master_server ".MASTER_SERVER.":".$master_server_get_time."\r\n";
							$time_log = $time_log."Get rate on master_server: ".floor(($total_no_of_keys * 3) / $master_server_get_time)."\r\n";
							$master_server_get_time = False;
						}	
					}
				}
				

				if(is_numeric($master_output["ep_total_persisted"])){
					if(!(isset($master_server_ep_total_persisted_time)) && $master_output["ep_total_persisted"] > 0){
						$master_server_ep_total_persisted_time = time();
					}
					if(isset($master_server_ep_total_persisted_time) && $master_server_ep_total_persisted_time){
						if($master_output["ep_total_persisted"] >= $total_no_of_keys){
							$master_server_ep_total_persisted_time = time() - $master_server_ep_total_persisted_time;
							$time_log = $time_log."Time taken for persistance in master_server ".MASTER_SERVER.":".$master_server_ep_total_persisted_time."\r\n";
							$master_server_ep_total_persisted_time = False;
						}	
					}
				}
				
				if(is_numeric($slave_1_output["curr_items"])){
					if(!(isset($slave_server_1_curr_items_time)) && $slave_1_output["curr_items"] > 0){
						$slave_server_1_curr_items_time = time();
					}
					if(isset($slave_server_1_curr_items_time) && $slave_server_1_curr_items_time){
						if($slave_1_output["curr_items"] >= $total_no_of_keys){
							$slave_server_1_curr_items_time = time() - $slave_server_1_curr_items_time;
							$time_log = $time_log."Time taken for replication in slave_server ".SLAVE_SERVER_1.":".$slave_server_1_curr_items_time."\r\n";
							$slave_server_1_curr_items_time = False;
						}	
					}
				}

				if(is_numeric($slave_1_output["ep_total_persisted"])){
					if(!(isset($slave_server_1_ep_total_persisted_time)) && $slave_1_output["ep_total_persisted"] > 0){
						$slave_server_1_ep_total_persisted_time = time();
					}
					if(isset($slave_server_1_ep_total_persisted_time) && $slave_server_1_ep_total_persisted_time){
						if($slave_1_output["ep_total_persisted"] >= $total_no_of_keys){
							$slave_server_1_ep_total_persisted_time = time() - $slave_server_1_ep_total_persisted_time;
							$time_log = $time_log."Time taken for persistance in slave_server ".SLAVE_SERVER_1.":".$slave_server_1_ep_total_persisted_time."\r\n";
							$slave_server_1_ep_total_persisted_time = False;
						}	
					}
				}
				
				if( isset($master_server_curr_items_time) && isset($slave_server_1_curr_items_time) && isset($master_server_get_time) && isset($master_server_ep_total_persisted_time) && isset($slave_server_1_ep_total_persisted_time)){
							
					if($master_server_curr_items_time || $slave_server_1_curr_items_time || $master_server_get_time || $master_server_ep_total_persisted_time || $slave_server_1_ep_total_persisted_time)
						sleep(1);
					else{
						log_function::result_log($time_log);
						exit;
					}
				} else {
					sleep(1);
				}	
			}		
		}else {
				$main_pid_arr[$main_pid_count] = $main_pid;
				$main_pid_count = $main_pid_count + 1;
		}
		
		
		// Generate data
		$sdata = Data_generation::generate_data($data_size);

		$mc_slave_1->set("get_miss_count", 0);
		$mc_slave_1->set("get_data_mismatch_count", 0);
		$mc_slave_1->set("set_miss_count", 0);
		
		// start parallel threads
		for ($ithread = 0 ; $ithread < $no_of_parallel_threads ; $ithread++){
			$main_pid = pcntl_fork();
			if ($main_pid == 0){	
				$child_pid_arr = array();
				$child_pid_count = 0;
				if(!(QUICK_RUN)){
					// Start a different thread for get request
					$get_no_of_keys_array = array($no_of_keys, round($no_of_keys / 2), round($no_of_keys / 3));
					foreach ($get_no_of_keys_array as $get_no_of_keys){
						$child_pid = pcntl_fork();
						if ($child_pid == 0){
							sleep($initial_sleep_for_get);	
							$get_miss_count = 0;
							$get_data_mismatch_count = 0;
							for ($iter= 0, $get_iter=0 ; $iter < $no_of_keys ; $iter++,$get_iter++){		
								if ($get_no_of_keys == $no_of_keys)
									if(!($iter % 1000)) usleep(5000);
								else
									if ($get_iter == $get_no_of_keys) $get_iter = 0;

								while(1){
									if($mc_master->get2("testkey".$ithread."_".$get_iter, $getresult)){
										if ($getresult != $sdata){
											$get_data_mismatch_count = $get_data_mismatch_count + 1;
										}
										break;
									} else{
										usleep(10000);
										$get_miss_count = $get_miss_count + 1;
									}
								}
								
							}
							if ($get_miss_count > 0 || $get_data_mismatch_count > 0){
								$mc_slave_1->increment("get_miss_count", $get_miss_count);
								$mc_slave_1->increment("get_data_mismatch_count", $get_data_mismatch_count);
							}
							exit (0);
						}
						else{
							$child_pid_arr[$child_pid_count] = $child_pid;
							$child_pid_count = $child_pid_count + 1;	
						}
					}
				}	
				
		
				$set_miss_count = 0;
				for ($iter= 0 ; $iter < $no_of_keys ; $iter++){
					while(1){
						$setresult = $mc_master->set("testkey".$ithread."_".$iter, $sdata, 0); //MEMCACHE_COMPRESSED_LZO
						if($setresult)
						{
							break;
						} else {
							usleep(1000);
							$set_miss_count = $set_miss_count + 1;
						}	
					}
				}

				if($set_miss_count > 0){
					$mc_slave_1->increment("set_miss_count", $set_miss_count);
				}

				while(count($child_pid_arr) > 0){
					$myId = pcntl_waitpid(-1, $status, WNOHANG);
					foreach($child_pid_arr as $key => $pid){
						if($myId == $pid) unset($child_pid_arr[$key]);
					}
					sleep(1);
				}
				exit (0);
			} else {
				$main_pid_arr[$main_pid_count] = $main_pid;
				$main_pid_count = $main_pid_count + 1;
			}
				
		}

		/* 	Check if ep_item_commit_failed increases due to storage errors at the start of membase server
		Termiate all process and return False to restart set_get	*/
		
		$master_stats = $mc_master->getStats();		// Assuming ep_min_data_age is same for master and slave templates
		$ep_min_data_age = intval($master_stats["ep_min_data_age"]);
		sleep($ep_min_data_age + 2);			// Wait till ep_min_data_age is crossed to begin persistance
		
		for($iTimecount = 0 ; $iTimecount < 120 ; $iTimecount++){

			$master_stats = $mc_master->getStats();
			$slave_1_stats = $mc_slave_1->getStats();
			
			if(intval($master_stats["ep_item_commit_failed"]) > 0 or intval($slave_1_stats["ep_item_commit_failed"]) > 0){
				foreach($main_pid_arr as $key => $pid){
					shell_exec("sudo kill -9 ".$pid);
				}
				log_function::debug_log("Persistance issue found master:".$master_stats["ep_item_commit_failed"]." slave:".$slave_1_stats["ep_item_commit_failed"].". Reseting the test");
				return False;		
			}	
			sleep(1);
		}

		
		while(count($main_pid_arr) > 0){
		
			$myId = pcntl_waitpid(-1, $status, WNOHANG);
			foreach($main_pid_arr as $key => $pid)
			{
				if($myId == $pid) unset($main_pid_arr[$key]);	
			}
			usleep(100);
		}
		
		$set_miss_count = $mc_slave_1->get("set_miss_count");
		$get_miss_count = $mc_slave_1->get("get_miss_count");
		$get_data_mismatch_count = $mc_slave_1->get("get_data_mismatch_count");
		$stats = $mc_slave_1->getStats();
		
		log_function::result_log("Membase memory of master_server ".MASTER_SERVER.": ".membase_function::get_membase_memory(MASTER_SERVER)."GB");
		log_function::result_log("Membase DB size on disk for master_server ".MASTER_SERVER.": ".membase_function::get_membase_db_size(MASTER_SERVER));
		log_function::result_log("Membase memory of slave_server ".SLAVE_SERVER_1.": ".membase_function::get_membase_memory(SLAVE_SERVER_1)."GB");
		log_function::result_log("Membase DB size on disk for slave_server ".SLAVE_SERVER_1.": ".membase_function::get_membase_db_size(SLAVE_SERVER_1));
		log_function::result_log("get misses as reported by Membase: ".$stats["get_misses"]);
		log_function::result_log("Client set miss count: $set_miss_count");
		log_function::result_log("Client get miss count: $get_miss_count");
		log_function::result_log("Client data verification fail count: $get_data_mismatch_count");
		
		return True;
		
	}
	 

	public function get_replication_time($total_no_of_keys){
		
	
		$slave_server_1_curr_items_time;
		$time_log = "";
		$mc_slave_1 = new Memcache();
		$mc_slave_1->addserver(SLAVE_SERVER_1, MEMBASE_PORT_NO);
		
		while(1){
			$slave_1_output = $mc_slave_1->getStats();
			
			if(intval($slave_1_output["ep_item_commit_failed"]) > 0){
				log_function::debug_log("Persistance issue found slave:".$slave_1_output["ep_item_commit_failed"].". Reseting the get_replication_time test.");
				return False;		
			}		
			
			if(is_numeric($slave_1_output["curr_items"])){
				if(!(isset($slave_server_1_curr_items_time)) && $slave_1_output["curr_items"] > 0){
					$slave_server_1_curr_items_time = time();
				}
				if(isset($slave_server_1_curr_items_time) && $slave_server_1_curr_items_time){
					if($slave_1_output["curr_items"] >= $total_no_of_keys){
						$slave_server_1_curr_items_time = time() - $slave_server_1_curr_items_time;
						$time_log = $time_log."Time taken for fresh replication in SLAVE_SERVER_1:".$slave_server_1_curr_items_time."\n";
						$slave_server_1_curr_items_time = False;
					}	
				}
			}
	
			if( isset($slave_server_1_curr_items_time)){	
				if($slave_server_1_curr_items_time)
					sleep(1);
				else{
					log_function::result_log($time_log);
					return True;
				}
			} else {
				sleep(1);
			}	
		}		
	}		
}
?>