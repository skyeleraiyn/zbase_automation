<?php

class generate_ssh_key{

	public function get_password(){

		if(GENERATE_SSH_KEYS){
			echo "Password: ";
			system('stty -echo');
			define('TEST_PASSWORD', trim(fgets(STDIN)));	
			system('stty echo');
			echo "\n";
		} else {
			define('TEST_PASSWORD', "");	
		}
	}
	
	public function generate_ssh_keys(){
		$ssh_key_path = "/home/".TEST_USERNAME."/.ssh/id_dsa";
		if(file_exists($ssh_key_path)){
			log_function::debug_log("Private key exists. Skipping key generation");
		} else {
			if(!stristr(self::ssh_keygen($ssh_key_path), "Your public key has been saved")){
				log_function::exit_log_message("Error generating public/private keys");	
			}
		}	
	}

	public function ssh_keygen($ssh_key_path){
		$keygen_output = shell_exec('ssh-keygen -t dsa -N "" -f '.$ssh_key_path);
		log_function::debug_log($keygen_output);
		return $keygen_output;
	}

	public function copy_public_key_to_remote_machines($remote_machine_list){
			// generate key if it's not created
		self::generate_ssh_keys();
			//extract the userid
		$public_key_contents = shell_exec("cat ~/.ssh/id_dsa.pub");	
		$ssh_key_in_public_key = explode(" ", $public_key_contents);
		$ssh_key_in_public_key = $ssh_key_in_public_key[1];
			// check if key is already added
				// localhost
		$auth_key_output = shell_exec("cat ~/.ssh/authorized_keys 2>&1");
		if(!stristr($auth_key_output, trim($ssh_key_in_public_key))){
			shell_exec("echo ".trim($public_key_contents)." >> ~/.ssh/authorized_keys");
			shell_exec("chmod 600 ~/.ssh/authorized_keys");		
		}
			
		if(is_array($remote_machine_list)){
			foreach($remote_machine_list as $remote_machine){	
				$auth_key_output = trim(remote_function::remote_execution_popen($remote_machine, "cat ~/.ssh/authorized_keys"));
				if(!stristr($auth_key_output, trim($ssh_key_in_public_key))){
					remote_function::remote_execution_popen($remote_machine, "echo ".trim($public_key_contents)." >> ~/.ssh/authorized_keys");
					remote_function::remote_execution_popen($remote_machine, "chmod 600 ~/.ssh/authorized_keys");
				}
			}
		} else {
			$auth_key_output = trim(remote_function::remote_execution_popen($remote_machine_list, "cat ~/.ssh/authorized_keys"));
			if(!stristr($auth_key_output, trim($ssh_key_in_public_key))){
				remote_function::remote_execution_popen($remote_machine_list, "echo ".trim($public_key_contents)." >> ~/.ssh/authorized_keys");
				remote_function::remote_execution_popen($remote_machine_list, "chmod 600 ~/.ssh/authorized_keys");
			}
		}
		return 1;	
	}
	
	public function add_stricthost_keycheck(){
		$config_filepath = "/home/".TEST_USERNAME."/.ssh/config";
		if(file_exists($config_filepath)){
			if(trim(shell_exec("cat ~/.ssh/config | grep StrictHostKeyChecking=no")) <> "") return 0;
		}
		shell_exec("echo StrictHostKeyChecking=no >> ~/.ssh/config");		
		return 1;
	}
}
?>