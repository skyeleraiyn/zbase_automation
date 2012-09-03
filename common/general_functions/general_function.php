<?php
class general_function{

	public function initial_setup($remote_machine_list){	
	
		if(count(array_unique($remote_machine_list))<count($remote_machine_list)){
			log_function::exit_log_message("test_machine_list has duplicates");
		}

			// Clean up RightScale cookies
		self::execute_command("sudo rm -rf /tmp/rscookie*");
		self::setup_result_folder();
		
		generate_ssh_key::get_password();	
		generate_ssh_key::add_stricthost_keycheck();
		
		if(GENERATE_SSH_KEYS){
			self::verify_expect_module_installation();
		}	
		self::verify_test_machines_interaction($remote_machine_list);
		if(GENERATE_SSH_KEYS){
			generate_ssh_key::copy_public_key_to_remote_machines($remote_machine_list);
		}			
		self::set_swappiness($remote_machine_list);
		
		if(!(SKIP_BUILD_INSTALLATION_AND_SETUP)){
			self::convert_files_dos_2_unix();
			self::copy_rpms_to_test_machines($remote_machine_list);
			
		}
		if(!general_rpm_function::install_python26($remote_machine_list)){
			log_function::exit_log_message("Installation of python26 failed");
		}
	}	
	
	public function copy_rpms_to_test_machines($remote_machine_list){
		foreach($remote_machine_list as $remote_machine){
			remote_function::remote_file_copy($remote_machine, BUILD_FOLDER_PATH, "/tmp/");
		}	
	}
	
	public function setup_result_folder(){

		if(file_exists(RESULT_FOLDER)){
			shell_exec("sudo mv ".RESULT_FOLDER." ".RESULT_FOLDER."_".time());
		}	
		
		return directory_function::create_folder(RESULT_FOLDER);
	}	
	
	public function setup_buildno_folder($rpm_array = NULL, $membase_server = NULL, $backup_server = NULL){
		global $buildno_folder_path, $result_file;
		$buildno_folder_path = "";
		
		if($rpm_array == NULL){
				// If no builds are specified, create a result folder with currently installed pecl version and run the testcases
			$nstalled_pecl_version = rpm_function::get_installed_pecl_version();	
			if(stristr($nstalled_pecl_version, "not installed")){
				log_function::result_log("php-pecl not installed. Aborting test.");
				exit;
			}
			$buildno_folder_path = $nstalled_pecl_version;
		}else {
			// Generate result folder name based on installed rpm version nos
			$build_version = "";
			foreach($rpm_array as $rpm){
				switch (true) {
				  case strstr($rpm, "php-pecl"):
					$build_version = $build_version.rpm_function::get_installed_pecl_version()."_";
					break;
				  case strstr($rpm, "mcmux"):
					$build_version = $build_version.rpm_function::get_installed_mcmux_version()."_";		
					break;
				  case strstr($rpm, "membase"):
					$build_version = $build_version.rpm_function::get_installed_membase_version($membase_server)."_";
					break;
				  case strstr($rpm, "backup"):
					$build_version = $build_version.rpm_function::get_installed_backup_tools_version($backup_server)."_";
					break;					
				}
			}
			$buildno_folder_path = substr($build_version, 0, -1 )	;
			
		}

		$result_file =  RESULT_FOLDER."/".$buildno_folder_path."/"."result.log";
		
		if(defined('RUN_WITH_VALGRIND') And RUN_WITH_VALGRIND){
			return directory_function::create_folder(RESULT_FOLDER."/".$buildno_folder_path."/valgrind");
		} else {
			return directory_function::create_folder(RESULT_FOLDER."/".$buildno_folder_path);
		}			
	}
	
	public function setup_data_folder($data_size) {
		global $data_folder, $result_file, $buildno_folder_path;
		
		$data_folder = RESULT_FOLDER."/".$buildno_folder_path."/".$data_size; 
		$result_file =  $data_folder."/"."result.log";
		return directory_function::create_folder($data_folder);
	}

	public function get_caller_function($function = NULL, $use_stack = NULL) {
		if ( is_array($use_stack) ) {
			$stack = $use_stack;
		} else {
			$stack = debug_backtrace();
		}

		if ($function == NULL) {
			$function = self::get_caller_function(__FUNCTION__, $stack);
		}

		if ( is_string($function) && $function != "" ) {
			for ($i = 0; $i < count($stack); $i++) {
				$curr_function = $stack[$i];
				if ( $curr_function["function"] == $function && ($i) < count($stack) ) {
					return $stack[$i + 1]["function"];
				}
			}
		}
	}	
	
	// To use this function set $pos = 0 
	// $temp_array and $combination_list_array = array() and make them global variable.
	public function generateCombination($input_array) {
		global $temp_array, $pos, $combination_list_array;
		if(count($input_array)) {
			for($i=0; $i<count($input_array[0]); $i++) {
				$tmp = $input_array;
				$temp_array[$pos] = $input_array[0][$i];
				$tarr = array_shift($tmp);
				$pos++;
				self::generateCombination($tmp);
			}
			$pos--;
		} else {

		$combination_list_array[] = $temp_array;
		$pos--;
		}
	}
	
	public function execute_command($command_to_be_executed, $remote_machine_name = NULL){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name." ".$command_to_be_executed);
		
		if($remote_machine_name){
			$execute_command_output = remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
		} else {
			$execute_command_output = shell_exec($command_to_be_executed);
		}
		$execute_command_output = trim($execute_command_output);
		log_function::debug_log($execute_command_output);
		return $execute_command_output;
	}

	public function verify_expect_module_installation(){
		if(file_exists("/usr/lib64/php/modules/expect.so"))
			return True;
		else
			log_function::exit_log_message("Expect module is not installed.\n Kindly follow the instructions available under tests/common/misc_files/expect_packages ");
	}	

	public function verify_test_machines_interaction($remote_machine_list){
		if(is_array($remote_machine_list)){
			foreach($remote_machine_list as $remote_machine_name){
				self::verify_test_machines_interaction($remote_machine_name);
			}
		} else {
			$output = remote_function::remote_execution($remote_machine_list, "time");
			log_function::debug_log($output);
			if(!stristr($output, "real")){
				log_function::exit_log_message("unable to establish connection to ".$remote_machine_list);
			}
		}
	}
	
	public function convert_files_dos_2_unix(){
		log_function::debug_log(self::execute_command("sudo dos2unix ".HOME_DIRECTORY."common/misc_files/1.6_files"."/* 2>&1"));
		log_function::debug_log(self::execute_command("sudo dos2unix ".HOME_DIRECTORY."common/misc_files/1.7_files"."/* 2>&1"));
	}
	
	
	
	public function set_swappiness($remote_machine_list){
	
		if(is_array($remote_machine_list)){
			foreach($remote_machine_list as $remote_machine_name){
				self::set_swappiness($remote_machine_name);
			}
		} else {
			remote_function::remote_execution($remote_machine_list, "sudo /sbin/sysctl vm.swappiness=0");
		}
	
	
	}
}
?>