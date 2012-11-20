<?php

class installation{

	public function verify_php_pecl_DI_capable(){
		if(general_function::execute_command("cat /etc/php.d/memcache.ini | grep data_integrity_enabled") <> "")
			return True;
		else 
			return False;
	}
	
	public function verify_mcmux_DI_capable(){
		if(PROXY_RUNNING == False){
			log_function::debug_log("verify_mcmux_DI_capable: mcmux is not running");
			return True;
		} else {
			if(stristr(PROXY_RUNNING, "mcmux")){
				$proxy_output = trim(general_function::execute_command("sudo /etc/init.d/mcmux stats | grep chksum"));
			} else {
				$proxy_output = trim(general_function::execute_command("sudo /etc/init.d/moxi stats | grep chksum"));
			} 
			if(stristr($proxy_output,"not running")){
				return True;
			} else {
				if(stristr($proxy_output, "chksum")){
					return True;
				} else {
					return False;
				}	
			}		
		}
	}
	
	public function verify_membase_DI_capable($remote_machine_name){
		// check if DI is implemented in the destination membase server
		$cksum_output = trim(shell_exec("echo stats | nc $remote_machine_name 11211 | grep cksum"));
		if(stristr($cksum_output, "cksum")){
			log_function::debug_log("verify_membase_DI_capable: ".$cksum_output);
			return True;
		} else {
			log_function::debug_log("verify_membase_DI_capable: ".$cksum_output);
			return False;
		}	
	}

}
?>