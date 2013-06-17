<?php
class vbucketmigrator_function{

	public function vbucketmigrator_service($remote_machine_name, $command) {
		return service_function::control_service($remote_machine_name, VBUCKETMIGRATOR_SERVICE, $command);		
	}

	public function copy_vbucketmigrator_log_file($remote_machine_name, $destination_path){
		remote_function::remote_file_copy($remote_machine_name, VBUCKETMIGRATOR_LOG_FILE, $destination_path."_vbucketmigrator.log", True);
	}
	
	public function clear_vbucketmigrator_log_file($remote_machine){
		file_function::clear_log_files($remote_machine, VBUCKETMIGRATOR_LOG_FILE);
	}
	
	public function add_slave_machine_sysconfig_file($master_machine_name, $slave_machine_name) {
		remote_function::remote_execution($master_machine_name, "echo SLAVE=$slave_machine_name | sudo tee ".VBUCKETMIGRATOR_SYSCONFIG_PATH);
		if(MEMBASE_VERSION <> 1.6){
			remote_function::remote_execution($master_machine_name, "echo TAPNAME=replication | sudo tee -a ".VBUCKETMIGRATOR_SYSCONFIG_PATH);
		}
	}

	public function kill_vbucketmigrator($remote_machine_name) {
		return process_functions::kill_process($remote_machine_name, "vbucketmigrator vbucketmigrator.sh");
	}

	public function copy_vbucketmigrator_files(array $remote_server_array){
		foreach($remote_server_array as $remote_server){
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."vbucketmigrator_init.d", VBUCKETMIGRATOR_INIT, False, True, True);
			remote_function::remote_file_copy($remote_server, BASE_FILES_PATH."vbucketmigrator.sh", VBUCKETMIGRATOR_SH, False, True, True);
		}
	}
	
	public function attach_vbucketmigrator($master_machine_name, $slave_machine_name) {
		self::add_slave_machine_sysconfig_file($master_machine_name, $slave_machine_name);
		if(MEMBASE_VERSION <> 1.6){
			tap_commands::register_replication_tap_name($master_machine_name);
		}
		self::vbucketmigrator_service($master_machine_name, "start");
		sleep(5);
	}

	public function verify_vbucketmigrator_is_running($master_machine_name, $slave_machine_name){
		if(!(process_functions::check_process_exists($master_machine_name, VBUCKETMIGRATOR_PROCESS)))
			self::attach_vbucketmigrator($master_machine_name, $slave_machine_name);
	}

	
}
?>