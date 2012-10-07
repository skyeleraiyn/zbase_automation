<?php

class Performance_function{

	public function run_churn_test(){
		
		log_function::result_log("Adding keys ...");	
		remote_function::remote_execution(MASTER_SERVER, "/tmp/brutis/brutis -x ".MASTER_SERVER." -t /tmp/brutis/tests/set_62M_1k.xml -v > /tmp/set_62M_1k.log");
		log_function::result_log("Warming up keys ...");	
		remote_function::remote_execution(MASTER_SERVER, "/tmp/brutis/brutis -x ".MASTER_SERVER." -t /tmp/brutis/tests/set_62M_1k_warmup_10M.xml -v > /tmp/set_62M_1k_warmup_10M.log");
		log_function::result_log("Running churn test ...");	
		self::get_graphs_thread();
		remote_function::remote_execution("localhost", "/tmp/brutis/brutis -x ".MASTER_SERVER." -t /tmp/brutis/tests/set_get_10M_1k.xml -v > /tmp/set_get_10M_1k.log");
				
	}

	public function get_graphs_thread(){
	
		$pid = pcntl_fork();
		if ($pid == 0){	
			for($itime=3 ; $itime<72 ; $itime = $itime + 3){
				sleep(10800);
				self::collect_graphs("now/hour".$itime);
			}
			exit;
		}
		$pid = pcntl_fork();
		if ($pid == 0){	
			for($itime=1 ; $itime<3 ; $itime++){
				sleep(86400);
				self::collect_graphs("day/day".$itime);
			}
			exit;
		}		
	}
	
	public function install_base_files_and_reset(){	
		if(!SKIP_BUILD_INSTALLATION_AND_SETUP){
			membase_function::copy_memcached_files(array(MASTER_SERVER));	
			vbucketmigrator_function::copy_vbucketmigrator_files(array(MASTER_SERVER));
			membase_function::copy_slave_memcached_files(array(SLAVE_SERVER_1));
		}
		
		proxy_server_function::kill_proxyserver_process("localhost");
		remote_function::remote_execution(MASTER_SERVER, "sudo killall -9 collector client brutis");
		remote_function::remote_execution("localhost", "sudo killall -9 collector client brutis");
		
		proxy_server_function::kill_mcmux_process("localhost");
		membase_function::clear_membase_log_file(MASTER_SERVER);
		vbucketmigrator_function::clear_vbucketmigrator_log_file(MASTER_SERVER);
		membase_function::clear_membase_log_file(SLAVE_SERVER_1);
		membase_function::reset_membase_servers(array(MASTER_SERVER, SLAVE_SERVER_1));
		vbucketmigrator_function::attach_vbucketmigrator(MASTER_SERVER, SLAVE_SERVER_1);	

		remote_function::remote_file_copy(MASTER_SERVER, "brutis.tar.gz", "/tmp");
		remote_function::remote_execution(MASTER_SERVER, "tar xvf /tmp/brutis.tar.gz -C /tmp");
		shell_exec("tar xvf brutis.tar.gz -C /tmp");
		
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


	public function collect_graphs($path){
		
		$graphs = array(	"cpu_overview" => "cpu-0",
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

		graph_functions::get_RightScale_graph(MASTER_SERVER, $graphs, RESULT_FOLDER."/".$path);
	}



}
?>