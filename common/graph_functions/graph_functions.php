<?php
class graph_functions{

	public function get_RightScale_graph($server_name, $graph_name = NULL, $path_to_download_graph, $graph_time = "now", $graph_size = "small"){
	
		if($graph_name == NULL){
			$graph_name = array(	
				"cpu_overview" => "cpu-0",
				"disk_octets" => "disk-xvda",
				"disk_ops" => "disk-xvda",
				"if_octets-eth0" => "interface",	
				"if_packets-eth0" => "interface",
				"load" => "load",
				"gauge-curr_items" => "membase",
				"gauge-ep_flush_duration" => "membase",
				"gauge-ep_flusher_todo" => "membase",
				"gauge-ep_num_eject_failures" => "membase",
				"gauge-ep_num_non_resident" => "membase",
				"gauge-ep_oom_errors" => "membase",
				"gauge-ep_queue_size" => "membase",
				"gauge-ep_total_cache_size" => "membase",
				"gauge-mem_used" => "membase",
				"counter-ep_bg_fetched"  => "membase",
				"counter-ep_tap_bg_fetched" => "membase",							
				"memory-used" => "memory",
				"swap-used" => "swap");	
		}
		
		if(defined('MEMBASE_CLOUD_ID') And MEMBASE_CLOUD_ID <> ""){
			directory_function::create_directory($path_to_download_graph);
			$rs = new RightScale(MEMBASE_CLOUD_ID, "ops-mon@zynga.com", "jump1shark");
			$server_details = $rs->getServerDetails(trim(gethostbyname($server_name)));
			$monitoring_metrics_url = $rs->linkIterator($server_details["links"], "monitoring_metrics");
			$data =$rs->curl("https://my.rightscale.com".$monitoring_metrics_url."/cpu-0:cpu_overview");
			$data = json_decode($data, True);
			$graph_url = str_replace("Indian%2FMaldives", "America%2FLos_Angeles", $data["graph_href"]);
			$graph_url = str_replace("period=day", "period=".$graph_time, $graph_url);
			$graph_url = "https://my.rightscale.com".$graph_url."&title=".$server_name;
			$graph_url = str_replace("&", "\&", $graph_url);
			
			foreach($graph_name as $name => $section){		
				$download_graph_url = str_replace("cpu-0", $section, $graph_url);
				$download_graph_url = str_replace("cpu_overview", $name, $download_graph_url);
				log_function::debug_log(shell_exec("wget -O ".$path_to_download_graph."/".$section."_".$name.".png ".$download_graph_url." 2>&1"));
			}
		} else {
			log_function::debug_log("MEMBASE_CLOUD_ID is not defined. Skipping capturing graph");
		}
	}
	
	public function get_Gangila_graph($server_name, $graph_name = NULL, $path_to_download_graph, $graph_time = "hour", $graph_size = "medium"){
	
		if($graph_name == NULL){
			$graph_name = array(	
				"cpu_report" => "load_one",
				"network_report" => "load_one",	
				"packet_report" => "load_one",
				"load_report" => "load_one",
				"dummy1" => "CURR_ITEMS",
				"dummy2" => "CMD_GET",
				"dummy3" => "CMD_SET",
				"dummy4" => "MEM_USED",
				"dummy5" => "EP_OVERHEAD",
				"dummy6" => "EP_OOM_ERRORS",			
				"dummy7" => "EP_TAP_BG_FETCHED",	
				"dummy8" => "EP_QUEUE_SIZE",
				"dummy9" => "EP_FLUSHER_TODO",
				"dummy10" => "EP_TOTAL_CACHE_SIZE",
				"dummy11" => "EP_BG_FETCHED",
				"dummy12" => "EP_NUM_EJECT_FAILURES",
				"dummy13" => "EP_NUM_NON_RESIDENT",						
				"mem_report" => "load_one",
				"dummy14" => "swap_free");
				
							$graph_name = array(	
				"cpu_report" => "load_one");
		}
		if(!stristr($server_name, "va2")) $server_name = $server_name.".va2.zynga.com";
		directory_function::create_directory($path_to_download_graph);
		foreach($graph_name as $g => $m){
			$download_graph_url = "http://netops-ganglia-1.va2.zynga.com/netops/graph.php?c=netops-demo-mb&h=".$server_name."&r=".$graph_time."&z=".$graph_size;
			$download_graph_url = $download_graph_url."&m=".$m;
			if(!stristr($g, "dummy")){
				$download_graph_url = $download_graph_url."&g=".$g;
				$download_graph_url = str_replace("&", "\&", $download_graph_url);
				log_function::debug_log(shell_exec("wget -O ".$path_to_download_graph."/".$g."_".$m.".png ".$download_graph_url." 2>&1"));
			} else {
				$download_graph_url = str_replace("&", "\&", $download_graph_url);
				log_function::debug_log(shell_exec("wget -O ".$path_to_download_graph."/".$m.".png ".$download_graph_url." 2>&1"));
			}
		}
	}
	
	
	
	
	
}	
?>