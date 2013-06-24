<?php
class mb_restore_commands {

	public function restore_server($hostname) {
		$temp_hostname = general_function::get_hostname($hostname);
		$command_to_be_executed = "sudo python26 ".MEMBASE_RESTORE_SCRIPT." -h $temp_hostname";
        return remote_function::remote_execution_popen($hostname, $command_to_be_executed);
	}
}
?>