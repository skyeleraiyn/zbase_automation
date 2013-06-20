<?php
class cluster_setup	{

	public function setup_membase_cluster()	{
		vbs_setup::vbs_start_stop("stop");
		vba_setup::vba_cluster_start_stop("stop");
		membase_setup::clear_cluster_membase_database();
                membase_setup::restart_membase_cluster();
		vba_setup::vba_cluster_start_stop("start");
		vbs_setup::populate_and_copy_config_file();
		vbs_setup::vbs_start_stop("start");
	}

	public function setup_membase_cluster_with_ibr() {
                global $storage_server_pool;
		$pid_count = 0;
		$pid = pcntl_fork();
		if($pid == 0) {
			diskmapper_setup::reset_diskmapper_storage_servers($storage_server_pool);
			if(!self::initialize_vb_storage_mapping()) {
				log_function::debug_log("couldn't initialize mapping");
				exit(1);
			}
			else { 
				exit(0);	
			}
		}
		else {

			self::setup_membase_cluster();
		}
		pcntl_waitpid($pid, $status);
		if(pcntl_wexitstatus($status) == 1)  return False;
		return True;
	}
	
	public function initialize_vb_storage_mapping() {
		global $storage_server_pool;
		$flag = True;
		remote_function::remote_file_copy(DISK_MAPPER_SERVER_ACTIVE , HOME_DIRECTORY."common/misc_files/1.9_files/initialize_diskmapper.sh", "/tmp/initialize_diskmapper.sh", False, True, True);
		$disk_mapper_ip = general_function::get_ip_address(DISK_MAPPER_SERVER_ACTIVE, False);
		$vb_per_disk = ceil((float)(NO_OF_VBUCKETS / NO_OF_STORAGE_DISKS));
		$init_output = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE, "sh /tmp/initialize_diskmapper.sh -i ".$disk_mapper_ip." -g ".GAME_ID." -v ".$vb_per_disk." -t ".NO_OF_VBUCKETS);
		$count_success = substr_count($init_output, "Saved file to disk");
		if($count_success != NO_OF_VBUCKETS) {
			return False;
		}
		else {
			for($vb_group_id = 0; $vb_group_id < NO_OF_STORAGE_DISKS; $vb_group_id++) {
				if(!torrent_functions::wait_for_torrent_copy("vb_group_".$vb_group_id, 300))
					$flag = False;
			}
			return $flag;
		}
	}


}
?>
