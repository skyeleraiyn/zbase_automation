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
	public function restore_to_cluster($hostname, $vb_id,  $disk_mapper = DISK_MAPPER_SERVER_ACTIVE) {
		$command_to_be_executed = "sudo python26 ".MEMBASE_RESTORE_SCRIPT." -v ".$vb_id." -d ".$disk_mapper;
		return remote_function::remote_execution_popen($hostname, $command_to_be_executed);
	}
}
?>
