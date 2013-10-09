<?php
class general_function{

	public function initial_setup($remote_machine_list){	
		global $storage_server_pool;
		
		if(count(array_unique($remote_machine_list))<count($remote_machine_list)){
			log_function::exit_log_message("test_machine_list has duplicates");
		}

			// Clean up RightScale cookies
		self::execute_command("sudo rm -rf /tmp/rscookie*");
		
		// Create build folder directory
		self::execute_command("mkdir -p ".BUILD_FOLDER_PATH);
		
		self::setup_result_folder();
		
		generate_ssh_key::get_password();	
		generate_ssh_key::add_stricthost_keycheck();
		self::verify_expect_module_installation();
		
			// verify test machine can be reached
		self::verify_test_machines_interaction($remote_machine_list);
		if($storage_server_pool <> ""  && count($storage_server_pool) > 0){
			self::verify_test_machines_interaction($storage_server_pool);
		}
		if(defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> ""){
			self::verify_test_machines_interaction(array(DISK_MAPPER_SERVER_ACTIVE));
		}
		if(defined('DISK_MAPPER_SERVER_PASSIVE') && DISK_MAPPER_SERVER_PASSIVE <> ""){
			self::verify_test_machines_interaction(array(DISK_MAPPER_SERVER_PASSIVE));
		}
				
		if(GENERATE_SSH_KEYS){
			generate_ssh_key::copy_public_key_to_remote_machines($remote_machine_list);
			if($storage_server_pool <> ""  && count($storage_server_pool) > 0){
				generate_ssh_key::copy_public_key_to_remote_machines($storage_server_pool);
			}
			if(defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> ""){
				generate_ssh_key::copy_public_key_to_remote_machines(array(DISK_MAPPER_SERVER_ACTIVE));
			}
			if(defined('DISK_MAPPER_SERVER_PASSIVE') && DISK_MAPPER_SERVER_PASSIVE <> ""){
				generate_ssh_key::copy_public_key_to_remote_machines(array(DISK_MAPPER_SERVER_PASSIVE));
			}		
		}			
			// set swappiness to 0 for zbase servers
		self::set_swappiness($remote_machine_list);
		
		if(!(SKIP_BUILD_INSTALLATION)){
			self::execute_command("sudo rm -rf ".LATEST_RELEASED_RPM_LIST_LOCAL_PATH);
			$download_output = self::execute_command("wget --directory-prefix=".BUILD_FOLDER_PATH." ".LATEST_RELEASED_RPM_LIST_PATH." 2>&1");
			if(stristr($download_output, "Forbidden") or stristr($download_output, "Name or service not known")){
				log_function::exit_log_message("Error downloading file ".LATEST_RELEASED_RPM_LIST_PATH);
			}
			self::copy_rpms_to_test_machines($remote_machine_list);
			if($storage_server_pool <> ""  && count($storage_server_pool) > 0){
				self::copy_rpms_to_test_machines($storage_server_pool);
			}
			if(defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> ""){
				self::copy_rpms_to_test_machines(array(DISK_MAPPER_SERVER_ACTIVE));
			}
			if(defined('DISK_MAPPER_SERVER_PASSIVE') && DISK_MAPPER_SERVER_PASSIVE <> ""){
				self::copy_rpms_to_test_machines(array(DISK_MAPPER_SERVER_PASSIVE));
			}			
		}
		self::convert_files_dos_2_unix();
			// Install python26
		if(!rpm_function::install_python26($remote_machine_list)){
			log_function::exit_log_message("Installation of python26 failed");
		}
		if($storage_server_pool <> ""  && count($storage_server_pool) > 0){
			if(!rpm_function::install_python26($storage_server_pool)){
				log_function::exit_log_message("Installation of python26 failed");
			}			
		}
		if(defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> ""){
			if(!rpm_function::install_python26(DISK_MAPPER_SERVER_ACTIVE)){
				log_function::exit_log_message("Installation of python26 failed");
			}
		}
		if(defined('DISK_MAPPER_SERVER_PASSIVE') && DISK_MAPPER_SERVER_PASSIVE <> ""){
			if(!rpm_function::install_python26(DISK_MAPPER_SERVER_PASSIVE)){
				log_function::exit_log_message("Installation of python26 failed");
			}
		}		
		
			// Get the cloud id from the first test_machine and first storage server
		define('ZBASE_CLOUD', self::get_cloud_id_from_server($remote_machine_list[0]));
		log_function::write_to_temp_config("ZBASE_CLOUD=".ZBASE_CLOUD, "a");
		if($storage_server_pool <> ""  && count($storage_server_pool) > 0){
			define('STORAGE_CLOUD', self::get_cloud_id_from_server($storage_server_pool[0]));
			log_function::write_to_temp_config("STORAGE_CLOUD=".STORAGE_CLOUD, "a");		
		}
				
		define('CENTOS_VERSION', self::get_CentOS_version($remote_machine_list[0]));
		log_function::write_to_temp_config("CENTOS_VERSION=".CENTOS_VERSION, "a");
		zbase_function::define_zbase_db_path();	
		
	}	
	
	public function get_CentOS_version($remote_machine_name){
		$centos_version = trim(self::execute_command("cat /etc/redhat-release", $remote_machine_name));
		if(stristr($centos_version, "5.")){
			return 5;
		} else {
			return 6;
		}	
	}
	
	public function get_cloud_id_from_server($remote_server_name){
		$dns_zone = trim(self::execute_command("cat /etc/zynga/dns_zone", $remote_server_name));
		if(!stristr($dns_zone, "No such file")){
				// /etc/zynga/dns_zone file should syntax ec2.zynga.com.
			return trim(str_replace(".zynga.com.", "", $dns_zone));
		} else {
			$hostname = trim(self::execute_command("hostname", $remote_server_name));
			if(stristr($hostname, "zynga.com")){
					// to handle hostname with this syntax netops-backup-mb-00.va1.zynga.com
				$hostname = str_replace(".zynga.com", "", $hostname);
				$hostname = explode(".", $hostname);
				return trim(end($hostname));
			} else {
				log_function::exit_log_message("Cannot find the cloud id for $remote_server_name");
			}
		}		
	}
	
	public function set_system_date($remote_machine_name, $date)	{
		$command_to_be_executed = "date -d \"$date\" \"+%Y-%m-%d\"";
		$date_to_be_set = remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
		$command_to_be_executed = "sudo date +%T -s \"$date_to_be_set\"";
		remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function set_system_time($remote_machine_name, $time)	{
		$command_to_be_executed = "sudo date +%T -s \"$time\"";
		remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function reset_system_time($remote_machine_name)	{
		$command_to_be_executed = "sudo /etc/init.d/ntpd stop; sudo /usr/sbin/ntpdate ntp.ubuntu.com pool.ntp.org; sudo /etc/init.d/ntpd start";
		remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}
	
	public function copy_rpms_to_test_machines($remote_machine_list){
		$current_machine_name = trim(self::execute_command("hostname"));
		
		if(is_array($remote_machine_list)){
			foreach($remote_machine_list as $remote_machine){
				if($remote_machine == $current_machine_name) continue;
				remote_function::remote_execution($remote_machine, "sudo chown -R ".TEST_USERNAME." ".BUILD_FOLDER_PATH);
				self::execute_command("rsync ".BUILD_FOLDER_PATH." $remote_machine:".BUILD_FOLDER_PATH." --checksum --recursive");
			}		
		} else {
			if($remote_machine_list <> $current_machine_name){
				remote_function::remote_execution($remote_machine_list, "sudo chown -R ".TEST_USERNAME." ".BUILD_FOLDER_PATH);
				self::execute_command("rsync ".BUILD_FOLDER_PATH." $remote_machine_list:".BUILD_FOLDER_PATH." --checksum --recursive");	
			}		
		}
	}
	
	public function setup_result_folder(){
		if(file_exists(RESULT_FOLDER)){
			$time_arr=getdate(time());
			$timestamp=$time_arr["mday"]."_".$time_arr["mon"]."_".$time_arr["year"]."_".$time_arr["hours"]."_".$time_arr["minutes"];
			shell_exec("sudo mv ".RESULT_FOLDER." ".RESULT_FOLDER."_".$timestamp);
		}		
		return directory_function::create_directory(RESULT_FOLDER);
	}	
	
	public function setup_buildno_folder($rpm_array = NULL, $zbase_server = NULL){
		global $buildno_folder_path, $result_file;
		$buildno_folder_path = "";
		
		if($rpm_array == NULL){
				// If no builds are specified, create a result folder with currently installed pecl version and run the testcases
			$nstalled_pecl_version = installation::get_installed_pecl_version();	
			if(stristr($nstalled_pecl_version, "not installed")){
				log_function::debug_log(PHP_PECL_PACKAGE_NAME." not installed. Pulling the latest version from S3");
				$rpm_name = installation::install_rpm_from_S3(PHP_PECL_PACKAGE_NAME, "localhost");
				self::setup_buildno_folder();
			}
			$buildno_folder_path = $nstalled_pecl_version;
			log_function::result_log("pecl version: ".rpm_function::get_rpm_version(NULL, PHP_PECL_PACKAGE_NAME));
		}else {
			// Generate result folder name based on installed rpm version nos
			$build_version = "";
			foreach($rpm_array as $rpm){
				switch (true) {
				  case strstr($rpm, "php-pecl"):
					$build_version = $build_version.installation::get_installed_pecl_version()."_";
					log_function::result_log("pecl version: ".rpm_function::get_rpm_version(NULL, PHP_PECL_PACKAGE_NAME));
					break;
				  case strstr($rpm, "mcmux"):
					$build_version = $build_version.installation::get_installed_mcmux_version()."_";	
					log_function::result_log("mcmux version: ".rpm_function::get_rpm_version(NULL, MCMUX_PACKAGE_NAME));					
					break;
				  case strstr($rpm, "moxi"):
					$build_version = $build_version.installation::get_installed_moxi_version()."_";	
					log_function::result_log("moxi version: ".rpm_function::get_rpm_version(NULL, MOXI_PACKAGE_NAME));					
					break;						
				  case strstr($rpm, "zbase"):
					$build_version = $build_version.installation::get_installed_zbase_version($zbase_server)."_";
					log_function::result_log("zbase version: ".rpm_function::get_rpm_version($zbase_server, ZBASE_PACKAGE_NAME));
					break;				
				}
			}
			$buildno_folder_path = substr($build_version, 0, -1 )	;
			
		}

		$result_file =  RESULT_FOLDER."/".$buildno_folder_path."/"."result.log";
		
		if(defined('RUN_WITH_VALGRIND') And RUN_WITH_VALGRIND){
			return directory_function::create_directory(RESULT_FOLDER."/".$buildno_folder_path."/valgrind");
		} else {
			return directory_function::create_directory(RESULT_FOLDER."/".$buildno_folder_path);
		}			
	}
	
	public function setup_data_folder($data_size) {
		global $data_folder, $result_file, $buildno_folder_path;
		
		$data_folder = RESULT_FOLDER."/".$buildno_folder_path."/".$data_size; 
		$result_file =  $data_folder."/"."result.log";
		return directory_function::create_directory($data_folder);
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
		log_function::debug_log(self::get_caller_function());
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
		if(file_exists("/usr/lib64/php/modules/expect.so")){
			return True;
		} else {
			echo "Installing expect module required to run the tests...";
			rpm_function::yum_install("expect");
			if(stristr(self::execute_command("cat /etc/redhat-release"), "5.4")){
				self::execute_command("sudo cp ".HOME_DIRECTORY."common/misc_files/expect_packages/expect_el5.so /usr/lib64/php/modules/expect.so");
			} else {
				self::execute_command("sudo cp ".HOME_DIRECTORY."common/misc_files/expect_packages/expect_el6.so /usr/lib64/php/modules/expect.so");
				self::execute_command("sudo cp ".HOME_DIRECTORY."common/misc_files/expect_packages/libexpect.so /usr/lib64/libexpect.so");
			}
			self::execute_command("sudo su -c 'echo extension=expect.so >> /etc/php.ini'");			
			echo "Done.\nRe-run your test.\n";
			exit;
		}	
	}	

	public function verify_test_machines_interaction($remote_machine_list){
		if(is_array($remote_machine_list)){
			foreach($remote_machine_list as $remote_machine_name){
				self::verify_test_machines_interaction($remote_machine_name);
			}
		} else {
			$output = remote_function::remote_execution_popen($remote_machine_list, "time");
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
	
	public function get_ip_address($remote_machine, $list_all_ips = True){
		if($list_all_ips){
			$ip_address_list = trim(remote_function::remote_execution($remote_machine, "/sbin/ifconfig | grep 'inet addr' | grep -v 127.0.0.1"));
			$ip_address_list = explode("\n", $ip_address_list);
			foreach($ip_address_list as &$ip_address){
				$ip_address = explode(" ", trim($ip_address));
				$ip_address = $ip_address[1];
				$ip_address = trim(str_replace("addr:", "", $ip_address));
			}
			return $ip_address_list;
		} else {
			if(filter_var($remote_machine, FILTER_VALIDATE_IP)){
				return $remote_machine;
			} else {
				$ip_address = explode(" ", general_function::execute_command("host ".$remote_machine));
				return end($ip_address);
			}
		
		}
	}
	
	public function get_hostname($hostname){
		if(filter_var($hostname, FILTER_VALIDATE_IP)){
			return $hostname;
		} else {
			$hostname = explode(".", $hostname);
			return $hostname[0];
		}	
	}
}
?>
