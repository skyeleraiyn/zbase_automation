<?php
class graph_functions{

	function get_RightScale_graph($server_name, $graph_name, $path_to_download_graph, $graph_time = "now", $graph_size = "small"){
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
}	
?>