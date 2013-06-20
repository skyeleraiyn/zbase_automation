<?

class moxi_functions	{

	public function get_moxi_stats($server, $specific_stat_name = NULL)	{
		print_r(stats_functions::get_stats_array($server, "proxy", 14000 ));
	}

	public function check_moxi_received_config()	{
		global $moxi_machines; 
		foreach($moxi_machines as $id=>$moxi)	{
			$return_array = self::get_moxi_stats($moxi);
			#if($return_array[
		}
	}


}



?>
