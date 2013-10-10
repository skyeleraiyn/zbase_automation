<?php

include_once "config.php";

		
Main();


function Main(){
	global $data_sample, $total_no_of_keys;
	global $data_folder, $result_file;
	global $php_pecl_build, $zbase_build, $backup_tools_build;
	
	general_function::initial_setup(array(MASTER_SERVER, SLAVE_SERVER_1));	
	
	$options = getopt("d:h");
	
	if (isset($options["d"])){
		$check_data_size = $options["d"];
		if(array_key_exists($check_data_size, $data_sample)){
			$value = $data_sample[$check_data_size];
			$data_sample = array($check_data_size => $value);
		} else {
			echo "Invalid data size. Valid data size: ";
			foreach( array_keys($data_sample) as $key){
				echo $key." ";
			}	
			echo "\n";	
			exit;
		}
	}
	
		$aBuildInstall = array();
	if(count($php_pecl_build) > 0){
		$aBuildInstall[] = $php_pecl_build;
	}
	if(count($zbase_build) > 0){
		$aBuildInstall[] = $zbase_build;
	}

	// If RPMs are defined in the config file tests will be run for all possible combinations of builds
	// Else installation is skipped 
	if(count($aBuildInstall) and !(SKIP_BUILD_INSTALLATION)){
		$rpm_combination_list = installation::create_rpm_combination_list($aBuildInstall);
		foreach($rpm_combination_list as $rpm_array){
			Performance_function::install_rpm_combination($rpm_array);
			general_function::setup_buildno_folder($rpm_array, MASTER_SERVER);
			Performance_function::install_base_files();
			Performance_function::run_performance_test($data_sample);
		}	
	} else {
		log_function::debug_log("No build defined or SKIP_BUILD_INSTALLATION is set to True. Skipping Installation.");
		if(!(SKIP_BASEFILES_SETUP)){
			Performance_function::install_base_files();
		}
		general_function::setup_buildno_folder();
		Performance_function::run_performance_test($data_sample);
	}
}


function usage(){

	$file = $_SERVER["SCRIPT_NAME"];
	$break = Explode('/', $file);
	$pfile = $break[count($break) - 1];

    echo "\n Usage: $pfile -d data_size (optional) \n";
	exit;
}

	 
?>
