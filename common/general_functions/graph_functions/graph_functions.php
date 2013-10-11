/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php
class graph_functions{

	public function get_graphs($server_name, $graph_name, $path_to_download_graph, $graph_time = NULL, $graph_size = NULL){

		if(ZBASE_CLOUD == "va2"){	
			if($graph_time == NULL) $graph_time = "hour";
			if($graph_size == NULL) $graph_size = "medium";
			self::get_Gangila_graph($server_name, $graph_name, $path_to_download_graph, $graph_time);
		} else {
			if($graph_time == NULL) $graph_time = "now";
			if($graph_size == NULL) $graph_size = "small";
			self::get_RightScale_graph($server_name, $graph_name, $path_to_download_graph, $graph_time);
		}	
	}

	public function get_RightScale_graph($server_name, $graph_name, $path_to_download_graph, $graph_time = "now", $graph_size = "small"){

		$avilable_clouds = array("ec2" => "9236", "zc1" => "22328", "zc2" => "30287", "va1" => "NA", "va2" => "NA");	
		$cloud_id = $avilable_clouds[ZBASE_CLOUD];
	
		if($cloud_id <> "NA"){
			directory_function::create_directory($path_to_download_graph);
			$rs = new RightScale($cloud_id, "ops-mon@zynga.com", "jump1shark");
			$server_details = $rs->getServerDetails(trim(gethostbyname($server_name)));
			$monitoring_metrics_url = $rs->linkIterator($server_details["links"], "monitoring_metrics");
			$data =$rs->curl("https://my.rightscale.com".$monitoring_metrics_url."/cpu-0:cpu_overview");
			$data = json_decode($data, True);
			$graph_url = str_replace("Indian%2FMaldives", "America%2FLos_Angeles", $data["graph_href"]);
			$graph_url = str_replace("period=day", "period=".$graph_time, $graph_url);
			$graph_url = "https://my.rightscale.com".$graph_url."&title=".$server_name;
			$graph_url = str_replace("&", "\&", $graph_url);
			foreach($graph_name as $graph){
				$graph = unserialize($graph);
				foreach($graph as $name => $section){
					// Choose 1st array for Right scale graph
					if($section <> "NA"){
						$download_graph_url = str_replace("cpu-0", $section, $graph_url);
						$download_graph_url = str_replace("cpu_overview", $name, $download_graph_url);
						log_function::debug_log(shell_exec("wget -O ".$path_to_download_graph."/".$section."_".$name.".png ".$download_graph_url." 2>&1"));
						break;
					} else {
						log_function::debug_log("graph not declared $name $section");
					}
				}
			}
		} else {
			log_function::debug_log("$cloud_id is not defined. Skipping capturing graph");
		}
	}
	
	public function get_Gangila_graph($server_name, $graph_name, $path_to_download_graph, $graph_time = "hour", $graph_size = "medium"){
	
		if(!stristr($server_name, "va2")) $server_name = $server_name.".va2.zynga.com";
		directory_function::create_directory($path_to_download_graph);
		foreach($graph_name as $graph){
			$graph = unserialize($graph);
			foreach($graph as $g => $m){
				// Choose 2nd array for Ganglia graph
			}
			if(stristr($server_name, "raid1")){
				$download_graph_url = "http://netops-ganglia-1.va2.zynga.com/netops/graph.php?c=netops-demo-mb-raid1&h=".$server_name."&r=".$graph_time."&z=".$graph_size;
			} else {
				$download_graph_url = "http://netops-ganglia-1.va2.zynga.com/netops/graph.php?c=netops-demo-mb&h=".$server_name."&r=".$graph_time."&z=".$graph_size;
			}	
			if($m  <> "NA"){ // NA = graph not defined for Ganglia
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
							
}	


// define graphs
	// general
define('CPU_0_GRAPH', serialize(array("cpu_overview" => "cpu-0", "cpu_report" => "load_one")));
define('DISK_OCTETS_GRAPH', serialize(array("disk_octets" => "disk-xvda", "NA")));
define('DISK_OPS_GRAPH', serialize(array("disk_ops" => "disk-xvda", "NA")));
define('IF_OCTETS_ETH0_GRAPH', serialize(array("if_octets-eth0" => "interface", "network_report" => "load_one")));
define('IF_PACKETS_ETH0_GRAPH', serialize(array("if_packets-eth0" => "interface", "packet_report" => "load_one")));
define('LOAD_GRAPH', serialize(array("load" => "load", "load_report" => "load_one")));
define('MEMORY_USED_GRAPH', serialize(array("memory-used" => "memory", "mem_report" => "load_one")));
define('SWAP_USED_GRAPH', serialize(array("swap-used" => "swap", "dummy1" => "swap_free")));
	
	// zbase graphs
define('CURR_ITEMS_GRAPH', serialize(array("gauge-curr_items" => "zbase", "dummy2" => "CURR_ITEMS")));
define('EP_FLUSH_DURATION_GRAPH', serialize(array("gauge-ep_flush_duration" => "zbase", "dummy3" => "EP_FLUSH_DURATION")));
define('EP_FLUSHER_TODO_GRAPH', serialize(array("gauge-ep_flusher_todo" => "zbase", "dummy4" => "EP_FLUSHER_TODO")));
define('EP_NUM_EJECT_FAILURES_GRAPH', serialize(array("gauge-ep_num_eject_failures" => "zbase", "dummy5" => "EP_NUM_EJECT_FAILURES")));
define('EP_NUM_NON_RESIDENT_GRAPH', serialize(array("gauge-ep_num_non_resident" => "zbase", "dummy6" => "EP_NUM_NON_RESIDENT")));
define('EP_OOM_ERRORS_GRAPH', serialize(array("gauge-ep_oom_errors" => "zbase", "dummy7" => "EP_OOM_ERRORS")));
define('EP_QUEUE_SIZE_GRAPH', serialize(array("gauge-ep_queue_size" => "zbase", "dummy8" => "EP_QUEUE_SIZE")));
define('EP_TOTAL_CACHE_SIZE_GRAPH', serialize(array("gauge-ep_total_cache_size" => "zbase", "dummy9" => "EP_TOTAL_CACHE_SIZE")));
define('MEM_USED_GRAPH', serialize(array("gauge-mem_used" => "zbase", "dummy10" => "MEM_USED")));
define('EP_BG_FETCHED_GRAPH', serialize(array("counter-ep_bg_fetched"  => "zbase", "dummy11" => "EP_BG_FETCHED")));
define('EP_TAP_BG_FETCHED_GRAPH', serialize(array("counter-ep_tap_bg_fetched" => "zbase", "dummy12" => "EP_TAP_BG_FETCHED")));
define('CMD_GET_GRAPH', serialize(array("memcached_command-get" => "zbase", "dummy13" => "CMD_GET")));
define('CMD_SET_GRAPH', serialize(array("memcached_command-set" => "zbase", "dummy14" => "CMD_SET")));
define('EP_OVERHEAD_GRAPH', serialize(array("gauge-ep_overhead" => "zbase", "dummy15" => "EP_OVERHEAD")));				
									

		// default graphs to be downloaded
		// This will used if $graph_name is not declared
define('DEFAULT_GRAPH_LIST', serialize( array(	
				CPU_0_GRAPH,
				DISK_OCTETS_GRAPH,
				DISK_OPS_GRAPH,
				IF_OCTETS_ETH0_GRAPH,	
				IF_PACKETS_ETH0_GRAPH,
				LOAD_GRAPH,
				CMD_GET_GRAPH,
				CMD_SET_GRAPH,
				CURR_ITEMS_GRAPH,
				EP_FLUSH_DURATION_GRAPH,
				EP_FLUSHER_TODO_GRAPH,
				EP_NUM_EJECT_FAILURES_GRAPH,
				EP_NUM_NON_RESIDENT_GRAPH,
				EP_OOM_ERRORS_GRAPH,
				EP_QUEUE_SIZE_GRAPH,
				EP_TOTAL_CACHE_SIZE_GRAPH,
				MEM_USED_GRAPH,
				EP_BG_FETCHED_GRAPH,
				EP_TAP_BG_FETCHED_GRAPH,							
				MEMORY_USED_GRAPH,
				SWAP_USED_GRAPH)));

?>
