<?php

class general_rpm_function{

	public function install_rpm($remote_machine_name, $rpm_path){
		return general_function::execute_command("sudo rpm -ivh $rpm_path", $remote_machine_name);
	}
	
	public function uninstall_rpm($remote_machine_name, $uninstall_packagename){
		return general_function::execute_command("sudo rpm -e $uninstall_packagename", $remote_machine_name);
	}
	
	public function get_rpm_version($remote_machine_name, $packagename_to_be_verified){
		return general_function::execute_command("rpm -q $packagename_to_be_verified", $remote_machine_name);
	}
	
	public function install_python26($remote_machine_name){
		return self::verify_and_install_rpm($remote_machine_name, "python26", "zynga");	
	}

	public function install_valgrind($remote_machine_name){
		return self::verify_and_install_rpm($remote_machine_name, "valgrind");	
	}	
	
	private function verify_and_install_rpm($remote_machine_name, $packagename, $reponame = NULL){
	
		if(is_array($remote_machine_name)){
			foreach($remote_machine_name as $machine_name){
				self::verify_and_install_rpm($machine_name, $packagename, $reponame);
			}
			return True;
		}	
		$check_rpm_output = self::get_rpm_version($remote_machine_name, $packagename, $reponame = NULL);
		if(stristr($check_rpm_output, "not installed")){
			$command_to_be_executed = "sudo yum install -y -q ".$packagename;
			if($reponame){
				$command_to_be_executed = $command_to_be_executed." --enablerepo=".$reponame;
			}	
			log_function::debug_log(general_function::execute_command($command_to_be_executed, $remote_machine_name));
		} else	{
			return $check_rpm_output;		
		}	
	}


}

?>