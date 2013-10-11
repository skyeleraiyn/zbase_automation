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
class file_function{

	public function get_md5sum($remote_machine, $file_path){
		if(is_array($file_path)){
			$file_list = "";
			foreach($file_path as $file){
				$file_list = $file_list." ".$file;
			}
			$md5_value = trim(remote_function::remote_execution($remote_machine, "md5sum ".$file_list." | awk '{print $1}'"));
			$md5_value = explode("\n", $md5_value);
            $md5_list = array_filter(array_map('trim', $md5_value));
			sort($md5_list);
			return $md5_list;
		} else {
			return trim(remote_function::remote_execution($remote_machine, "md5sum $file_path | awk '{print $1}'"));
		}
	}

	public function create_dummy_file($remote_machine, $file_path, $file_size = 1024, $sudo_permission = False, $file_contents = "urandom"){
		if($sudo_permission){
			return remote_function::remote_execution($remote_machine, "sudo dd if=/dev/$file_contents of=$file_path count=1 bs=$file_size");
		} else {
			return remote_function::remote_execution($remote_machine, "dd if=/dev/$file_contents of=$file_path count=1 bs=$file_size");
		}
	}

	public function check_file_exists($remote_machine, $file_path){
		$output = remote_function::remote_execution($remote_machine, "sudo ls ".$file_path);
		if(stristr($output, "cannot access") || stristr($output, "No such file")){
			return False;
		} else {
			return True;
		}
	}

	public function clear_log_files($remote_machine, $file_path_to_be_cleared){
		if(is_array($file_path_to_be_cleared)){
			$temp_path = "";
			foreach($file_path_to_be_cleared as $file_name){
				$temp_path = $file_name." ".$temp_path;
			}
			$file_path_to_be_cleared = 	$temp_path;
		}
		remote_function::remote_execution($remote_machine, "cat /dev/null | sudo tee ".$file_path_to_be_cleared);
		if(general_function::get_CentOS_version($remote_machine) == 5){
			service_function::control_service($remote_machine, SYSLOG_NG_SERVICE, "restart");
		} else {
			service_function::control_service($remote_machine, RSYSLOG, "restart");
		}
	}

	public function query_log_files($file_to_query, $query_name = NULL, $remote_machine_name = NULL){
		if($query_name <> NULL){
			$search_log_files = general_function::execute_command("sudo cat $file_to_query | grep -i $query_name", $remote_machine_name);	
		} else {
			$search_log_files = general_function::execute_command("sudo cat $file_to_query", $remote_machine_name);	
		}
		if($search_log_files == "" or $search_log_files  == NULL){
			return 0;
		} else {
			return $search_log_files ;
		}
	}

	public function modify_value_ini_file($ini_file_path, $search_key, $replace_value, $remote_machine_name = NULL){
		$command_to_be_executed = "";
		if(is_array($search_key)){
			foreach(array_combine($search_key, $replace_value) as $search_key_value => $replace_key_value){
				$command_to_be_executed = "sudo sed -i 's/^$search_key_value =.*$/$search_key_value = $replace_key_value/g' $ini_file_path";
				general_function::execute_command($command_to_be_executed, $remote_machine_name);
			}
		} else {
			$command_to_be_executed = "sudo sed -i 's/^$search_key = .*$/$search_key = $replace_value/g' $ini_file_path";
			general_function::execute_command($command_to_be_executed, $remote_machine_name);
		}
	}	
	// Should remove modify_value_ini_file and use a common function
	public function edit_config_file($remote_server , $file , $parameter , $value , $operation = 'replace'){
		//specify $parameter and $value as complete string
		//$operation can be delete , append, replace, modify

		switch($operation){
		case "delete":
			$command_to_be_executed = "sudo sed -i '/$parameter/d' $file";
			break;
		case "append":
			$command_to_be_executed = "sudo sed -i '/$parameter/a$value' $file";
			break;
		case "modify":
			$command_to_be_executed = "sudo sed -i 's/^$parameter/$parameter$value/g' $file";
			break;
		default:
			$command_to_be_executed = "sudo sed -i 's/^$parameter/$value/g' $file";
		}
		return remote_function::remote_execution($remote_server, $command_to_be_executed);		

	}

	public function modify_config_value($remote_server, $file, $parameter, $value)	{
		$command_to_be_executed = "sudo sed -i 's/^$parameter =.*$/$parameter = $value/g' $file";
	}

	public function write_to_file($file_name, $message_to_log, $write_mode){
		// skip logging if the directory is not created
		if(file_exists(dirname($file_name))){
			$filePointer = fopen($file_name, $write_mode);
			fputs($filePointer,$message_to_log."\r\n");
			fclose($filePointer);	
		}	
	}

	public function read_from_file($file_name){
		// skip logging if the directory is not created		
		if(file_exists(dirname($file_name))){
			$filePointer = fopen($file_name, "r");
			$file_contents = fread($filePointer, filesize($file_name));
			fclose($filePointer);	
			return $file_contents;
		}  else {
			return False;
		}
	}

	public function add_modified_file_to_list($remote_server, $file_name){
		global $modified_file_list;

		self::keep_copy_original_file($remote_server, $file_name, "n");	// keep a copy as .org if it doesn't exists
		$file_name = $remote_server."::".$file_name;
		if(is_array($modified_file_list)){
			if(!in_array($file_name, $modified_file_list)) $modified_file_list[] = $file_name;
		} else {
			$modified_file_list[] = $file_name;
		}
	}

	public function file_attributes($remote_machine, $file_path, $property){
		switch($property){
		case "access_time":
			return trim(general_function::execute_command("stat -c %X $file_path", $remote_machine));
		case "modified_time":
			return trim(general_function::execute_command("stat -c %Y $file_path", $remote_machine));
		case "change_time":
			return trim(general_function::execute_command("stat -c %Z $file_path", $remote_machine));
		case "ownership_user":
			return trim(general_function::execute_command("stat -c %U $file_path", $remote_machine));
		case "ownership_group":
			return trim(general_function::execute_command("stat -c %G $file_path", $remote_machine));
		default:
			return trim(general_function::execute_command("stat $file_path", $remote_machine));
		}
	}

	public function get_file_size($remote_machine, $file_path, $human_readable = True){
		if($human_readable){
			return trim(general_function::execute_command("du -sch $file_path | grep total | awk '{print $1}'", $remote_machine));	
		} else {	
			return trim(general_function::execute_command("du -sc $file_path | grep total | awk '{print $1}'", $remote_machine));	
		}	
	}
	
	public function keep_copy_original_file($remote_machine, $list_of_files, $confirmation="y"){
		if(is_array($list_of_files)){
			$command = "";
			foreach($list_of_files as $file){
				if($confirmation == "n"){
					$command = $command."echo n | sudo cp -i ".$file." ".$file.".org ;";
				} else {
					$command = $command."sudo cp ".$file." ".$file.".org ;";
				}
			}
		} else {
			if($confirmation == "n"){
				$command = "echo n | sudo cp -i ".$list_of_files." ".$list_of_files.".org";
			} else {
				$command = "sudo cp ".$list_of_files." ".$list_of_files.".org";
			}
		}
		
		if(is_array($remote_machine)){
			foreach($remote_machine as $machine){
				remote_function::remote_execution($machine, $command);
			}
		} else {
			remote_function::remote_execution($remote_machine, $command);
		}
	}	
}	
?>
