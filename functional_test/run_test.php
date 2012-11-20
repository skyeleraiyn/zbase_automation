<?php

include_once 'config.php';

Main();

function Main(){
	global $php_pecl_build, $membase_build, $proxyserver_build;
	global $backup_tools_build, $test_machine_list;

	Functional_test::initial_setup($test_machine_list);

	$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($membase_build) > 0){
		$aBuildInstall[] = $membase_build;
	}
	if(count($proxyserver_build) > 0){
		$aBuildInstall[] = $proxyserver_build;
	}
	if(count($backup_tools_build) > 0){
		$aBuildInstall[] = $backup_tools_build;
	}

	// If RPMs are defined in the config file tests will be run for all possible combinations of builds
	// Else installation is skipped 
	if(count($aBuildInstall) and !(SKIP_BUILD_INSTALLATION)){
		$rpm_combination_list = rpm_function::create_rpm_combination_list($aBuildInstall);
		foreach($rpm_combination_list as $rpm_array){
			Functional_test::install_rpm_combination($rpm_array);
			general_function::setup_buildno_folder($rpm_array, $test_machine_list[0], $test_machine_list[1]);
			Functional_test::install_base_files_and_reset();
			Functional_test::run_functional_test();
		}	
	} else {
		log_function::debug_log("No build defined or SKIP_BUILD_INSTALLATION is set to True. Skipping Installation.");
		Functional_test::install_base_files_and_reset();
		general_function::setup_buildno_folder();
		Functional_test::run_functional_test();
	}
}
	
?>