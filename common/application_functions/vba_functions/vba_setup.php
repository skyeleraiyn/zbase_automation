<?php
class vba_setup {


	public function vba_cluster_start_stop($command = "start")	{
		global $test_machine_list;
		for($i=0; $i<count($test_machine_list); $i++)	{
			self::vba_start_stop($test_machine_list[$i], $command);
		}
	}

        public function vba_start_stop($remote_machine_name, $command = "start")      {
		return service_function::control_service($remote_machine_name, VBA_SERVICE, $command);
        }

}
?>
