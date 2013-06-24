<?php

class tap_example{
	public function fetch_key($server_name, $timeout = 5){
		remote_function::remote_file_copy($server_name, HOME_DIRECTORY."common/misc_files/timeout.sh", "/tmp/timeout.sh");
		remote_function::remote_execution($server_name, "chmod +x /tmp/timeout.sh");
		$command_to_be_executed = "/tmp/timeout.sh -t $timeout python ".TAP_EXAMPLE_SCRIPT." localhost:".MEMBASE_PORT_NO;
		return remote_function::remote_execution($server_name, $command_to_be_executed);
	
	}	
} 	
?>