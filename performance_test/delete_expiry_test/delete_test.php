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
	global $php_pecl_build, $zbase_build;
	

	general_function::initial_setup(array(MASTER_SERVER, SLAVE_SERVER_1));	

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
			Delete_function::install_rpm_combination($rpm_array);
		}
		general_function::setup_buildno_folder($rpm_array, MASTER_SERVER);
			// cleanup 
		remote_function::remote_execution(MASTER_SERVER, "sudo killall -9 php");
		Delete_function::install_base_files_and_reset();
		Delete_function::run_delete_test();
		
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
		graph_functions::get_graphs(MASTER_SERVER, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".MASTER_SERVER."/last_hour", "hour");
		graph_functions::get_graphs(SLAVE_SERVER_1, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".SLAVE_SERVER_1."/last_hour", "hour");	
	}	

}


class Delete_function{

	public function run_delete_test(){
		remote_function::remote_execution(MASTER_SERVER, "sudo rm -rf ".DEBUG_LOG);
		log_function::result_log("Adding keys ...");	
		flushctl_commands::set_flushctl_parameters(MASTER_SERVER, "min_data_age", 0);
		flushctl_commands::set_flushctl_parameters(SLAVE_SERVER_1, "min_data_age", 0);
		remote_function::remote_file_copy(MASTER_SERVER, "add_keys.php", "/tmp/");
		remote_function::remote_file_copy(MASTER_SERVER, "config.php", "/tmp/");
		remote_function::remote_execution(MASTER_SERVER, "php /tmp/add_keys.php ".EXPIRY_TIME." 2>&1 >>/tmp/add_keys_debug.log");
		if(EXPIRY_TIME == 0){
			log_function::result_log("Starting delete ...");
			remote_function::remote_execution(MASTER_SERVER, "php /tmp/add_keys.php delete 2>&1 >>/tmp/add_keys_debug.log");
		}
		$log_contents = remote_function::remote_execution(MASTER_SERVER, "cat ".DEBUG_LOG);
		log_function::result_log($log_contents);
	}
	
	public function install_base_files_and_reset(){	
		if(!SKIP_BASEFILES_SETUP){
			zbase_setup::copy_memcached_files(array(MASTER_SERVER));	
			vbucketmigrator_function::copy_vbucketmigrator_files(array(MASTER_SERVER));
			zbase_setup::copy_slave_memcached_files(array(SLAVE_SERVER_1));
		}
		proxy_server_function::kill_proxyserver_process("localhost");		
		zbase_setup::reset_zbase_vbucketmigrator(MASTER_SERVER, SLAVE_SERVER_1);
		tap_commands::deregister_backup_tap_name(SLAVE_SERVER_1);	
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
				installation::verify_and_install_rpm(MASTER_SERVER, $rpm_name, PHP_PECL_PACKAGE_NAME);
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
