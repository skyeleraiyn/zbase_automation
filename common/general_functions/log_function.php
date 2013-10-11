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
class log_function{
	public function result_log($message_to_log){

		global $result_file;
		echo $message_to_log."\n";
		self::log_to_file($result_file, $message_to_log);
		self::debug_log($message_to_log);
	}

	public function debug_log($message_to_log) {
		
		$debug_file = RESULT_FOLDER."/debug_file.log";
		if(is_array($message_to_log)) 
			$message_to_log = serialize($message_to_log);
		self::log_to_file($debug_file, $message_to_log);

	}

	public function exit_log_message($message_to_log){
		self::error_log_message($message_to_log);
		exit;
	}
	
	public function error_log_message($message_to_log){
		return self::result_log("Error: ".$message_to_log);
	}	
	public function log_to_file($file_name, $message_to_log){
		$time_stamp = date("d-m-Y H:i:s");
		$message_to_log = $time_stamp."  ".$message_to_log;
		return file_function::write_to_file($file_name, $message_to_log, "a");
	}

		// These are needed to define constants for phpunit framework
	public function write_to_temp_config($message_to_log){
		return file_function::write_to_file(TEMP_CONFIG, $message_to_log, "a");
	}
	
	public function read_from_temp_config(){
		return explode("\n", trim(file_function::read_from_file(TEMP_CONFIG)));	
	}
}	
?>
