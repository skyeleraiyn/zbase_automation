<?php
class vba_setup {


	public function vba_cluster_start_stop($command = "start", $spare  = False)	{
		global $test_machine_list;
                global $spare_machine_list;
		if($spare) {
			foreach($spare_machine_list as $test_machine) {
				self::vba_start_stop($test_machine, $command);
			}
		}

		foreach ($test_machine_list as $test_machine) {
			$pid = pcntl_fork();
			if($pid==0) {
				self::vba_start_stop($test_machine, $command);
				exit();
			}
			else {
				$pid_arr[] = $pid;
			}
		}
		foreach ($pid_arr as $pid) {
			pcntl_waitpid($pid, $status);
		}		
	}

        public function vba_start_stop($remote_machine_name, $command = "start")      {
		return service_function::control_service($remote_machine_name, VBA_SERVICE, $command);
        }

}
?>
