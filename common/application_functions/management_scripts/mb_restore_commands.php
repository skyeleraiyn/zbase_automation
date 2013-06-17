<?php
class mb_restore_commands {

	public function restore_server($hostname) {
                if(IBR_STYLE == 2.0 && defined('DISK_MAPPER_SERVER_ACTIVE') && DISK_MAPPER_SERVER_ACTIVE <> "") {
					$host = general_function::get_hostname($hostname);
				}
				else {
		 			$host = $hostname;	
				}
				$command_to_be_executed = "sudo python26 ".MEMBASE_RESTORE_SCRIPT." -h $host";
		        return remote_function::remote_execution_popen($hostname, $command_to_be_executed);
	}
}
?>
