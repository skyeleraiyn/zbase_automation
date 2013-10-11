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
class storage_server_api{

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

	public function add_entry_api($storage_server, $type, $entry){
		$status = self::curl_call("http://$storage_server/api/?action=add_entry&type=$type&entry=$entry");
		return trim($status);
	}

	public function remove_entry_api($storage_server, $type, $entry){
		$status = self::curl_call("http://$storage_server/api/?action=remove_entry&type=$type&entry=$entry");
		return trim($status);
	}

	public function list_api($storage_server, $path="/", $recursive="false") {
		$status = self::curl_call("http://$storage_server/api".$path."?action=list&recursive=$recursive");
		return trim($status);
	}

	public function get_file_api($storage_server, $type) {
		$status = self::curl_call("http://$storage_server/api/?action=get_file&type=$type");
		$file_content = trim($status,'"');
		$array = explode("\\n", $file_content);
		$status = array_filter($array);
		return $status;
	}

	public function get_mtime_api($storage_server, $disk, $host_name, $type="primary"){
		$status = self::curl_call("http://$storage_server/api/?action=get_mtime&host_name=$host_name&type=$type&disk=$disk");
		return trim($status);
	}

	public function make_spare_api($storage_server, $disk, $type){
		$status = self::curl_call("http://$storage_server/api/?action=make_spare&disk=$disk&type=$type");
		return trim($status);
	}

	public function get_config_api($storage_server){
		$status = self::curl_call("http://$storage_server/api/?action=get_config");
		$json_string = trim($status);
		return(json_decode($json_string, TRUE));

	}

	public function initialize_host_api($storage_server, $disk, $game_id, $host_name, $type, $promote="false"){
		$status = self::curl_call("http://$storage_server/api/?action=initialize_host&game_id=$game_id&host_name=$host_name&type=$type&disk=$disk&promote=$promote");
		return trim($status);
	}

	public function create_torrent_api($storage_server, $file_path) {
		$status = self::curl_call("http://$storage_server/api/?action=create_torrent&file_path=$file_path");
		return trim($status);
	}

	public function start_download_api($storage_server, $file_path, $torrent_url) {
		$status = self::curl_call("http://$storage_server/api/?action=start_download&file_path=$file_path&torrent_url=$torrent_url");
		return trim($status);
	}

}
?>
