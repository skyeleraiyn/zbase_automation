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
class diskmapper_api{

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

	//Parameter in this case generally refers to 'disk' or 'storage_server' to which the host has been mapped.
	//If neither host_name or return_array are set, then the script just exits. So either one at least should be set.
	//If only $host_name is defined then it will return the specified $parameter value in the form of a string.
	//If return_array is set to true then it will return all the hosts that exist for that game on the storage servers in the form of an array.


	public function get_all_config($disk_mapper_server = DISK_MAPPER_SERVER_ACTIVE){
		$json_string = self::curl_call("http://".$disk_mapper_server."/api?action=get_all_config");
		return(json_decode($json_string, TRUE));
	}

	public function get_ss_mapping($disk_mapper_server = DISK_MAPPER_SERVER_ACTIVE) {
		$json_string = self::curl_call("http://".$disk_mapper_server."/api?action=get_ss_mapping");
		return(json_decode($json_string, TRUE));
	}

	public function get_vb_mapping($disk_mapper_server = DISK_MAPPER_SERVER_ACTIVE) {
		$json_string = self::curl_call("http://".$disk_mapper_server."/api?action=get_vb_mapping");
		return(json_decode($json_string, TRUE));
	}

	public function zstore_put($file_name, $host_name, $parameters = "test", $vb_id=NULL){
		$disk_mapper_server = general_function::get_ip_address(DISK_MAPPER_SERVER_ACTIVE, False);
        if($vb_id ==NULL) {
    		$command_to_be_executed = "zstore_cmd put $file_name s3://".$disk_mapper_server."/".GAME_ID."/$host_name/".ZBASE_CLOUD."/$parameters/";
        }
        else {
            $command_to_be_executed = "zstore_cmd put $file_name s3://".$disk_mapper_server."/".GAME_ID."/$host_name/$vb_id/$parameters/";
        }
		for($iattempt = 0 ; $iattempt<3 ; $iattempt++){
			$start_time = time();
			$status = remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed);
			$total_time = time() - $start_time;
			log_function::debug_log("upload time: $total_time, retries: $iattempt");
			if(stristr($status, "Saved file to disk")){
				return True;
			} else {
				sleep(2);
			}
		}
		return False;
	}

	public function zstore_get($filename, $host_name, $parameters = "test"){
		$disk_mapper_server = general_function::get_ip_address(DISK_MAPPER_SERVER_ACTIVE, False);
		$filename = basename($filename);
		$command_to_be_executed = "zstore_cmd get  s3://".$disk_mapper_server."/".GAME_ID."/$host_name/".ZBASE_CLOUD."/$parameters/$filename /tmp/$filename";
		return(remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed));
	}

	public function zstore_list($host_name, $filename = NULL, $parameters = NULL){
		$disk_mapper_server = general_function::get_ip_address(DISK_MAPPER_SERVER_ACTIVE, False);
		if($filename <> NULL){
			$filename = basename($filename);
			$command_to_be_executed = "zstore_cmd ls s3://".$disk_mapper_server."/".GAME_ID."/$host_name/".ZBASE_CLOUD."/$parameters/$filename";
			return remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed);
		} else {
			$command_to_be_executed = "zstore_cmd la s3://".$disk_mapper_server."/".GAME_ID."/$host_name/".ZBASE_CLOUD."/$parameters";
			return explode(" " ,remote_function::remote_execution(TEST_HOST_2, $command_to_be_executed));
		}
	}


}
?>
