<?php
class mb_restore_commands {

	public function restore_server($hostname) {
		$command_to_be_executed = "sudo python26 ".MEMBASE_RESTORE_SCRIPT." -h $hostname";
        return remote_function::remote_execution_popen($hostname, $command_to_be_executed);
	}
}
?>