<?

class moxi_setup	{

	public function populate_and_copy_config_file($machine = NULL)	{
		#Create the config
		global $moxi_machines;
		$config= "MCMUX_MODE=0\nVBS_SERVER=".VBS_IP.":14000";
		$file_handle = fopen("/tmp/moxi", "w");
		file_function::write_to_file("/tmp/moxi", $config, "w");
		if($machine) {
	                remote_function::remote_file_copy($machine, "/tmp/moxi", MOXI_CONFIG, False, True, True);
		}
		remote_function::remote_file_copy($moxi_machines[0], "/tmp/moxi", MOXI_CONFIG, False, True, True);

	}

	public function copy_pump_script() {
		global $moxi_machines;
		foreach($moxi_machines as $id=>$moxi) {
			remote_function::remote_file_copy($moxi, HOME_DIRECTORY."common/misc_files/1.9_files/pump", "/tmp/pump.php");
		}
	}

	public function moxi_start_stop_all($command = "start")      {
		global $moxi_machines;
		foreach($moxi_machines as $id=>$moxi)	{
			self::moxi_start_stop($moxi,  $command);
		}
        }

	public function moxi_start_stop($machine, $command)	{
		service_function::control_service($machine, MOXI_SERVICE, $command);
	}
}



?>
