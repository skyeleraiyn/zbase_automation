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
class vba_stub_functions{

	public function is_initialised($VBA) {
                $init = $VBA->readCommand();
		if($init !== FALSE && !is_string($init) && !strcmp("INIT",$init["Cmd"]) )
			return TRUE;
		else
			return FALSE;

	}

	public function check_initialization($VBA)
	{
		if(is_array($VBA))
		{
			for($i=0;$i<count($VBA);$i++)
			{
				$status = self::is_initialised($VBA[$i]);
				if($status === FALSE)
					return FALSE;
			}
			return TRUE;
		}
		else
			return(self::is_initialised($VBA));
	}

	public function get_config($VBA)
	{
		$data = $VBA->readCommand(30);						// Initial config comes after some time because VBS waits for all VBAs to connect.
		if ($data === FALSE || is_string($data) || strcmp("CONFIG",$data["Cmd"]))
			return FALSE;
		else
			return $data;
	}

	public function verify_config_structure($config)				// can add in this if the strucure changes
	{
		if(!array_key_exists('Data',$config))
			return("Key Data does not exist");

		else if(!array_key_exists('RestoreCheckPoints',$config))
			return("Key RestoreCheckPoints does not exist");

		else if(!array_key_exists('HeartBeatTime',$config))
			return("Key HeartBeatTime does not exist");

		else if(!is_array($config['Data']))
			return("Key Data has non-array value");

		else if($config['HeartBeatTime'] == NULL)
			return("HeartBeat has NULL value");

		$data = self::parse_config($config,'Data');

		for($i=0;$i<count($data);$i++)
		{

			if(!array_key_exists('Source',$data[$i]))
				return("Key Source in Data does not exist");

			else if(!array_key_exists('VbId',$data[$i]))
				return("Key VbId in Data does not exist");

			else if(!array_key_exists('Destination',$data[$i]))
				return("Key Destination in Data does not exist");

			else if(!array_key_exists('CheckPoints',$data[$i]))
				return("Key CheckPoints in Data does not exist");

			else if(!array_key_exists('Transfer_VbId',$data[$i]))
				return("Key Transfer_VbId in Data does not exist");

			else if($data[$i]['Source'] == NULL)
				return("Source in Data has NULL value");

			else if($data[$i]['VbId'] == NULL)
				return("VbId in Data has NULL value");

			else if($data[$i]['Destination'] == NULL)
				return("Destination in Data has NULL value");

		}
		return TRUE;
	}

        public function is_uniform_distribution($vbucket_list_array)
        {
                $max_vbuckets = count($vbucket_list_array[0]);$min_vbuckets = count($vbucket_list_array[0]);
                for($i=1;$i<count($vbucket_list_array);$i++)
                {
                        if(count($vbucket_list_array[$i]) > $max_vbuckets)
                                $max_vbuckets = count($vbucket_list_array[$i]);
                        else if(count($vbucket_list_array[$i]) < $min_vbuckets)
                                $min_vbuckets = count($vbucket_list_array[$i]);
                }
                if($max_vbuckets - $min_vbuckets >= NO_OF_VBA)
                        return FALSE;
                else
                        return TRUE;


        }

        public function has_duplicates($vbucket_list)
        {
                sort($vbucket_list);
                for($i=1;$i<count($vbucket_list);$i++)
                        if($vbucket_list[$i-1] == $vbucket_list[$i])
                                return TRUE;

                return FALSE;
        }

	public function verify_vbucket_list($vbucket_list)				//// It can verify whethet initial distribution is correct
	{
		for($i=0;$i<count($vbucket_list);$i++)					//Checking for Vbuckets Duplication in the VBA
		{
			if(self::has_duplicates($vbucket_list[$i]))
				return("Duplicate Vbuckets in VBA");
		}

		if(!self::is_uniform_distribution($vbucket_list))			//Checking for Uniform Distribution
			return("Non-uniform distribution");

		$complete_list = Array();
		$complete_list = $vbucket_list[0];
		for($i=1;$i<count($vbucket_list);$i++)
			$complete_list = array_merge($complete_list,$vbucket_list[$i]);

		if(self::has_duplicates($complete_list))				//Checking for Vbuckets Duplication in the Cluster
			return("Duplicate Vbuckets in Cluster");

		if(count($complete_list) != NO_OF_VBUCKETS)				//Verifying the Vbuckets count 
			return("Less Vbuckets in Cluster");
		else
			return TRUE;
	}




        public function verify_replica_active_not_on_same_box($active_list,$replica_list)       //It make sure that Active and replicas of the same vbucket are not on the same bix     
        {
                if(!is_array($active_list) || !is_array($replica_list) || count($active_list) != count($replica_list))
                        return "Error: Invalid Arguments";

                for($i=0;$i<count($active_list);$i++)
                {
                        for($j=0;$j<count($active_list[$i]);$j++)
                                if(in_array($active_list[$i][$j],$replica_list[$i]))
                                        return FALSE;
                }
                return TRUE;
        }


	public function get_no_of_peer_vbas($config) {
		$list_vbas = self::parse_config($config, "Data");
		return (count($list_vbas));
	}

	public function parse_config($config, $parameter="Cmd") {
		return $config[$parameter];
	}

	public function get_restore_checkpoint($config) {
		return self::parse_config($config, "RestoreCheckPoints");
	}

	public function get_heart_beat_time($config) {
		return self::parse_config($config, "HeartBeatTime");
	}

	public function get_list_of_destinations($config) {
                $list_vbas = self::parse_config($config, "Data");
		$list_destinations = array();
		for($vba = 0; $vba < count($list_vbas); $vba++) {
			array_push($list_destinations, $vba["Destination"]);
		}
		return $list_destinations;
	}





	public function get_vbucket_list($configs,$is_active=TRUE)		// getting both lists from one function [TRUE (default) -> Active, FALSE -> Replica]
	{
		if($is_active)
			return (self::get_list_of_active_vbuckets($configs));
		else
			return (self::get_list_of_replica_vbuckets($configs));
	}

	private function get_list_of_replica_vbuckets($configs)			//getting replica list from the VBA configs (All configs should be given to find replicas in all) 
	{
		$replica_list = Array();
		for($i=0;$i<count($configs);$i++)
		{
			$source_ip = $configs[$i]['Data'][0]['Source'];
			$replica_list[$i] = Array();
			for($j=0;$j<count($configs);$j++)
			{
				if(!strcmp($source_ip,$configs[$j]['Data'][0]['Source']))
					continue;
				else
				{
					for($k=0;$k<count($configs[$j]['Data']);$k++)
					{
						if(!strcmp($source_ip,$configs[$j]['Data'][$k]['Destination']))
							$replica_list[$i] = array_merge($replica_list[$i],$configs[$j]['Data'][$k]['VbId']);
					}
				}
			}
		}
		return($replica_list);
	}

	private function get_list_of_active_vbuckets($configs)
	{
		$active_list = Array();
		for($i=0;$i<count($configs);$i++)
		{
			$active_list[$i] = Array();
			$active_list[$i] = array_merge($active_list[$i],self::extract_vbuckets($configs[$i]));		// extract_vbuckets used here
		}
	
		return($active_list);
	}

	public function extract_vbuckets($config, $is_array = false) {		// Extract all the vbuckets given in the VBA config (they are actives only) -- used above
                if($is_array){
                        $list_vbuckets = array();
                        foreach ($config as $vba_config) {
                                $list_vbuckets = array_merge($list_vbuckets, self::extract_vbuckets($vba_config));
                        }
                }
                else
                        {
                        $list_vbas = self::parse_config($config, "Data");
                        $list_vbuckets= array();
                        for($vba = 0; $vba < count($list_vbas); $vba++) {
                                $list_vbuckets = array_merge($list_vbuckets, $list_vbas[$vba]["VbId"]);
                        }
                }
                return $list_vbuckets;
        }
	public function get_list_of_sources($config) {
                $list_vbas = self::parse_config($config, "Data");
                $list_sources = array();
                for($vba = 0; $vba < count($list_vbas); $vba++) {
                        array_push($list_sources, $vba["Source"]);
                }
                return $list_sources;
        }

	public function get_vba_ip($config) {
		$list_sockets = self::get_list_of_sources($config);
		$unique_list = array_unique($list_sockets);
		if(count($unique_list) != 1) {
			log_function::debug_log("found non unique ip list".print_r($unique_list, TRUE));
			return False;
		}
		$list=explode(":",$unique_list[0]);
		return $list[0];
	}


}
?>

