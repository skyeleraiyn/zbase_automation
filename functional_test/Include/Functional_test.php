<?php

class Functional_test{
	private static $list_tcpdump_pids=array() ;

	public function initial_setup(){
		global $test_machine_list;
		general_function::initial_setup($test_machine_list);

		if(defined('RUN_WITH_VALGRIND') && RUN_WITH_VALGRIND){
			if(!rpm_function::install_valgrind("localhost")){
				log_function::exit_log_message("Installation of valgrind failed");
			}
			// To limit valgrind output only for memcache.ini and igbinary.ini
			general_function::execute_command("sudo mkdir -p /etc/org_php.d/ ; sudo cp -r /etc/php.d/* /etc/org_php.d/ ; sudo rm -rf /etc/php.d/* ; sudo cp /etc/org_php.d/memcache.ini /etc/org_php.d/igbinary.ini /etc/org_php.d/curl.ini /etc/org_php.d/json.ini /etc/php.d ");
		}

		// copy add_keys.php to all the test machines
		foreach($test_machine_list as $test_machine){
			remote_function::remote_file_copy($test_machine, HOME_DIRECTORY."common/misc_files/add_keys", "/tmp/add_keys.php");

			$nstalled_pecl_version = installation::get_installed_pecl_version($test_machine);	
			if(stristr($nstalled_pecl_version, "not installed")){
				log_function::debug_log(PHP_PECL_PACKAGE_NAME." not installed on $test_machine. Pulling the latest version from S3");
				$rpm_name = installation::install_rpm_from_S3(PHP_PECL_PACKAGE_NAME, $test_machine);
			}
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
			zbase_setup::copy_slave_memcached_files(array($test_machine_list[1]));
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
			general_function::execute_command("USE_ZEND_ALLOC=0 valgrind  --leak-check=yes php phpunit.php zbase.php $suite_path $temp_test_machine >".$output_file_path." 2>".$valgrind_file_path, NULL);
		} else {
			general_function::execute_command("php phpunit.php zbase.php $suite_path $temp_test_machine >".$output_file_path, NULL);		
		}		
		self::post_phpunit_test($suite_path, $test_machine);
		log_function::result_log("$suite_path completed");

	}

	private function pre_phpunit_test($test_suite, $test_machine, $output_file_path){
		global $setup_storage_server, $storage_server_pool, $setup_diskmapper_server;

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
				zbase_setup::copy_slave_memcached_files(array($test_machine[2]));
				zbase_setup::reset_zbase_servers($test_machine);
				vbucketmigrator_function::verify_vbucketmigrator_is_running($test_machine[0], $test_machine[1]);
				vbucketmigrator_function::verify_vbucketmigrator_is_running($test_machine[1], $test_machine[2]);
			} else {
				shell_exec("Need 3 machines to execute replication test >>".$output_file_path);
				log_function::debug_log("Need 3 machines to execute replication test");
				return False;
			}
		} else {
			if(is_array($test_machine)){
				zbase_setup::reset_zbase_servers($test_machine);
			} else {
				// Reset zbase to have a fresh setup
				zbase_setup::reset_zbase_servers(array($test_machine));
			}
		}

		// For Persistance suites restart zbase
		if(stristr($test_suite, "persistance")){
			zbase_setup::reset_zbase_servers(array($test_machine));
			sleep(1);
			flushctl_commands::set_flushctl_parameters($test_machine, "min_data_age", 0);
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
				return PROXY_RUNNING;
			}	
		}

		if(strstr($test_suite, "Logger_invalid_rule")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_".$log_conf_file_name." ".LOGCONF_PATH);
		}		
		if(	strstr($test_suite, "Logger_basic") or 
				strstr($test_suite, "Logger_Multi_Ops") or 
				strstr($test_suite, "Logger_non_existant_keys") or 
				strstr($test_suite, "Logger_non_existant_server") or
				strstr($test_suite, "ByKey_logger")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			$log_conf_file_name = str_replace("__independent", "", $log_conf_file_name);
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
		if(strstr($test_suite, "ApacheLog")){
			$log_conf_file_name = str_replace(".php", "", basename($test_suite));
			general_function::execute_command("sudo cp ".LOGCONF_LOCAL_FOLDER_PATH."logconf_LogToSyslog ".LOGCONF_PATH);
		}

		if(stristr($test_suite, "LRU")){
			file_function::keep_copy_original_file(array($test_machine), array(MEMCACHED_SYSCONFIG));
			zbase_setup::edit_sysconfig_file($test_machine , "max_size" , 524288000 , "modify");
			zbase_setup::edit_sysconfig_file($test_machine , "tap_keepalive" , 600 , "modify");
			zbase_setup::edit_sysconfig_file($test_machine , "chk_max_items" , 100 , "modify");
			zbase_setup::edit_sysconfig_file($test_machine , "max_evict_entries" , 500000 , "modify");
		}

		// Testsuites of IBR
		if(	stristr($test_suite, "Backup_Daemon") or 
				stristr($test_suite, "Backup_Tests") or 
				stristr($test_suite, "Core_Merge") or 
				stristr($test_suite, "Daily_Merge__") or 
				stristr($test_suite, "Restore") or
				stristr($test_suite, "DI_IBR") or
				stristr($test_suite, "Master_Merge__")){

			// Set setup_storage_server. Skip configuration if it is already done
			if($setup_storage_server){
				return True;
			} else {
				// check if storage_server and slave machine are defined
				if(count($storage_server_pool) < 1 or count($test_machine) < 1){
					log_function::debug_log("Need atleast 1 storage server and 2 machines to run $test_suite suite");
					return False;
				}

				// check if backup-tools rpm is installed on storage_server and slave machine
				// If not installed, latest rpm from S3 will be installed
				zbase_backup_setup::install_backup_tools_rpm($test_machine[1]);
				zbase_backup_setup::install_backup_tools_rpm($storage_server_pool[0]);				
				storage_server_setup::install_zstore_and_configure_storage_server($test_machine[1], $storage_server_pool[0]);
				file_function::keep_copy_original_file(array($test_machine[1]), array(MEMCACHED_SYSCONFIG, ZBASE_BACKUP_CONSTANTS_FILE, TEST_SPLITLIB_FILE_PATH, DEFAULT_INI_FILE));
				$setup_storage_server = True;					
			}
		}

		// Multi_KVStore testsuite
		if(	stristr($test_suite, "Multi_KVStore")){
			file_function::keep_copy_original_file(array($test_machine[0], $test_machine[1]), array(MEMCACHED_SYSCONFIG, MEMCACHED_MULTIKV_CONFIG));
		}

		// Disk mapper
		if(	stristr($test_suite, "disk_mapper") or
				stristr($test_suite, "Daily_Merge_DM") or
				stristr($test_suite, "Storage_Server_Component") or
				stristr($test_suite, "Torrent") or 
				stristr($test_suite, "Scheduler")){

			if(isset($setup_diskmapper_server) && $setup_diskmapper_server){ 
				return True;
			} else {
				if(count($storage_server_pool) > 2){
					foreach($storage_server_pool as $storage_server){
						storage_server_setup::install_storage_server($storage_server);
						zbase_backup_setup::install_backup_tools_rpm($storage_server);
						backup_tools_functions::set_backup_const($storage_server, "MIN_INCR_BACKUPS_COUNT", 1);
						backup_tools_functions::set_backup_const($storage_server, "ZRT_MAPPER_KEY", ACTIVE_DISKMAPPER_KEY, False);
						remote_function::remote_file_copy($storage_server, HOME_DIRECTORY."common/misc_files/1.7_files/generate_merge_data", "/tmp/generate_merge_data.php");
					}
					file_function::keep_copy_original_file(array($storage_server), array(ZBASE_BACKUP_CONSTANTS_FILE, DEFAULT_INI_FILE));
					zbase_backup_setup::install_backup_tools_rpm($test_machine[1]);
					diskmapper_setup::install_disk_mapper_rpm(DISK_MAPPER_SERVER_ACTIVE);
					file_function::keep_copy_original_file(array(DISK_MAPPER_SERVER_ACTIVE), array(DISK_MAPPER_CONFIG));
					remote_function::remote_file_copy(DISK_MAPPER_SERVER_ACTIVE, HOME_DIRECTORY."common/misc_files/pickle_json.py", "/tmp/pickle_json.py");
					remote_function::remote_file_copy($test_machine[1], HOME_DIRECTORY."common/misc_files/string_json.py", "/tmp/string_json.py");
					file_function::keep_copy_original_file(array($test_machine[1]), array(ZBASE_BACKUP_CONSTANTS_FILE, DEFAULT_INI_FILE));
					file_function::create_dummy_file($test_machine[1], DUMMY_FILE_1);
					file_function::create_dummy_file($test_machine[1], DUMMY_FILE_2);
					file_function::create_dummy_file($test_machine[1], DUMMY_FILE_1GB, 1073741824, False, "zero");
				} else {
					log_function::debug_log("Need 3 storage servers run $test_suite suite");
					return False;			
				}
				$setup_diskmapper_server = True;
			} 
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

		if (stristr($test_suite, "Restore")){
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

		if(stristr($test_suite, "LRU")){
			remote_function::remote_execution($test_machine, "sudo cp ".MEMCACHED_SYSCONFIG.".org ".MEMCACHED_SYSCONFIG);
		}
	}



	public function install_base_files_and_reset(){				
		global $test_machine_list, $proxyserver_installed;

		zbase_setup::copy_memcached_files($test_machine_list);		
		proxy_server_function::kill_proxyserver_process("localhost");
		zbase_setup::reset_zbase_servers($test_machine_list);
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
				installation::verify_and_install_rpm("localhost", $rpm_name, PHP_PECL_PACKAGE_NAME);
				break;
			case strstr($rpm_name, "mcmux"):
				installation::verify_and_install_rpm("localhost", $rpm_name, MCMUX_PACKAGE_NAME);
				$proxyserver_installed = "mcmux";
				break;
			case strstr($rpm_name, "moxi"):
				installation::verify_and_install_rpm("localhost", $rpm_name, MOXI_PACKAGE_NAME);
				$proxyserver_installed = "moxi";
				break;				
			case strstr($rpm_name, "zbase"):
				foreach($test_machine_list as $test_machine){
					installation::verify_and_install_rpm($test_machine, $rpm_name, ZBASE_PACKAGE_NAME);
				}
				break;
			default:
				log_function::exit_log_message("rpm_function not defined for $rpm_name");	
			}
		}
	}		
}

?>
