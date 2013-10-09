<?

class moxi_functions	{

	public function get_moxi_stats($server, $specific_stat_name = NULL)	{
		return self::get_moxi_stats_array($server, "proxy");
	}

	public function check_moxi_received_config()	{
		global $moxi_machines; 
		foreach($moxi_machines as $id=>$moxi)	{
			$return_array = self::get_moxi_stats($moxi);
			#if($return_array[
		}
	}

	                // Basic stats
        public function get_moxi_stats_array($server_name, $stat_option = NULL){
                for($iattempt = 0 ;$iattempt < 50; $iattempt++ ){
                        $conn = @memcache_connect($server_name, 11114);
                        if(is_object($conn)) break;
                        sleep(1);
                }
                $stats_array = $conn->getstats($stat_option);
                memcache_close($conn);
                return $stats_array;
        }


}



?>
