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
<?

class vbs_functions	{
        #Returns an array of vbucket_ids with the server IP of active and replica vbuckets
        public function get_vb_map()    {
                $return_array = array();
                $server = array();
                $ci = curl_init();
                curl_setopt($ci, CURLOPT_URL, GET_VBS_MAPPING);
                curl_setopt($ci, CURLOPT_HEADER, 0);
                curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                $vb_mapping = curl_exec($ci);
                $vb_map_params = json_decode($vb_mapping);
                curl_close($ci);
                $vb_map = $vb_map_params->buckets[0]->vBucketServerMap->vBucketMap;
                $server_list = $vb_map_params->buckets[0]->vBucketServerMap->serverList;
                foreach($server_list as $key=>$server_with_ip)  {
                        $server_only = explode(":", $server_with_ip);
                        $server[$key] = $server_only[0];
                }
                foreach($vb_map as $vb=>$servers)       {
			if($servers[0] < 0)	{
				$return_array[$vb]['active'] = "NIL";
				$return_array[$vb]['replica'] = $server[$servers[1]];
			}
			else if(!(array_key_exists(1,$servers)) or $servers[1] < 0 ) {	
				$return_array[$vb]['active'] = $server[$servers[0]];
				$return_array[$vb]['replica'] = "NIL";
				}
			else	{
                        	$return_array[$vb]['active'] = $server[$servers[0]];
	                        $return_array[$vb]['replica'] = $server[$servers[1]];
			}
                }
                return $return_array;

        }

	#Adds a server to the exsting cluster - upshard.
	public function add_server_to_cluster($server_ip)	{
		#Given one IP fine the other
		$ip_array = general_function::get_dual_ip($server_ip);
		$ci = curl_init();
                curl_setopt($ci, CURLOPT_URL, SERVER_ALIVE_API);
                curl_setopt($ci, CURLOPT_HEADER, true);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		$ip['SecIp']=array(str_replace("\r\n","",$ip_array[1]).":11211");
		$ip['Server']=array(str_replace("\r\n","",$ip_array[0]).":11211");
		$json = json_encode($ip);
		echo $json;
		curl_setopt($ci , CURLOPT_POSTFIELDS, $json);
		echo "{\"SecIp\" : [\"".$ip_array[1].":11211\"], \"Server\" : [\"".$ip_array[0].":11211\"]}";
		$status = curl_exec($ci);
		curl_close($ci);
		if(stristr($status, "SUCCESS"))	{
			return 1;
		}
		return 0;
	}

	public function remove_server_from_cluster($server_ip)	{
		$ip_array = general_function::get_dual_ip($server_ip);
		print_r($ip_array);
                $ci = curl_init();
                curl_setopt($ci, CURLOPT_URL, SERVER_DOWN_API);
		curl_setopt($ci, CURLOPT_HEADER, true);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		$ip['SecIp']=array(str_replace("\r\n","",$ip_array[1]).":11211");
		$ip['Server']=array(str_replace("\r\n","",$ip_array[0]).":11211");
		$json = json_encode($ip);
		echo $json;
		curl_setopt($ci , CURLOPT_POSTFIELDS, $json);
                $status = curl_exec($ci);
                curl_close($ci);
                if(stristr($status, "SUCCESS")) {
                        return 1;
                }
                return 0;
	}

	public function set_log_vb__level($level) {
		$ci = curl_init();
		curl_setopt($ci, CURLOPT_URL, VB_SET_PARAM);
		curl_setopt($ci, CURLOPT_HEADER, 'Content-Type: application/json');
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		echo "{\"Loglevel\":$level}";
		curl_setopt($ci, CURLOPT_POSTFIELDS, "{\"Loglevel\": $level}");
		$status = curl_exec($ci);
		curl_close($ci);
		return 1;
	}

	public function get_no_of_vbuckets(){
		$vb_array=self::get_vb_map();
		return count($vb_array);
	}

}

?>
