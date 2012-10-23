<?php

include_once "config.php";

	
Main();


function Main(){
	global $data_folder, $result_file;
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
			Performance_function::install_rpm_combination($rpm_array);
		}
		general_function::setup_buildno_folder($rpm_array, MASTER_SERVER);
		Performance_function::install_base_files_and_reset();
		Performance_function::run_churn_test();
	}	

}



	 
?>