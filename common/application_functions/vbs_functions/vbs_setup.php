<?php
class vbs_setup	{

	public function vbs_start_stop($command = "start")	{
		vbs_stub_setup::vbucket_server_service($command);
	}
	#This function is responsible for populating the vbs config file from the test suite config file and transferring it to the VBS machine
	public function populate_and_copy_config_file($vbuckets = NO_OF_VBUCKETS, $capacity = CLUSTER_CAPACITY)	{
		global $test_machine_list;
		$array_string = implode("," , $test_machine_list);
		if(!rpm_function::install_python_simplejson("localhost")){
                	log_function::exit_log_message("Installation of simplejson failed");
		}
		if(!rpm_function::install_pdsh("localhost")){
			log_function::exit_log_message("Installation of pdsh failed");
		}
		$command_to_be_executed = "python26 ".HOME_DIRECTORY."common/misc_files/1.9_files/Config_Generate.py ".$array_string." ".$vbuckets." ".NO_OF_REPLICAS." ".CLUSTER_CAPACITY;
		log_function::debug_log("Generating config ".$command_to_be_executed);
		$config = shell_exec($command_to_be_executed);
		file_function::write_to_file("/tmp/vbucketserver", $config, "w");
		remote_function::remote_file_copy(VBS_IP, "/tmp/vbucketserver", VBS_CONFIG, False, True, True);
	}
}