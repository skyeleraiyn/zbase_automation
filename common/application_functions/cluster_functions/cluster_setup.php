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
class cluster_setup	{

	public function setup_zbase_cluster($vbuckets = NO_OF_VBUCKETS, $restart_spares = False)	{
		global $moxi_machines;
		vbs_setup::vbs_start_stop("stop");
		moxi_setup::moxi_start_stop_all("stop");
		vba_setup::vba_cluster_start_stop("stop", $restart_spares);
		zbase_setup::clear_cluster_zbase_database($restart_spares);
        zbase_setup::restart_zbase_cluster($restart_spares);
		vba_setup::vba_cluster_start_stop("start", $restart_spares);
		vbs_setup::populate_and_copy_config_file($vbuckets);
		moxi_setup::populate_and_copy_config_file();
		moxi_setup::moxi_start_stop_all("start");
		moxi_setup::copy_pump_script();
		vbs_setup::vbs_start_stop("start");
	}

	public function setup_zbase_cluster_with_ibr($setup_zbase_cluster = True, $wait_for_torrents = False, $restart_spares = False) {
        global $storage_server_pool;
		$pid_count = 0;
		$pid = pcntl_fork();
		if($pid == 0) {
			zbase_backup_setup::stop_cluster_backup_daemon();
            zbase_backup_setup::clear_cluster_backup_log_file();
			diskmapper_setup::reset_diskmapper_storage_servers($storage_server_pool, $wait_for_torrents);
			if(!self::initialize_vb_storage_mapping($wait_for_torrents)) {
				log_function::debug_log("couldn't initialize mapping");
				exit(1);
			}
			else {
				exit(0);
			}
		}
		else {
			if($setup_zbase_cluster) {
				self::setup_zbase_cluster(NO_OF_VBUCKETS, $restart_spares);
				sleep(30);
			}
			else {
				log_function::debug_log("not resetting zbase cluster");
			}
		}
		pcntl_waitpid($pid, $status);
		if(pcntl_wexitstatus($status) == 1)  return False;
		return True;
	}

	public function initialize_vb_storage_mapping($wait_for_torrents = False) {
		global $storage_server_pool;
		$flag = True;
		remote_function::remote_file_copy(DISK_MAPPER_SERVER_ACTIVE , HOME_DIRECTORY."common/misc_files/1.9_files/initialize_diskmapper.sh", "/tmp/initialize_diskmapper.sh", False, True, True);
		$disk_mapper_ip = general_function::get_ip_address(DISK_MAPPER_SERVER_ACTIVE, False);
		$vb_per_disk = ceil((float)(NO_OF_VBUCKETS / NO_OF_STORAGE_DISKS));
        if($wait_for_torrents) {
		$init_output = remote_function::remote_execution(DISK_MAPPER_SERVER_ACTIVE, "sh /tmp/initialize_diskmapper.sh -i ".$disk_mapper_ip." -g ".GAME_ID." -v ".$vb_per_disk." -t ".NO_OF_VBUCKETS);
		$count_success = substr_count($init_output, "Saved file to disk");
		if($count_success != NO_OF_VBUCKETS) {
			return False;
		}
        for($vb_group_id = 0; $vb_group_id < NO_OF_STORAGE_DISKS; $vb_group_id++) {
            if(!torrent_functions::wait_for_torrent_copy("vb_group_".$vb_group_id, 300))
                $flag = False;
        }
        return $flag;
        }
        else
            return True;
	}


}
?>
