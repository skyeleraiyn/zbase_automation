<?php

include_once "config.php";
include_once "../../common/common.php";

Main();


function Main(){
	global $php_pecl_build, $membase_build;
	

	general_function::initial_setup(array(MASTER_SERVER, SLAVE_SERVER_1));	

	$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($membase_build) > 0){
		$aBuildInstall[] = $membase_build;
	}
 
	$rpm_combination_list = rpm_function::create_rpm_combination_list($aBuildInstall);
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
		graph_functions::get_graphs(MASTER_SERVER, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".MASTER_SERVER."/last_hour", "hour");
		graph_functions::get_graphs(SLAVE_SERVER_1, unserialize(DEFAULT_GRAPH_LIST), RESULT_FOLDER."/".SLAVE_SERVER_1."/last_hour", "hour");	
	}	

}


class Delete_function{

	public function run_delete_test(){
		
		log_function::result_log("Adding keys ...");	
		remote_function::remote_file_copy(MASTER_SERVER, "add_keys.php", "/tmp/");
		remote_function::remote_file_copy(MASTER_SERVER, "config.php", "/tmp/");
		remote_function::remote_execution(MASTER_SERVER, "php /tmp/add_keys.php ".EXPIRY_TIME);
		if(EXPIRY_TIME == 0){
			log_function::result_log("Starting delete ...");
			remote_function::remote_execution(MASTER_SERVER, "php /tmp/add_keys.php delete");
		}
	}
	
	public function install_base_files_and_reset(){	
		if(!SKIP_BASEFILES_SETUP){
			membase_function::copy_memcached_files(array(MASTER_SERVER));	
			vbucketmigrator_function::copy_vbucketmigrator_files(array(MASTER_SERVER));
			membase_function::copy_slave_memcached_files(array(SLAVE_SERVER_1));
		}
		proxy_server_function::kill_proxyserver_process("localhost");		
		membase_function::reset_membase_vbucketmigrator(MASTER_SERVER, SLAVE_SERVER_1);
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
				self::verify_and_install_rpm(MASTER_SERVER, $rpm_name, PHP_PECL_PACKAGE_NAME);
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
}



	 
?>