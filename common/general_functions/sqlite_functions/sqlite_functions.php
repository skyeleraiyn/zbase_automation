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

class sqlite_functions {

	public function sqlite_schema($remote_machine_name, $file)	{
		$command_to_be_executed =  "echo \".schema\" | sqlite3 ".$file;
		return trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
	}

	public function sqlite_select($remote_machine_name, $field, $table_name, $file, $parameters = "") {
		log_function::debug_log($remote_machine_name." ".$field." ".$table_name." ".$file." ".$parameters);
		$command_to_be_executed = "echo \"select ".$field." from ".$table_name.";\" | sqlite3 ".$file;
		//$command_to_be_executed = 'sudo /opt/zbase/bin/sqlite3 "select "'.$field.'" from "'.$table_name.'";"'.$file;
		if($parameters != "") {
			$command_to_be_executed = $command_to_be_executed.$parameters;
		}
		$output = trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
		log_function::debug_log($output);
		return $output;
	}

	public function sqlite_update($remote_machine_name, $field, $table_name, $file, $new_value="new_value", $parameters = "") {
		log_function::debug_log($remote_machine_name." ".$field." ".$table_name." ".$file." ".$new_value." ".$parameters);
		$command_to_be_executed = "echo \"update ".$table_name." set ".$field."=\'".$new_value."\';\" | sqlite3 ".$file;
		if($parameters != "") {
			$command_to_be_executed = $command_to_be_executed.$parameters;
		}
		$output = trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
		log_function::debug_log($output);
		return $output;
	}

	public function sqlite_select_uniq($remote_machine_name, $field, $table_name, $file) {
		return self::sqlite_select($remote_machine_name, $field, $table_name, $file, " | uniq");
	}

	public function sqlite_select_cut($remote_machine_name, $arg, $table_name, $file, $delimiter =" ", $fields_array, $uniq = false) {
		$parameter = " \| cut -d'$delimiter' -f";
		foreach($fields_array as $field) {
			$parameter  = $parameter.$field.",";
		}
		$parameter = substr($parameter, 0, strlen($parameter)-1);		
		if($uniq == true) {
			$parameter  = $parameter." | uniq";
		}
		return self::sqlite_select($remote_machine_name, $arg, $table_name, $file, $parameter);
	}

	public function sqlite_select_cut_uniq($remote_machine_name, $field, $table_name, $file, $delimiter =" ", $fields_array) {
		return self::sqlite_select_cut($remote_machine_name, $field, $table_name, $file, $delimiter, $fields_array, true);
	}

	public function sqlite_count($remote_machine_name, $table_name, $file){
		return self::sqlite_select($remote_machine_name, "count(*)", $table_name, $file);
	}
	
	public function corrupt_sqlite_file($remote_machine_name, $file_path) {
		$command_to_be_executed = "sudo sed -i 's/\(.\{30\}\)/Corrupting/' $file_path";
		return remote_function::remote_execution($remote_machine_name, $command_to_be_executed);
	}

}
?>
