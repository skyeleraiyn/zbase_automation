<?php

class sqlite_functions {


	public function sqlite_schema($remote_machine_name, $file)	{
		$command_to_be_executed = "echo \".schema\" | sqlite3 ".$file;
		return trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
	}


	public function sqlite_select($remote_machine_name, $field, $table_name, $file, $parameters = "") {
		log_function::debug_log($remote_machine_name." ".$field." ".$table_name." ".$file." ".$parameters);
		
		$command_to_be_executed = "echo \"select ".$field." from ".$table_name.";\" | sudo sqlite3 ".$file;
		if($parameters != "") {
			$command_to_be_executed = $command_to_be_executed.$parameters;
		}
		$output = explode("\n", trim(remote_function::remote_execution_popen($remote_machine_name, $command_to_be_executed)));
		if(count($output) > 1)
			return trim($output[1]);
		else
			return trim($output[0]);	
	}

	public function sqlite_update($remote_machine_name, $field, $table_name, $file, $new_value="new_value", $parameters = "") {
		$command_to_be_executed = "echo \"update ".$table_name." set ".$field."='".$new_value."';\" | sudo sqlite3 ".$file;
		if($parameters != "") {
			$command_to_be_executed = $command_to_be_executed.$parameters;
		}
		return trim(remote_function::remote_execution($remote_machine_name, $command_to_be_executed));
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

	public function sqlite_count($remote_machine_name, $file) { 
		return self::sqlite_select($remote_machine_name, "count(*)", "cpoint_op", $file);
	}

	public function sqlite_chkpoint_count($remote_machine_name, $file){
		return self::sqlite_select($remote_machine_name, "count(*)", "cpoint_state", $file);
	}

	public function db_sqlite_select($remote_machine_name, $field, $table_name)	{
		$db_checksum_array = array();
		$temp_array = array();
		foreach(unserialize(MEMBASE_DATABASE_PATH) as $membase_dbpath){
			for ($i=0; $i<4; $i++)	{
				$db_checksum_array = array_merge($temp_array, explode("\n", self::sqlite_select($remote_machine_name, $field, $table_name, $membase_dbpath."/ep.db-$i.sqlite")));
				$temp_array = $db_checksum_array;
			}
		}
		sort($db_checksum_array);
		return($db_checksum_array);
	}
	
	public function corrupt_sqlite_file($file_path) {
		$command_to_be_executed = "sudo sed -i 's/\(.\{30\}\)/Corrupting/' $file_path";
		return remote_function::remote_execution(STORAGE_SERVER, $command_to_be_executed);
	}

}
?>
