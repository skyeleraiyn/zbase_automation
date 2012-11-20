<?php

class Functional_test{
        private static $list_tcpdump_pids=array() ;
	
	public function initial_setup(){
		global $test_machine_list;
		general_function::initial_setup($test_machine_list);
		
		if(defined('RUN_WITH_VALGRIND') && RUN_WITH_VALGRIND){
			if(!general_rpm_function::install_valgrind("localhost")){
				log_function::exit_log_message("Installation of valgrind failed");
			}
				// To limit valgrind output only for memcache.ini and igbinary.ini
			general_function::execute_command("sudo mkdir -p /etc/org_php.d/ ; sudo cp -r /etc/php.d/* /etc/org_php.d/ ; sudo rm -rf /etc/php.d/* ; sudo cp /etc/org_php.d/memcache.ini /etc/org_php.d/igbinary.ini /etc/php.d");
		}		
	}

	public function run_functional_test(){
		global $test_suite_array, $result_file, $test_machine_list;

		// If a particular suite is having word "independent", then that suite will added $list_indepentent_test array list 
		// and executed in serial fashion in the end
		$list_indepentent_test = array();
		$list_parallel_test = $test_suite_array;
		$execution_machine_list = array();
		
		$pid_arr = array();
	
		// check for independent suites and remove them from list_parallel_test
		foreach($list_parallel_test as $test_suite){
			if(stristr($test_suite, "independent")){
				$list_indepentent_test[] = $test_suite;
				$key_to_delete = array_keys($list_parallel_test, $test_suite);
				unset($list_parallel_test[$key_to_delete[0]]);
			} 
		}
		
		$start_time = time();
		
		if(count($list_parallel_test)){
			while(count($list_parallel_test)){
				
				// Find available machine
				foreach($test_machine_list as $test_machine){
					if(!array_key_exists($test_machine, $execution_machine_list)){
						break;
					}
				}
				
				$list_parallel_test = array_reverse($list_parallel_test);
				$test_suite = array_pop($list_parallel_test);
				$list_parallel_test = array_reverse($list_parallel_test);
				
				$pid = pcntl_fork();
				if ($pid == 0){	
					$suite_name = str_replace(".php", "", basename(trim($test_suite)));
					$temp_result_file = str_replace(".log", "_".$suite_name.".log", $result_file);
					self::run_phpunit_test(TEST_FOLDER_PATH.$test_suite, $temp_result_file, $test_machine);
					exit;
				}else {
					$execution_machine_list[$test_machine] = $pid;
				}	
					// wait if all the machines are used
				while(count($execution_machine_list) > count($test_machine_list) - 1){
					$exit_pid = pcntl_waitpid(-1, $status, WNOHANG);
					foreach($execution_machine_list as $machine_name => $pid){
						if($exit_pid == $pid) unset($execution_machine_list[$machine_name]);	
					}
					usleep(100);		
				}					
			}
				// wait until all the suites are executed
			while(count($execution_machine_list)){
				$exit_pid = pcntl_waitpid(-1, $status, WNOHANG);
				foreach($execution_machine_list as $machine_name => $pid){
					if($exit_pid == $pid) unset($execution_machine_list[$machine_name]);	
				}
				usleep(100);		
			}
		}
		
		if(count($list_indepentent_test) > 0 ){
			vbucketmigrator_function::copy_vbucketmigrator_files(array($test_machine_list[0]));
			membase_function::copy_slave_memcached_files(array($test_machine_list[1]));
			foreach($list_indepentent_test as $test_suite){
				$suite_name = str_replace("__independent.php", "", basename(trim($test_suite)));
				$temp_result_file = str_replace(".log", "_".$suite_name.".log", $result_file);
				self::run_phpunit_test(TEST_FOLDER_PATH.$test_suite, $temp_result_file, $test_machine_list);
			}
		}
		
		$total_time = time() - $start_time;
		log_function::result_log("Execution time: ".gmdate("H:i:s", $total_time));
		
			// Clean up RightScale cookies
		general_function::execute_command("sudo rm -rf /tmp/rscookie*");	
		
		if(defined('RUN_WITH_VALGRIND') And RUN_WITH_VALGRIND){
			general_function::execute_command("sudo cp /etc/org_php.d/* /etc/php.d/");
		}		
	}	
	
	public function run_phpunit_test($suite_path, $output_file_path, $test_machine){
				
		if(!self::pre_phpunit_test($suite_path, $test_machine, $output_file_path)){
			return False;
		}
		if(is_array($test_machine)){
			$temp_test_machine = implode(":", $test_machine);
		} else {
			$temp_test_machine = $test_machine;
		}
		$valgrind_file_path = dirname($output_file_path)."/valgrind/".basename($output_file_path);
		
		log_function::result_log("Executing $suite_path in $temp_test_machine");
		
		if(RUN_WITH_VALGRIND){
			general_function::execute_command("USE_ZEND_ALLOC=0 valgrind  --leak-check=yes php phpunit.php membase.php $suite_path $temp_test_machine >".$output_file_path." 2>".$valgrind_file_path, NULL);
		} else {
			general_function::execute_command("php phpunit.php membase.php $suite_path $temp_test_machine >".$output_file_path, NULL);		
		}
		log_function::result_log("$suite_path completed");
		
		self::post_phpunit_test($suite_path, $test_machine);
		
	}
	private function pre_phpunit_test($test_suite, $test_machine, $output_file_path){
		global $setup_storage_server;
		
		if(RUN_WITH_TCPDUMP){
			if(is_array($test_machine)){
				$host = $test_machine[0];
			}else {
				$host = $test_machine;
			}
			$pid2 = pcntl_fork();
			if($pid2 != 0){
				self::$list_tcpdump_pids[basename($test_suite)] = $pid2;
			} else {
				$tcpdump_out_path = dirname($output_file_path)."/tcpdump/".basename($test_suite).".trc";
				general_function::execute_command("sudo /usr/sbin/tcpdump -i any -w $tcpdump_out_path -s1500 host ".$test_machine);
				exit(0);
			}
		}


		// For replication suites start vbucketmigrator
		if(stristr($test_suite, "replication")){
			if(count($test_machine) > 2){
				vbucketmigrator_function::copy_vbucketmigrator_files(array($test_machine[1]));
				membase_function::copy_slave_memcached_files(array($test_machine[2]));
				vbucketmigrator_function::verify_vbucketmigrator_is_running($test_machine[0], $test_machine[1]);
				vbucketmigrator_function::verify_vbucketmigrator_is_running($test_machine[1], $test_machine[2]);
			} else {
				shell_exec("Need 3 machines to execute replication test >>".$output_file_path);
				log_function::debug_log("Need 3 machines to execute replication test");
				return False;
			}
		}
			// For bykey ensure vbucketmigrator is not running
		if(stristr($test_suite, "bykey")){
			if(count($test_machine) > 2){
				vbucketmigrator_function::kill_vbucketmigrator($test_machine[0]);
				vbucketmigrator_function::kill_vbucketmigrator($test_machine[1]);
			} else {
				shell_exec("Need 3 machines to execute bykey test >>".$output_file_path);
				log_function::debug_log("Need 3 machines to execute bykey test");
				return False;
			}
		}		
		if(stristr($test_suite, "mcmux") or stristr($test_suite, "TestKeyValueLimit_mcmux")){
			if(PROXY_RUNNING){
				return PROXY_RUNNING;
			} else {	
				shell_exec("mcmux is not running. Skipping mcmux test suite >>".$output_file_path);
				log_function::debug_log("mcmux is not running. Skipping mcmux test suite");
			}	
		}
		
		if(strstr($test_suite, "Logger_invalid_rule")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_".$log_conf_file_name." ".LOGCONF_PATH);
		}		
		if(	strstr($test_suite, "Logger_basic") or 
			strstr($test_suite, "Logger_Multi_Ops") or 
			strstr($test_suite, "Logger_non_existant_keys") or 
			strstr($test_suite, "Logger_non_existant_server")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_".$log_conf_file_name." ".LOGCONF_PATH);
		}
		if(strstr($test_suite, "Logger_out_of_memory_server")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_".$log_conf_file_name." ".LOGCONF_PATH);
			flushctl_commands::Set_max_size($test_machine, 100663296); // set memory limit to 96MB before running out of memory suite
		}		
		if(strstr($test_suite, "Logger_sig_stop_server")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_".$log_conf_file_name." ".LOGCONF_PATH);
			general_function::execute_command("sudo killall -SIGSTOP memcached", $test_machine);
		}		

		// Testsuites of IBR
		if(	stristr($test_suite, "Backup_Daemon") or 
			stristr($test_suite, "Backup_Tests") or 
			stristr($test_suite, "Core_Merge") or 
			stristr($test_suite, "Daily_Merge") or 
			stristr($test_suite, "Restore") or
			stristr($test_suite, "Data_Integrity_With_IBR") or
			stristr($test_suite, "Master_Merge")){
					
					// Set setup_storage_server to skip configuration of Storage Server for each suite
				if($setup_storage_server){
					return True;
				} else {
					// check if storage_server and slave machine are defined
					if(STORAGE_SERVER == "" or count($test_machine) < 1){
						log_function::debug_log("Need STORAGE_SERVER constant to be defined and 2 machines to run $test_suite suite");
						return False;
					}
					
						// check if backup-tools rpm is installed on storage_server and slave machine
						// If not installed, latest rpm from S3 will be installed
					storage_server::install_backup_tools_rpm($test_machine[1]);
					storage_server::install_backup_tools_rpm(STORAGE_SERVER);				
					storage_server::configure_storage_server($test_machine[1], STORAGE_SERVER);
					remote_function::remote_execution($test_machine[1], "sudo cp ".MEMCACHED_SYSCONFIG." ".MEMCACHED_SYSCONFIG.".org");
					remote_function::remote_execution($test_machine[1], "sudo cp ".MEMBASE_BACKUP_CONSTANTS_FILE." ".MEMBASE_BACKUP_CONSTANTS_FILE.".org");
					remote_function::remote_execution($test_machine[1], "sudo cp ".TEST_SPLITLIB_FILE_PATH." ".TEST_SPLITLIB_FILE_PATH.".org");
					remote_function::remote_execution($test_machine[1], "sudo cp ".DEFAULT_INI_FILE." ".DEFAULT_INI_FILE.".org");
					$setup_storage_server = True;					
				}
		}
		
		// Multi_KVStore testsuite
		if(	stristr($test_suite, "Multi_KVStore")){
			remote_function::remote_execution($test_machine[1], "sudo cp ".MEMCACHED_SYSCONFIG." ".MEMCACHED_SYSCONFIG.".org");
			remote_function::remote_execution($test_machine[1], "sudo cp ".MEMCACHED_MULTIKV_CONFIG." ".MEMCACHED_MULTIKV_CONFIG.".org");
			remote_function::remote_execution($test_machine[2], "sudo cp ".MEMCACHED_MULTIKV_CONFIG." ".MEMCACHED_MULTIKV_CONFIG.".org");
		}
		
		return True;	
	}
	
	private function post_phpunit_test($test_suite, $test_machine){
		if(RUN_WITH_TCPDUMP){
			if(count($test_machine) > 2)
				$cmd = "sudo ps -ef | grep tcpdump | grep " . $test_machine[0] . " | awk {'print $2'} | xargs sudo kill -SIGINT ";
			else
				$cmd = "sudo ps -ef | grep tcpdump | grep " . $test_machine . " | awk {'print $2'} | xargs sudo kill -SIGINT ";
			general_function::execute_command($cmd);
		}
	
		if stristr($test_suite, "Restore"){
			remote_function::remote_execution($test_machine[1], "sudo cp ".MEMCACHED_SYSCONFIG.".org ".MEMCACHED_SYSCONFIG);
		}
		
		if(stristr($test_suite, "logger")){
			general_function::execute_command("cat /dev/null | sudo tee ".LOGCONF_PATH);
		}
		if(strstr($test_suite, "Logger_out_of_memory_server")){
			flushctl_commands::Set_max_size($test_machine, 64424509440); // reset back to 60GB when test is complete
		}
		if(strstr($test_suite, "Logger_sig_stop_server")){
			general_function::execute_command("sudo killall -SIGCONT memcached", $test_machine);
		}		
					// For replication disconnect vbucketmigrator
		if(stristr($test_suite, "replication")){
			vbucketmigrator_function::kill_vbucketmigrator($test_machine[0]);
			vbucketmigrator_function::kill_vbucketmigrator($test_machine[1]);
		}	
	}
	
	public function install_base_files_and_reset(){				
		global $test_machine_list, $proxyserver_installed;
		
		membase_function::copy_memcached_files($test_machine_list);		
		proxy_server_function::kill_proxyserver_process("localhost");
		membase_function::reset_membase_servers($test_machine_list);
		if($proxyserver_installed){
			proxy_server_function::start_proxyserver("localhost", $proxyserver_installed);
		}
	}
	
	// This function skips installation if the rpm is already installed from the previous run. 
	// However it verifies that machines are updated to the latest rpm combination
	public function install_rpm_combination($rpm_array){
		global $list_of_installed_rpms, $test_machine_list, $proxyserver_installed;
		$proxyserver_installed = False;
				
		if(!(isset($list_of_installed_rpms))){	// list_of_installed_rpms will maintian all the RPM's installed during a given loop of testing
			$list_of_installed_rpms[] = array();	// This avoids duplicate installations. This variable will get reset once the loop is complete.
		}
		foreach($rpm_array as $rpm_name){			
			switch (true) {
			  case strstr($rpm_name, "php-pecl"):
				self::verify_and_install_rpm("localhost", $rpm_name, PHP_PECL_PACKAGE_NAME);
				break;
			  case strstr($rpm_name, "mcmux"):
				self::verify_and_install_rpm("localhost", $rpm_name, MCMUX_PACKAGE_NAME);
				$proxyserver_installed = "mcmux";
				break;
			  case strstr($rpm_name, "moxi"):
				self::verify_and_install_rpm("localhost", $rpm_name, MOXI_PACKAGE_NAME);
				$proxyserver_installed = "moxi";
				break;
			  case strstr($rpm_name, "backup"):
				if(STORAGE_SERVER <> "" && count($test_machine_list) > 1){
					self::verify_and_install_backup_tools_rpm($test_machine_list[1], $rpm_name);
					self::verify_and_install_backup_tools_rpm(STORAGE_SERVER, $rpm_name);
					break;
				} else{
					log_function::exit_log_message("Need STORAGE_SERVER constant to be defined and 2 machines to install backup tools rpm");	
				}				
			  case strstr($rpm_name, "membase"):
				foreach($test_machine_list as $test_machine){
					self::verify_and_install_rpm($test_machine, $rpm_name, MEMBASE_PACKAGE_NAME);
				}
				break;
			default:
				log_function::exit_log_message("rpm_function not defined for $rpm_name");	
			}
		}
	}
	
	private function verify_and_install_backup_tools_rpm($remote_machine_name, $rpm_name){
	
		rpm_function::install_jemalloc_rpm($remote_machine_name);
		self::verify_and_install_rpm($remote_machine_name, $rpm_name, BACKUP_TOOLS_PACKAGE_NAME);
		file_function::modify_value_ini_file(DEFAULT_INI_FILE, array("game_id", "cloud", "interval"), array(GAME_ID, MEMBASE_CLOUD, 30), $remote_machine_name);
						
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
	
	public function restart_membase_after_persistance(){
		if(Utility::Check_keys_are_persisted())
		 	return membase_function::restart_membase_servers(TEST_HOST_1);
		else
			return False;
	}
		
}

?>