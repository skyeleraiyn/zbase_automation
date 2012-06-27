<?php

class Functional_test{
	public function run_functional_test(){
		global $test_suite_array, $result_file, $test_machine_list;

		// Setting EXECUTE_TESTCASES_PARALLELY to True will run all the suites in parallel with different key, value pairs. 
		// However if a particular suite is having word "independent", then that suite will added $list_indepentent_test array list and executed in serial fashion in the end
		$list_indepentent_test = array();
		$list_parallel_test = $test_suite_array;
		$execution_machine_list = array();
		
		$pid_arr = array();
		if(EXECUTE_TESTCASES_PARALLELY){
			// check for independent suites and remove them from list_parallel_test
			foreach($list_parallel_test as $test_suite){
				if(stristr($test_suite, "independent")){
					$list_indepentent_test[] = $test_suite;
					$key_to_delete = array_keys($list_parallel_test, $test_suite);
					unset($list_parallel_test[$key_to_delete[0]]);
				} 
			}
			
			if(count($list_parallel_test)){
				while(count($list_parallel_test)){
					while(count($execution_machine_list) > 2){
						$exit_pid = pcntl_waitpid(-1, $status, WNOHANG);
						foreach($execution_machine_list as $machine_name => $pid){
							if($exit_pid == $pid) unset($execution_machine_list[$machine_name]);	
						}
						usleep(100);		
					}
					
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
				}

				while(count($execution_machine_list)){
					$exit_pid = pcntl_waitpid(-1, $status, WNOHANG);
					foreach($execution_machine_list as $machine_name => $pid){
						if($exit_pid == $pid) unset($execution_machine_list[$machine_name]);	
					}
					usleep(100);		
				}

			
			}
			
			if(count($list_indepentent_test) > 0 ){
				foreach($list_indepentent_test as $test_suite){
					$suite_name = str_replace("__independent.php", "", basename(trim($test_suite)));
					$temp_result_file = str_replace(".log", "_".$suite_name.".log", $result_file);
					self::run_phpunit_test(TEST_FOLDER_PATH.$test_suite, $temp_result_file, $test_machine_list);
				}
			}
		} else {
			foreach($test_suite_array as $test_suite){
				$suite_name = str_replace("__independent", "", basename(trim($test_suite)));
				$suite_name = str_replace(".php", "", basename(trim($test_suite)));
				$temp_result_file = str_replace(".log", "_".$suite_name.".log", $result_file);
				self::run_phpunit_test(TEST_FOLDER_PATH.$test_suite, $temp_result_file);
			}
		}
			// Clean up RightScale cookies
		general_function::execute_command("sudo rm -rf /tmp/rscookie*");	
	}	
	
	public function run_phpunit_test($suite_path, $output_file_path, $test_machine){
				
		if(!self::pre_phpunit_test($suite_path, $test_machine)){
			return False;
		}
		if(is_array($test_machine)) $test_machine = implode(" ", $test_machine);
		$valgrind_file_path = dirname($output_file_path)."/valgrind/".basename($output_file_path);
		
		echo "Executing $suite_path in $test_machine \n";
		
		if(RUN_WITH_VALGRIND){
			general_function::execute_command("USE_ZEND_ALLOC=0 valgrind  --leak-check=yes php phpunit.php membase.php $suite_path $test_machine >".$output_file_path." 2>".$valgrind_file_path, NULL);
		} else {
			general_function::execute_command("php phpunit.php membase.php $suite_path $test_machine >".$output_file_path, NULL);
		}
		echo "$suite_path completed\n";
		
		self::post_phpunit_test($suite_path, $test_machine);
		
	}
	private function pre_phpunit_test($test_suite, $test_machine){
			// For replication suites start vbucketmigrator
		if(stristr($test_suite, "replication")){
			if(count($test_machine) > 2){
			vbucketmigrator_function::copy_vbucketmigrator_files(array($test_machine[0], $test_machine[1]));
			membase_function::copy_slave_memcached_files(array($test_machine[1], $test_machine[2]));
			vbucketmigrator_function::verify_vbucketmigrator_is_running($test_machine[0], $test_machine[1]);
			vbucketmigrator_function::verify_vbucketmigrator_is_running($test_machine[1], $test_machine[2]);
			} else {
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
				log_function::debug_log("Need 3 machines to execute replication test");
				return False;
			}
		}		
		if(stristr($test_suite, "mcmux")){
			return MCMUX_RUNNING;
		}
		if(strstr($test_suite, "Logger_invalid_rule")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_".$log_conf_file_name." ".LOGCONF_PATH);
		}		
		if(strstr($test_suite, "Logger_basic") or strstr($test_suite, "Logger_non_existant_keys") or strstr($test_suite, "Logger_non_existant_server")){
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
		return True;	
	}
	
	private function post_phpunit_test($test_suite, $test_machine){
		if(stristr($test_suite, "logger")){
			general_function::execute_command("cat /dev/null | sudo tee ".LOGCONF_PATH);
		}
		if(strstr($test_suite, "Logger_out_of_memory_server")){
			flushctl_commands::Set_max_size($test_machine, 64424509440); // reset back to 60GB when test is complete
		}
		if(strstr($test_suite, "Logger_sig_stop_server")){
			general_function::execute_command("sudo killall -SIGCONT memcached", $test_machine);
		}		
	}
	
	public function install_base_files_and_reset(){				
		global $test_machine_list;
		
		membase_function::copy_memcached_files($test_machine_list);		
		mcmux_function::kill_mcmux_process("localhost");
		membase_function::reset_membase_servers($test_machine_list);
		if(defined('MCMUX_INSTALLED') and MCMUX_INSTALLED){
			mcmux_function::start_mcmux_service("localhost");
		}
	}
	
	// This function skips installation if the rpm is already installed from the previous run. 
	// However it verifies that machines are updated to the latest rpm combination
	public function install_rpm_combination($rpm_array){
		// Global array to maintain installed rpms
		global $list_of_installed_rpms, $test_machine_list;
		
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
				foreach($test_machine_list as $test_machine){
					self::verify_and_install_rpm($test_machine, $rpm_name, MEMBASE_PACKAGE_NAME);
				}
				break;
			  case strstr($rpm_name, "backup"):
				if(count($test_machine_list) > 2){
					self::verify_and_install_rpm($test_machine_list[2], $rpm_name, BACKUP_TOOLS_PACKAGE_NAME);
					break;
				} else{
					log_function::exit_log_message("Need atleast 3 machines to execute backup tools rpm");	
				}
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
	
	public function clean_install_bz2($remote_machine_name, $bz2_file, $uninstall_packagename){
		remote_function::remote_file_copy($remote_machine_name, BUILD_FOLDER_PATH.$bz2_file, "/tmp");
		general_rpm_function::uninstall_rpm($remote_machine_name, $uninstall_packagename);
		general_function::execute_command("sudo rm -rf /opt/membase", $remote_machine_name);
		general_function::execute_command("sudo tar xvf /tmp/".$bz2_file." -C /opt/", $remote_machine_name);	
	}
	
	public function restart_membase_after_persistance(){
		if(Utility::Check_keys_are_persisted())
		//	return membase_function::reset_membase_servers(array(TEST_HOST_1), False);
		 	return membase_function::restart_membase_servers(TEST_HOST_1);
		else
			return False;
	}
	
}

?>