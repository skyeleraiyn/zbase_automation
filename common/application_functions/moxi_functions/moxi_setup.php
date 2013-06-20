<?

class moxi_setup	{

	public function populate_and_copy_config_file()	{
		global $moxi_machines;
		#Create the config
		$string = "MCMUX_MODE=0\nVBS_SERVER=".VBS_IP.":14000";
		$file_handle = fopen("/tmp/moxi", "w");
		file_function::write_to_file("/tmp/moxi", $config, "w");
		foreach($moxi_machines as $id=>$moxi)   {
			remote_function::remote_file_copy($moxi, "/tmp/moxi", MOXI_CONFIG, False, True, True);
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
