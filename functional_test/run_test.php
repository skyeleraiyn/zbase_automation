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

include_once 'config.php';

Main();

function Main(){
	global $php_pecl_build, $zbase_build, $proxyserver_build;
	global $backup_tools_build, $test_machine_list;

	Functional_test::initial_setup($test_machine_list);

	$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($zbase_build) > 0){
		$aBuildInstall[] = $zbase_build;
	}
	if(count($proxyserver_build) > 0){
		$aBuildInstall[] = $proxyserver_build;
	}
	
	// If RPMs are defined in the config file tests will be run for all possible combinations of builds
	// Else installation is skipped 
	if(count($aBuildInstall) and !(SKIP_BUILD_INSTALLATION)){
		$rpm_combination_list = installation::create_rpm_combination_list($aBuildInstall);
		foreach($rpm_combination_list as $rpm_array){
			Functional_test::install_rpm_combination($rpm_array);
			general_function::setup_buildno_folder($rpm_array, $test_machine_list[0]);
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
