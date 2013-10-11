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

class tap_example{
	public function fetch_key($server_name, $timeout = 5){
		remote_function::remote_file_copy($server_name, HOME_DIRECTORY."common/misc_files/timeout.sh", "/tmp/timeout.sh");
		remote_function::remote_execution($server_name, "chmod +x /tmp/timeout.sh");
		$command_to_be_executed = "/tmp/timeout.sh -t $timeout python ".TAP_EXAMPLE_SCRIPT." localhost:".ZBASE_PORT_NO;
		return remote_function::remote_execution($server_name, $command_to_be_executed);
	
	}	
} 	
?>
