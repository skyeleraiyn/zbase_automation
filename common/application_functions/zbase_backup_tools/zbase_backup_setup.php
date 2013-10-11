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

class zbase_backup_setup{

	public function install_backup_tools_rpm($remote_machine_name){
		global $backup_tools_build;

		if($backup_tools_build <> "" || !SKIP_BUILD_INSTALLATION){
			rpm_function::uninstall_rpm($remote_machine_name, BACKUP_TOOLS_PACKAGE_NAME);
			rpm_function::install_jemalloc_rpm($remote_machine_name);
			rpm_function::yum_install(BUILD_FOLDER_PATH.$backup_tools_build, $remote_machine_name);
			self::configure_incremental_backup_feature($remote_machine_name);
		} 
		// verify backup tools is installed
		if(stristr(installation::get_installed_backup_tools_version($remote_machine_name), "not installed")){
			log_function::exit_log_message("backup tools rpm is not installed on $remote_machine_name");
		}		
	}
	
	private function configure_incremental_backup_feature($remote_machine_name){
		file_function::modify_value_ini_file(DEFAULT_INI_FILE, array("game_id", "cloud", "interval"), array(GAME_ID, ZBASE_CLOUD, 30), $remote_machine_name);
		$installed_zbase_version = installation::get_installed_zbase_version($remote_machine_name);
			// create tmpfs only in the box where zbase is installed
		if(!stristr(installation::get_installed_zbase_version($remote_machine_name), "not installed")){
			self::create_tmpfs($remote_machine_name);
		}	
	}

	private function create_tmpfs($remote_machine_name){
		directory_function::create_directory("/db_backup", $remote_machine_name, True);
		if(!stristr(general_function::execute_command("mount", $remote_machine_name), "db_backup")){
			general_function::execute_command("sudo mount -t tmpfs -o size=3584M none /db_backup", $remote_machine_name);
		}
		if(!stristr(general_function::execute_command("cat /etc/fstab", $remote_machine_name), "db_backup")){
			general_function::execute_command("sudo sh -c 'echo \"none /db_backup tmpfs size=3584M 0 0\" >> /etc/fstab'", $remote_machine_name);
		}
	}

	public function start_backup_daemon($remote_machine_name) {
		return service_function::control_service($remote_machine_name, ZBASE_BACKUP_SERVICE, "start");
	}

	public function start_backup_daemon_full($remote_machine_name) {
		$flag = service_function::control_service($remote_machine_name, ZBASE_BACKUP_SERVICE, "start-with-fullbackup");
		sleep(10);
		return $flag;
	}

	public function stop_backup_daemon($remote_machine_name) {
		return service_function::control_service($remote_machine_name, ZBASE_BACKUP_SERVICE, "stop");
	}

	public function restart_backup_daemon($remote_machine_name) {
		return service_function::control_service($remote_machine_name, ZBASE_BACKUP_SERVICE, "restart");
	}
		
	public function clear_zbase_backup_log_file($remote_machine){
		file_function::clear_log_files($remote_machine, ZBASE_BACKUP_LOG_FILE);
	}

}
?>
