<?php

include_once 'config.php';

Main();

function Main(){
	global $php_pecl_build, $membase_build, $mcmux_build;
	global $backup_tools_build, $test_machine_list;
	
	general_function::initial_setup($test_machine_list);

	$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($membase_build) > 0){
		$aBuildInstall[] = $membase_build;
	}
	if(count($mcmux_build) > 0){
		define('MCMUX_INSTALLED', TRUE);
		$aBuildInstall[] = $mcmux_build;
	}
	if(count($backup_tools_build) > 0){
		$aBuildInstall[] = $backup_tools_build;
	}

	// If RPMs are defined in the config file tests will be run for all possible combinations of builds
	// Else installation is skipped 
	if(count($aBuildInstall) and !(SKIP_BUILD_INSTALLATION_AND_SETUP)){
		$rpm_combination_list = rpm_function::create_rpm_combination_list($aBuildInstall);
		foreach($rpm_combination_list as $rpm_array){
			Functional_test::install_rpm_combination($rpm_array);
			general_function::setup_buildno_folder($rpm_array, $test_machine_list[0]);
			Functional_test::install_base_files_and_reset();
			Functional_test::run_functional_test();
		}	
	} else {
		log_function::debug_log("No build defined or SKIP_BUILD_INSTALLATION_AND_SETUP is set to True. Skipping Installation.");
		if(!(SKIP_BUILD_INSTALLATION_AND_SETUP)){
			Functional_test::install_base_files_and_reset();
		}
		general_function::setup_buildno_folder();
		Functional_test::run_functional_test();
	}
}
	
?>