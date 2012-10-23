<?php
class mb_backup_commands {

	public function run_master_merge() {
		$command_to_be_executed = "sudo python26 ".MASTER_MERGE_FILE_PATH;
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function run_daily_merge() {
		$command_to_be_executed = "sudo python26 ".DAILY_MERGE_FILE_PATH;
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}
	
	public function run_core_merge_script($validate=True, $split_size=NULL) {
		$command_to_be_executed = "sudo ".MERGE_INCREMENTAL_FILE_PATH." -i ".MERGE_INCREMENTAL_INPUT_FILE." -o ".TEMP_OUTPUT_FILE_PATTERN;
		if($validate == True){
			$command_to_be_executed = $command_to_be_executed." -v";
		}
		if($split_size){
			$command_to_be_executed = $command_to_be_executed." -s $split_size";
		}	
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}
	
	public function edit_date_folder($days=-1, $type="daily") {
		$path = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/".date("Y-m-d");
		$date = mktime(0,0,0,date("m"),date("d")+$days,date("Y"));
		$mod_date = date("Y-m-d", $date);
		$new_path = "/var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/".$mod_date;
		$command_to_be_executed = "sudo mv $path $new_path";
		remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
		sleep(1);
		return $new_path;
	}

	public function create_lock_file() {
		$command_to_be_executed = "sudo touch /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/.lock-".TEST_HOST_2;
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function delete_lock_file() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/.lock-".TEST_HOST_2;
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function get_merged_files($type) {
		$return_array = array();
		if($type == "incremental"){ 
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/*.mbb"; 
		} else {
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/$type/*/*.mbb";
		}
		$return_list = trim(remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed));
		$return_array = explode("\n", $return_list);
		return($return_array);

	}
	
	public function delete_input_file_entry($line_no){
		$command_to_be_executed = "sudo sed -i ".$line_no."d ".MERGE_INCREMENTAL_INPUT_FILE;
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function delete_split_file_entry($line_no) {
		$array = self::list_incremental_backups("*.split");
		$command_to_be_executed = "sudo sed -i '".$line_no."d' $array[0]";
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function set_input_file_merge($file_list_array, $mode = 'w') {
		if(is_array($file_list_array )){
			foreach($file_list_array as $file_list){
				remote_function::remote_execution(STORAGE_SERVER, "echo ".trim($file_list)." >>".MERGE_INCREMENTAL_INPUT_FILE);
			}
		} else {
			remote_function::remote_execution(STORAGE_SERVER, "echo ".trim($file_list_array)." >>".MERGE_INCREMENTAL_INPUT_FILE);
		}
		return 1;
	}

	public function get_backup_time_from_log($parameter, $value = "1") {
		$command_to_be_executed = "cat ".MEMBASE_BACKUP_LOG_FILE." | grep -m$value \"$parameter\" | sed -n '$value"."p' | cut -d' ' -f3";
		$time = remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed); 
		$time = trim($time);
		$array = split('[:.-]', $time);
		return $array;
	}

	public function edit_defaultini_file($parameter, $value) {
		global $modified_file_list;
		
		$modified_file_list[] = DEFAULT_INI_FILE;	
		$command_to_be_executed = "sudo sed -i 's/^$parameter.*/$parameter = $value/' ".DEFAULT_INI_FILE;
		return remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed);
	}

	public function set_last_closed_chkpoint_file($new_value){
		$command_to_be_executed = "sudo chmod 777 ".LAST_CLOSED_CHECKPOINT_FILE_PATH."; echo $new_value > ".LAST_CLOSED_CHECKPOINT_FILE_PATH.";sudo chmod 644 ".LAST_CLOSED_CHECKPOINT_FILE_PATH;
		return remote_function::remote_execution(TEST_HOST_2 ,$command_to_be_executed);				
	}

	public function upload_stat_from_membasebackup_log($check, $count = "1") {
		if($check == "SUCCESS"){	
			$command_to_be_executed = "tac ".MEMBASE_BACKUP_LOG_FILE." | grep '$check: Uploading' | grep -m$count '.mbb'";
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
				$element = "/var/www/html/membase_backup/".$element;
			}
			sort($array);
			return $array;
		} else {
			$command_to_be_executed = "tac ".MEMBASE_BACKUP_LOG_FILE." | grep -m$count '$check' ";
			$status = trim(remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed));
			return $status;
		}	
	}

	public function delete_done_daily_merge() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/done*";
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function verify_membase_backup_success(){
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
		
			$folder_size = trim(remote_function::remote_execution(TEST_HOST_2, "du -sh ".MEMBASE_DB_BACKUP_FOLDER));
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

	public function delete_daily_backups() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/";
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}		

	public function list_daily_backups($filetype = ".mbb", $date = NULL) {
		if($date <> NULL){
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/".$date."/ -name \*".$filetype;
		} else {	
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/daily/ -name \*".$filetype;
		}	
		$string = trim(remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed));
		$array = preg_split("/(\n)/", $string);
		sort($array);
		return $array;
	}

	public function delete_incremental_backups($filetype="") {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/$filetype";
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function list_master_backups($filetype = ".mbb", $date = NULL) {
		if($date <> NULL){
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/".$date."/ -name \*".$filetype;
		} else {
			$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/master/ -name \*".$filetype;
		}
		$string = trim(remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed));
		$array = preg_split("/(\n)/", $string);
		sort($array);
		return $array;
	}

	public function list_incremental_backups($filetype = ".mbb") {
		$command_to_be_executed = "find /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/incremental/ -name \*".$filetype; 
		$string = remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
		$string = trim($string);
		$array = array();
		$array = preg_split("/(\n)/", $string);
		sort($array);
		return $array;
	}

	public function copy_master_backups() {
		$command_to_be_executed= "mkdir -p /tmp/copy_of_backups/master_backups; sudo rm -rf /tmp/copy_of_backups/master_backups/*;";
		remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
		$backups_array=self::list_master_backups();
		$split_array=self::list_master_backups(".split");
		foreach($backups_array as $backup) {
			$command_to_be_executed = "sudo cp $backup /tmp/copy_of_backups/master_backups/";
			remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
		}
		foreach($split_array as $split) {
			$command_to_be_executed = "sudo cp $split /tmp/copy_of_backups/master_backups/";
			remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
		}
	}

	public function clear_storage_server() {
		$command_to_be_executed = "sudo rm -rf /var/www/html/membase_backup/".GAME_ID."/".TEST_HOST_2."/".MEMBASE_CLOUD."/";
		membase_function::clear_membase_backup_log_file(STORAGE_SERVER);
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

	public function last_closed_checkpoint_file($remote_machine_name) {
		$command_to_be_executed = "cat ".LAST_CLOSED_CHECKPOINT_FILE_PATH." ; echo";
		return trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
	}

	public function run_backup_script($remote_machine_name) {
		$command_to_be_executed = "python26 ".BACKUP_SCRIPT_PATH;
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function start_backup_daemon($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMBASE_BACKUP_SERVICE, "start");
	}

	public function start_backup_daemon_full($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMBASE_BACKUP_SERVICE, "start-with-fullbackup");
	}

	public function stop_backup_daemon($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMBASE_BACKUP_SERVICE, "stop");
	}

	public function restart_backup_daemon($remote_machine_name) {
		return service_function::control_service($remote_machine_name, MEMBASE_BACKUP_SERVICE, "restart");
	}

	public function clear_backup_data($remote_machine_name) {
		$command_to_be_executed = "sudo rm -rf ".LAST_CLOSED_CHECKPOINT_FILE_PATH."; sudo rm -rf ".MEMBASE_DB_BACKUP_FOLDER."*";
		membase_function::clear_membase_backup_log_file($remote_machine_name);
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function clear_temp_backup_data($remote_machine_name){
		$command_to_be_executed = "sudo rm -rf ".TEMP_BACKUP_FOLDER." ; sudo rm -rf ".MERGE_INCREMENTAL_INPUT_FILE." ; mkdir ".TEMP_BACKUP_FOLDER;
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);	
	}
	
	public function set_backup_type($remote_machine_name, $backup_type) {
		global $modified_file_list;
		
		$modified_file_list[] = TEST_SPLITLIB_FILE_PATH;
		if($backup_type == "full"){
			$command_to_be_executed = "sudo sed -i 's/backup_type\ = \"incr\"/backup_type\ = \"$backup_type\"/g' ".TEST_SPLITLIB_FILE_PATH;
		} else {
			$command_to_be_executed = "sudo sed -i 's/backup_type\ = \"full\"/backup_type\ = \"$backup_type\"/g' ".TEST_SPLITLIB_FILE_PATH;
		}
        general_function::execute_command($command_to_be_executed, $remote_machine_name);	
	}

	public function set_backup_const($remote_machine_name, $field, $value) {
		global $modified_file_list;
		$modified_file_list[] = MEMBASE_BACKUP_CONSTANTS_FILE;
		$command_to_be_executed = "sudo sed -i 's/^$field.*/$field = $value/' ".MEMBASE_BACKUP_CONSTANTS_FILE;
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

	public function get_backup_size($remote_machine_name, $file_name) {
		$command_to_be_executed = "stat -c %s $file_name";
		return trim(remote_function::remote_execution_popen($remote_machine_name, $command_to_be_executed));
	}
}
?>
