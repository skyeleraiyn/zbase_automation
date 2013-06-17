<?php

include_once "config.php";
include_once "../../common/common.php";

Main();


function Main(){
	global $php_pecl_build, $membase_build, $client_machines_list;
	

	general_function::initial_setup(array(MASTER_SERVER, SLAVE_SERVER_1));	
	
	general_function::verify_test_machines_interaction($client_machines_list);
	
	if(GENERATE_SSH_KEYS){
		generate_ssh_key::copy_public_key_to_remote_machines($client_machines_list);
	}
	if(!SKIP_BUILD_INSTALLATION){
		general_function::copy_rpms_to_test_machines($client_machines_list);
	}
	$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($membase_build) > 0){
		$aBuildInstall[] = $membase_build;
	}
 
	$rpm_combination_list = installation::create_rpm_combination_list($aBuildInstall);
	foreach($rpm_combination_list as $rpm_array){
		if(!SKIP_BUILD_INSTALLATION){
			Churn_function::install_rpm_combination($rpm_array);
		}
		general_function::setup_buildno_folder($rpm_array, MASTER_SERVER);
		Churn_function::cleanup();
		Churn_function::install_base_files_and_reset();
		Churn_function::run_churn_test();
		
		// get stats, logs and graphs
		membase_function::copy_membase_log_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		membase_function::copy_membase_log_file(SLAVE_SERVER_1, RESULT_FOLDER."/".SLAVE_SERVER_1);
		vbucketmigrator_function::copy_vbucketmigrator_log_file(MASTER_SERVER, RESULT_FOLDER."/".SLAVE_SERVER_1);
		
		stats_commands::capture_timings_stats_to_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		stats_commands::capture_all_stats_to_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		stats_commands::capture_checkpoint_stats_to_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		stats_commands::capture_eviction_stat_to_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		stats_commands::capture_tap_stats_to_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		
		stats_commands::capture_timings_stats_to_file(SLAVE_SERVER_1, RESULT_FOLDER."/".SLAVE_SERVER_1);
		stats_commands::capture_all_stats_to_file(SLAVE_SERVER_1, RESULT_FOLDER."/".SLAVE_SERVER_1);
		stats_commands::capture_checkpoint_stats_to_file(SLAVE_SERVER_1, RESULT_FOLDER."/".SLAVE_SERVER_1);
		stats_commands::capture_eviction_stat_to_file(SLAVE_SERVER_1, RESULT_FOLDER."/".SLAVE_SERVER_1);
		
		graph_functions::get_graphs(MASTER_SERVER, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".MASTER_SERVER, "day");
		graph_functions::get_graphs(SLAVE_SERVER_1, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".SLAVE_SERVER_1, "day");
		graph_functions::get_graphs(MASTER_SERVER, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".MASTER_SERVER."/last_hour");
		graph_functions::get_graphs(SLAVE_SERVER_1, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".SLAVE_SERVER_1."/last_hour");		

	}	

}


class Churn_function{

	public function run_churn_test(){
		
		
		log_function::result_log("Adding keys ...");	
		$start_time = time();
		self::add_keys();
		$add_keys_time = time() - $start_time;
		log_function::result_log("Starting chrun ...");	
		$start_time = time();
		self::churn_keys();
		$churn_keys_time = time() - $start_time;
		log_function::result_log("Add key time: $add_keys_time");	
		log_function::result_log("Churn key time: $churn_keys_time");
		$mc_slave = new memcache();
		$mc_slave->addserver(SLAVE_SERVER_1, 11211);
		$client_set_failure = $mc_slave->get("client_set_failure");
		$client_get_miss = $mc_slave->get("client_get_miss");
		
		log_function::result_log("client_set_failure: $client_set_failure");
		log_function::result_log("client_get_miss: $client_get_miss");
	}

	public function cleanup(){
		global $client_machines_list;
		
		$current_machine_name = trim(general_function::execute_command("hostname"));
		foreach($client_machines_list as $client_machine){
			if(stristr($current_machine_name, $client_machine)){
				$current_process = getmypid();
				$list_of_php_process = trim(general_function::execute_command("/sbin/pidof php"));
				$list_of_php_process = explode(" ", $list_of_php_process);
				foreach($list_of_php_process as $kill_process){
					if($kill_process == $current_process) continue;
					general_function::execute_command("sudo kill -9 ".$kill_process." 2>&1");
				}
			} else {
				remote_function::remote_execution($client_machine, "sudo killall -9 php");
			}
		}	
		remote_function::remote_execution(MASTER_SERVER, "sudo killall -9 php");
	}
		
	public function add_keys(){
		remote_function::remote_file_copy(MASTER_SERVER, "add_churn_keys.php", "/tmp/");
		remote_function::remote_file_copy(MASTER_SERVER, "config.php", "/tmp/");
		//remote_function::remote_file_copy(MASTER_SERVER, "Histogram.php", "/tmp/");
		remote_function::remote_execution(MASTER_SERVER, "php /tmp/add_churn_keys.php add");
	}
	
	public function churn_keys(){
		global $client_machines_list;
		
		foreach($client_machines_list as $client_machine){
			remote_function::remote_file_copy($client_machine, "add_churn_keys.php", "/tmp/");
			remote_function::remote_file_copy($client_machine, "config.php", "/tmp/");
			//remote_function::remote_file_copy($client_machine, "Histogram.php", "/tmp/");
			$ip_address_list = general_function::get_ip_address(MASTER_SERVER);
			$ip_address_list = implode(":", $ip_address_list);
			$pid_count = 0;
			$pid = pcntl_fork();
			if ($pid == 0){	
				remote_function::remote_execution($client_machine, "php /tmp/add_churn_keys.php ".$ip_address_list);
				exit;
			} else {
				$pid_arr[$pid_count] = $pid;
				$pid_count++;
			}
		}
			
		foreach($pid_arr as $pid){	
			pcntl_waitpid($pid, $status);			
			if(pcntl_wexitstatus($status) == 4) exit;
			sleep(1);
		}	
	}	
	
	
	public function install_base_files_and_reset(){	
		if(!SKIP_BASEFILES_SETUP){
			membase_setup::copy_memcached_files(array(MASTER_SERVER));	
			vbucketmigrator_function::copy_vbucketmigrator_files(array(MASTER_SERVER));
			membase_setup::copy_slave_memcached_files(array(SLAVE_SERVER_1));
		}
		proxy_server_function::kill_proxyserver_process("localhost");		
		membase_setup::reset_servers_and_backupfiles(MASTER_SERVER, SLAVE_SERVER_1);
#		membase_backup_setup::start_backup_daemon_full(SLAVE_SERVER_1);
		sleep(10);
		membase_backup_setup::start_backup_daemon(SLAVE_SERVER_1);
		sleep(10);
#		tap_commands::deregister_backup_tap_name(SLAVE_SERVER_1);
	
	}
	
	// This function skips installation if the rpm is already installed from the previous run. 
	// However it verifies that machines are updated to the latest rpm combination
	public function install_rpm_combination($rpm_array){
		// Global array to maintain installed rpms
		global $list_of_installed_rpms, $client_machines_list;
		if(!(isset($list_of_installed_rpms))){
			$list_of_installed_rpms[] = array();
		}
		foreach($rpm_array as $rpm_name){			
			switch (true) {
			  case strstr($rpm_name, "php-pecl"):
				installation::verify_and_install_rpm(MASTER_SERVER, $rpm_name, PHP_PECL_PACKAGE_NAME);
				foreach($client_machines_list as $client_machine){
					installation::verify_and_install_rpm($client_machine, $rpm_name, PHP_PECL_PACKAGE_NAME);
				}
				break;
			  case strstr($rpm_name, "membase"):
				installation::verify_and_install_rpm(MASTER_SERVER, $rpm_name, MEMBASE_PACKAGE_NAME);
				installation::verify_and_install_rpm(SLAVE_SERVER_1, $rpm_name, MEMBASE_PACKAGE_NAME);
				break;
			default:
				log_function::exit_log_message("rpm_function not defined for $rpm_name");	
			}
		}
	}	
}



	 
?>
