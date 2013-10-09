<?php
class storage_server_api{

	public function add_entry_api($storage_server, $type, $entry){
				$status = diskmapper_api::curl_call("http://$storage_server/api/?action=add_entry&type=$type&entry=$entry");
				return trim($status);
	}

	public function remove_entry_api($storage_server, $type, $entry){
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=remove_entry&type=$type&entry=$entry");
                return trim($status);
	}
	
	public function list_api($storage_server, $path="/", $recursive="false") {
                $status = diskmapper_api::curl_call("http://$storage_server/api".$path."?action=list&recursive=$recursive");
                return trim($status);
        }

	public function get_file_api($storage_server, $type) {
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=get_file&type=$type");
				$file_content = trim($status,'"');
                $array = explode("\\n", $file_content);
                $status = array_filter($array);
                return $status;
        }

	public function get_mtime_api($storage_server, $disk, $host_name, $type="primary"){
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=get_mtime&host_name=$host_name&type=$type&disk=$disk");
                return trim($status);
        }

	public function make_spare_api($storage_server, $disk, $type){
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=make_spare&disk=$disk&type=$type");
                return trim($status);
        }

	public function get_config_api($storage_server){
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=get_config");
				$json_string = trim($status);
                return(json_decode($json_string, TRUE));

        }

        public function initialize_host_api($storage_server, $disk, $game_id, $host_name, $type, $promote="false"){
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=initialize_host&game_id=$game_id&host_name=$host_name&type=$type&disk=$disk&promote=$promote");
                return trim($status);
        }

	public function create_torrent_api($storage_server, $file_path) {
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=create_torrent&file_path=$file_path");
                return trim($status);
        }

	public function start_download_api($storage_server, $file_path, $torrent_url) {
                $status = diskmapper_api::curl_call("http://$storage_server/api/?action=start_download&file_path=$file_path&torrent_url=$torrent_url");
				return trim($status);
	}

}
?>
