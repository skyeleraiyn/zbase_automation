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
class backup_tools_functions{

	public function get_backup_time_from_log($parameter, $value = "1") {
		$command_to_be_executed = "cat ".ZBASE_BACKUP_LOG_FILE." | grep -m$value \"$parameter\" | sed -n '$value"."p' | cut -d' ' -f3";
		$time = remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed); 
		$time = trim($time);
		$array = split('[:.-]', $time);
		return $array;
	}

	public function verify_zbase_backup_upload(){
			// verify backup cursor has reached its end
		for($iattempt=0 ;$iattempt < 200; $iattempt++){
			$checkpoint_stats = stats_functions::get_checkpoint_stats(TEST_HOST_2);
			if($checkpoint_stats["open_checkpoint_id"] == $checkpoint_stats["cursor_checkpoint_id"]["eq_tapq"]["backup"]){
				break;
			} else {
				if($iattempt == 199){
					log_function::debug_log("backup cursor ".$checkpoint_stats["cursor_checkpoint_id"]["eq_tapq"]["backup"]." is not equal to open checkpoint id ".$checkpoint_stats["open_checkpoint_id"]);
					return False;
				}	
				log_function::debug_log("backup cursor ".$checkpoint_stats["cursor_checkpoint_id"]["eq_tapq"]["backup"]." open checkpoint id ".$checkpoint_stats["open_checkpoint_id"]);
				sleep(2);
			}
		}
			// verify all the files are uploaded
		for($iattempt=0 ;$iattempt < 120; $iattempt++){
		
			$folder_size = trim(remote_function::remote_execution(TEST_HOST_2, "du -sh ".ZBASE_DB_BACKUP_FOLDER));
			$folder_size = trim(str_replace("/db_backup/", "", $folder_size));
			if($folder_size == 0 ){
				return True;
			} else {
				sleep(5);
			}	
		}
		log_function::debug_log("/db/backup folder size is not 0: ".$folder_size);
		return False;
	}

	public function set_backup_const($remote_machine_name, $field, $value, $add_modified_file_to_list = True) {
		if($add_modified_file_to_list){
			file_function::add_modified_file_to_list($remote_machine_name, ZBASE_BACKUP_CONSTANTS_FILE);
		}
		if(is_numeric($value)){
			$command_to_be_executed = "sudo sed -i 's/^$field.*/$field = $value/' ".ZBASE_BACKUP_CONSTANTS_FILE;	
		} else {
			$command_to_be_executed = "sudo sed -i 's/^$field.*/$field = \"$value\"/' ".ZBASE_BACKUP_CONSTANTS_FILE;	
		}	
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function run_backup_script($remote_machine_name) {
		$command_to_be_executed = "python26 ".TEST_SPLITLIB_FILE_PATH;
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function clear_backup_data($remote_machine_name) {
		$command_to_be_executed = "sudo rm -rf ".LAST_CLOSED_CHECKPOINT_FILE_PATH."; sudo rm -rf ".ZBASE_DB_BACKUP_FOLDER."*";
		if(defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> "") {
			enhanced_backup_functions::clear_local_backups($remote_machine_name);
			enhanced_backup_functions::clear_local_host_config_file($remote_machine_name);
		}
		zbase_backup_setup::clear_zbase_backup_log_file($remote_machine_name);
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function clear_temp_backup_data($remote_machine_name){
		$command_to_be_executed = "sudo rm -rf ".TEMP_BACKUP_FOLDER." ; sudo rm -rf ".MERGE_INCREMENTAL_INPUT_FILE." ; mkdir ".TEMP_BACKUP_FOLDER;
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);	
	}
	
	public function set_backup_type($remote_machine_name, $backup_type) {

		file_function::add_modified_file_to_list(TEST_HOST_2, TEST_SPLITLIB_FILE_PATH);
		if($backup_type == "full"){
			$command_to_be_executed = "sudo sed -i 's/backup_type\ = \"incr\"/backup_type\ = \"$backup_type\"/g' ".TEST_SPLITLIB_FILE_PATH;
		} else {
			$command_to_be_executed = "sudo sed -i 's/backup_type\ = \"full\"/backup_type\ = \"$backup_type\"/g' ".TEST_SPLITLIB_FILE_PATH;
		}
        general_function::execute_command($command_to_be_executed, $remote_machine_name);	
	}

	public function edit_defaultini_file($parameter, $value) {

		file_function::add_modified_file_to_list(TEST_HOST_2, DEFAULT_INI_FILE);		
		$command_to_be_executed = "sudo sed -i 's/^$parameter.*/$parameter = $value/' ".DEFAULT_INI_FILE;
		return remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed);
	}

	public function set_last_closed_chkpoint_file($new_value){
		$command_to_be_executed = "sudo chmod 777 ".LAST_CLOSED_CHECKPOINT_FILE_PATH."; echo $new_value > ".LAST_CLOSED_CHECKPOINT_FILE_PATH.";sudo chmod 644 ".LAST_CLOSED_CHECKPOINT_FILE_PATH;
		return remote_function::remote_execution(TEST_HOST_2 ,$command_to_be_executed);				
	}

	public function last_closed_checkpoint_file($remote_machine_name) {
		$command_to_be_executed = "cat ".LAST_CLOSED_CHECKPOINT_FILE_PATH." ; echo";
		return trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
	}

	public function upload_stat_from_zbasebackup_log($check, $count = "1") {
		if($check == "SUCCESS"){	
			$command_to_be_executed = "tac ".ZBASE_BACKUP_LOG_FILE." | grep '$check: Uploading' | grep -m$count '.mbb'";
			$status = trim(remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed));
			$temp_status = explode(" ", $status);
			foreach($temp_status as $check_output){
				if(stristr($check_output, "s3:")){
					$status = str_replace("s3://", "", $check_output);
					break;
				}
			}
			$array = array();
			$array = preg_split("/(\n)/", $status);
			foreach($array as &$element){
				$element = "/var/www/html/zbase_backup/".$element;
			}
			sort($array);
			return $array;
		} else {
			$command_to_be_executed = "tac ".ZBASE_BACKUP_LOG_FILE." | grep -m$count '$check' ";
			$status = trim(remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed));
			return $status;
		}	
	}

	public function get_backup_size($remote_machine_name, $file_name) {
		$command_to_be_executed = "stat -c %s $file_name";
		return trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
	}
	
	
}
?>
