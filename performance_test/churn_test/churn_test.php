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

include_once "config.php";
include_once "../../common/common.php";

Main();


function Main(){
	global $php_pecl_build, $zbase_build, $client_machines_list;
	
	Churn_function::cleanup();
	general_function::initial_setup(array(MASTER_SERVER, SLAVE_SERVER_1));	
	
	general_function::verify_test_machines_interaction($client_machines_list);
	if(defined('STORAGE_SERVER_1') && STORAGE_SERVER_1 <> ""){
		general_function::verify_test_machines_interaction(STORAGE_SERVER_1);
	}
	
	if(GENERATE_SSH_KEYS){
		generate_ssh_key::copy_public_key_to_remote_machines($client_machines_list);
	}
	if(!SKIP_BUILD_INSTALLATION){
		general_function::copy_rpms_to_test_machines($client_machines_list);
		if(defined('STORAGE_SERVER_1') && STORAGE_SERVER_1 <> ""){
			general_function::copy_rpms_to_test_machines(STORAGE_SERVER_1);
		}
	}
	$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($zbase_build) > 0){
		$aBuildInstall[] = $zbase_build;
	}
 
	$rpm_combination_list = installation::create_rpm_combination_list($aBuildInstall);
	foreach($rpm_combination_list as $rpm_array){
		if(!SKIP_BUILD_INSTALLATION){
			Churn_function::install_rpm_combination($rpm_array);
		}
		general_function::setup_buildno_folder($rpm_array, MASTER_SERVER);
		
		Churn_function::install_base_files_and_reset();
		Churn_function::run_churn_test();
		
		// get stats, logs and graphs
		zbase_function::copy_zbase_log_file(MASTER_SERVER, RESULT_FOLDER."/".MASTER_SERVER);
		zbase_function::copy_zbase_log_file(SLAVE_SERVER_1, RESULT_FOLDER."/".SLAVE_SERVER_1);
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
		log_function::result_log("Add key time: $add_keys_time");	
		log_function::result_log("Starting chrun ...");	
		$start_time = time();
		self::churn_keys();
		$churn_keys_time = time() - $start_time;
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
		// get set min_data_age and reset to 0
		$min_data_age = stats_functions::get_all_stats(MASTER_SERVER, "ep_min_data_age");
		flushctl_commands::set_flushctl_parameters(MASTER_SERVER, "min_data_age", 0);
		flushctl_commands::set_flushctl_parameters(SLAVE_SERVER_1, "min_data_age", 0);
	
		remote_function::remote_file_copy(MASTER_SERVER, "add_churn_keys.php", "/tmp/");
		remote_function::remote_file_copy(MASTER_SERVER, "config.php", "/tmp/");
		//remote_function::remote_file_copy(MASTER_SERVER, "Histogram.php", "/tmp/");
		remote_function::remote_execution(MASTER_SERVER, "php /tmp/add_churn_keys.php add");
		
		flushctl_commands::set_flushctl_parameters(MASTER_SERVER, "min_data_age", $min_data_age);
		flushctl_commands::set_flushctl_parameters(SLAVE_SERVER_1, "min_data_age", $min_data_age);
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
			zbase_setup::copy_memcached_files(array(MASTER_SERVER));	
			vbucketmigrator_function::copy_vbucketmigrator_files(array(MASTER_SERVER));
			zbase_setup::copy_slave_memcached_files(array(SLAVE_SERVER_1));
			if(defined('STORAGE_SERVER_1') && STORAGE_SERVER_1 <> ""){
				define('STORAGE_CLOUD', general_function::get_cloud_id_from_server(STORAGE_SERVER_1));
				zbase_backup_setup::install_backup_tools_rpm(SLAVE_SERVER_1);
				zbase_backup_setup::install_backup_tools_rpm(STORAGE_SERVER_1);
				storage_server_setup::install_zstore_and_configure_storage_server(SLAVE_SERVER_1, STORAGE_SERVER_1);
			}
		}
		proxy_server_function::kill_proxyserver_process("localhost");		
		if(defined('STORAGE_SERVER_1') && STORAGE_SERVER_1 <> ""){
			zbase_setup::reset_servers_and_backupfiles(MASTER_SERVER, SLAVE_SERVER_1);	
			
				// Set backup interval to 5 min and start backup service
			$command_to_be_executed = "sudo sed -i 's/^interval.*/interval = 5/' /etc/zbase-backup/default.ini";
			remote_function::remote_execution(SLAVE_SERVER_1, $command_to_be_executed);
			zbase_backup_setup::start_backup_daemon(SLAVE_SERVER_1);			
		} else {	
			zbase_setup::reset_zbase_vbucketmigrator(MASTER_SERVER, SLAVE_SERVER_1);
			tap_commands::deregister_backup_tap_name(SLAVE_SERVER_1);	
		}
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
			  case strstr($rpm_name, "zbase"):
				installation::verify_and_install_rpm(MASTER_SERVER, $rpm_name, ZBASE_PACKAGE_NAME);
				installation::verify_and_install_rpm(SLAVE_SERVER_1, $rpm_name, ZBASE_PACKAGE_NAME);
				break;
			default:
				log_function::exit_log_message("rpm_function not defined for $rpm_name");	
			}
		}
	}	
}



	 
?>
