<?php

class rpm_function{

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
	
		if(is_array($remote_machine_name)){
			foreach($remote_machine_name as $remote_machine){
				if(!self::install_python26($remote_machine)){
					return False;
				}
			}
			return True;
		}
	
		$output = general_function::execute_command("ls /usr/bin/python2.6", $remote_machine_name);
		if(stristr($output, "No such file")){
			self::yum_install("python26", $remote_machine_name, "zynga");
			$output = general_function::execute_command("ls /usr/bin/python2.6", $remote_machine_name);
			if(stristr($output, "No such file")){
				return False;
			} 
		}
		$output = general_function::execute_command("ls /usr/bin/python26", $remote_machine_name);
		if(stristr($output, "No such file")){
			general_function::execute_command("sudo ln -s /usr/bin/python2.6 /usr/bin/python26", $remote_machine_name);
		}
		return True;	
	}

	public function install_python_simplejson($remote_machine_name) {
                if(is_array($remote_machine_name)){
                        foreach($remote_machine_name as $remote_machine){
                                if(!self::install_python_simplejson($remote_machine)){
                                        return False;
                                }
                        }
                        return True;
                }
		$command_to_be_executed = "python26 -c 'import simplejson'";
		$output = general_function::execute_command($command_to_be_executed, $remote_machine_name);
		if(!stristr($output, "No module named simplejson")) {
			self::install_python26($remote_machine_name);
			self::yum_install("python26-setuptools", $remote_machine, "zynga");
			$command_to_be_executed = "wget http://pypi.python.org/packages/source/s/simplejson/simplejson-2.0.9.tar.gz#md5=af5e67a39ca3408563411d357e6d5e47;tar xzvf simplejson-2.0.9.tar.gz;cd simplejson-2.0.9 && sudo python26 setup.py install;";
			$output = general_function::execute_command($command_to_be_executed, $remote_machine_name);
			$command_to_be_executed = "python26 -c 'import simplejson'";
			if(!stristr($output, "No module named simplejson"))
				return False;
		}
		return True;

	}




	public function install_valgrind($remote_machine_name){
		return self::verify_and_install_rpm_from_repo($remote_machine_name, "valgrind");	
	}	

	public function yum_install($packagename, $remote_machine_name = NULL, $reponame = NULL){
		$command_to_be_executed = "sudo yum install -y --quiet --nogpgcheck ".$packagename;
		if($reponame <> NULL){
			$command_to_be_executed = $command_to_be_executed." --enablerepo=".$reponame;
		}
		log_function::debug_log(general_function::execute_command($command_to_be_executed, $remote_machine_name));
	}
	
	private function verify_and_install_rpm_from_repo($remote_machine_name, $packagename, $reponame = NULL){
	
		if(is_array($remote_machine_name)){
			$array_result = True;
			foreach($remote_machine_name as $machine_name){
				$array_result = $array_result && self::verify_and_install_rpm_from_repo($machine_name, $packagename, $reponame);
			}
			return $array_result;
		}	
		$check_rpm_output = self::get_rpm_version($remote_machine_name, $packagename);
		if(stristr($check_rpm_output, "not installed")){
			self::yum_install($packagename, $remote_machine_name, $reponame);
		} else	{
			return True;		
		}	
	}

	public function install_jemalloc_rpm($remote_machine_name){
			// check if jemalloc is installed
		if(stristr(self::get_rpm_version($remote_machine_name, JEMALLOC), "not installed")){
			if(stristr(general_function::execute_command("cat /etc/redhat-release", $remote_machine_name), "5.")){
				installation::install_rpm_from_S3("JEMALLOC5", $remote_machine_name);
			} else{
				installation::install_rpm_from_S3("JEMALLOC6", $remote_machine_name);	// Support CentOS 6.x
			}	
		}	
	}

}

?>
