<?php


class vbs_cmd{
        public function curl_call($url){
                log_function::debug_log(general_function::get_caller_function());
                log_function::debug_log($url);

                $curl_session = curl_init();
                curl_setopt($curl_session, CURLOPT_URL, $url);
                curl_setopt($curl_session, CURLOPT_HEADER, 0);
                curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, 1);
                $curl_output = curl_exec($curl_session);
                curl_close($curl_session);
                log_function::debug_log($curl_output);
                return $curl_output;
        }

	public function getVBMap($hostname, $cluster, $port){

		$map = self::curl_call("http://$hostname:$port/$cluster/vbucketMap");
		$full_map = json_decode($map, true);

		print_r($full_map);
		echo "Echo Echo\n";
		print_r($full_map["buckets"][0]["port"]);
	}

	public function get_vbs_config_json(){
		$config = remote_function::remote_execution(VBS_IP, "cat ".VBS_CONFIG);
		return $config;
	}




}


?>

