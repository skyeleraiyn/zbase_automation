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

$source_pool = get_machine_list("/tmp/src_pool");
$destination_pool = get_machine_list("/tmp/dest_pool");

	// create conn for source and destination pool
$source_pool_all_conn = new memcache();
foreach($source_pool as $server){
	$source_pool_all_conn->addserver($server, 11211);
}

$source_pool_conn = array();
foreach($source_pool as $server){
	$mc = new memcache();
	$mc->addserver($server, 11211);
	$source_pool_conn[] = $mc;
}

$destination_pool_conn = array();
foreach($destination_pool_conn as $server){
	$mc = new memcache();
	$mc->addserver($server, 11211);
	$destination_pool_conn[] = $mc;
}

	// set keys in source and verify in destination
	
for($ikey=0;$ikey<2;$ikey++){
	$source_pool_all_conn->set("testkey_to_verify_source_destination_conn_$ikey", "testvalue_to_verify_source_destination_conn_$ikey", 0, 10);
}	

	// get the source and destination pool ips and create indiviual conn to those machines
$source_conn_list_with_key = array();
$destination_conn_list_with_key = array();
for($ikey=0;$ikey<2;$ikey++){
	$ip_address = $source_pool_all_conn->findserver("testkey_to_verify_source_destination_conn_$ikey");
		// source conn for each key
	$mc = new memcache();
	$mc->addserver($ip_address, 11211);
	$source_conn_list_with_key[] = $mc;
	
		// destination conn for each key
	$index = array_search($ip_address, $source_pool);
	$mc = new memcache();
	$mc->addserver($destination_pool[$index], 11211);
	$destination_conn_list_with_key[] = $mc;	
}

sleep(3);

$mismatch = False;
for($ikey=0;$ikey<2;$ikey++){
	$source_value = $source_conn_list_with_key[$ikey]->get("testkey_to_verify_source_destination_conn_$ikey");
	$destination_value = $destination_conn_list_with_key[$ikey]->get("testkey_to_verify_source_destination_conn_$ikey");
	if($source_value <> $destination_value){
		echo "mismatch found for key testkey_to_verify_source_destination_conn_$ikey \n";
		$mismatch = True;
	}	
}
	
if($mismatch){
	echo "Verification complete and mismatch found \n";
} else {
	echo "Verification complete and no mismatch found \n";
}



function getConn($machine_list){
	$mc = new memcache();	
	if(isarray(machine_list)){
		foreach($machine_list as $server){
			$mc->addserver($server, 11211);
		}
	} else {
		$mc->addserver($machine_list[], 11211);
	}
	return $mc;
}

function get_machine_list($file_path){
	$server_list=array();
	$fp=fopen($file_path, 'r');
	while (!feof($fp)){
		$server=fgets($fp);
		$server=trim($server);
		$server=explode(":", $server);
		//add to array
		if(count($server) > 1)	$server_list[]=$server[0];
	}
	fclose($fp);
	return $server_list;
}
?>
