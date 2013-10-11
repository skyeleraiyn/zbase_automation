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

/**
* RightScale client class
* 
*/
class RightScale {

	private $cookieFile;
	private $acctID;
	private $userName;
	private $password;
	private $apiHrefPrefix;
	private $apiVersion;
	private $zCloud;
	private $cloudID;
	public  $lastHttpCode;
	public  $headerOnly;

	/**
	* Create cookie to connect to rightscale.
	*
	*/
	public function __construct ($acctID, $userName, $password) {
		$this->acctID = $acctID;
		$this->userName = $userName;
		$this->password = $password;
		$this->cookieFile = $this->createCookie();	
		$this->apiHrefPrefix = "https://my.rightscale.com/api";
		$this->apiVersion = "X-API-VERSION:1.5";
		$this->cloudID = $this->getCloudID($acctID);
		$this->zCloud = TRUE;

		if ($acctID == 9236) {
			$this->apiVersion = "X-API-VERSION:1.0";
			$this->apiHrefPrefix = $this->apiHrefPrefix . "/acct/9236";
			$this->zCloud = FALSE;
		}

		$this->login();

	}

	public function __destruct () {
		$this->destroyCookie($this->cookieFile);
	}

	public function getCloudID($acctID) {
		$cloudIDs = array("9236" => "1", "22328" => "858", "30287" => "1384");
		return $cloudIDs[$acctID];
	}

	private function createCookie() {
		$cookie = tempnam("/tmp", 'rscookiefoo');
		return $cookie;
	}

	private function destroyCookie($cookie) {
	#unlink($cookie);
	}

	/*
	* Creates a valid cookie with the given creds.
	*
	* @return		TRUE if successfully logged in, FALSE on failure.
	*/
	private function login() {
		if ($this->zCloud === FALSE) {
			$url = $this->apiHrefPrefix . "/login?api_version=1.0";
			$authdata = "rs_ec2_login";
		} else {
			$authdata = "email=" . $this->userName . "&password=" . $this->password . "&account_href=/api/accounts/" . $this->acctID;
			$url = $this->apiHrefPrefix . "/session";
		}
		$this->curl($url, $authdata);
		if($this->lastHttpCode == 204) {
			return TRUE;
		}
		return FALSE;
	}

	/*
	* Get zone of a given server.
	*
	* @params	string	$ipAddress	IP address of the server.
	*
	* @return	mixed			Zone of the server, FALSE on failure.
	*/
	public function getZone($ipAddress) {
		$ipAddress = preg_replace("/:.*/", "", $ipAddress);

		$serverDetails = $this->getServerDetails($ipAddress);

		if ($this->zCloud === FALSE) {
			if (array_key_exists("ec2_availability_zone", $serverDetails)) {
				return $serverDetails["ec2_availability_zone"];
			} 
			return FALSE;

		}

		if (array_key_exists("links", $serverDetails) === FALSE) {
			return FALSE;
		}

		$links = $serverDetails["links"];
		$dataCenter = $this->linkIterator($links, "datacenter");

		if ($dataCenter === NULL) {
			return FALSE;
		}
		$dataCenter = preg_replace("/.*\//", "", $dataCenter);

		return $this->getZoneName($dataCenter);

	}

	public function linkIterator($links, $match) {
		$href = NULL;
		foreach ($links as $link) {
			if ($link->rel == $match){
				$href = $link->href;
			}
		}

		if ($href === NULL) {
			return FALSE;
		}
		return $href;
	}

	/*
	* Get rightscale name of a given server.
	*
	* @params      string  $ipAddress      IP address of the server.
	*
	* @return      mixed                   Rightscale name of the server, FALSE on failure.
	*/
	public function getRSName($ipAddress) {
		$ipAddress = preg_replace("/:.*/", "", $ipAddress);

		$serverDetails = $this->getServerDetails($ipAddress);

		$name = "name";
		if ($this->zCloud === FALSE) {
			$name = "nickname";
		}

		if (array_key_exists($name, $serverDetails)) {
			return $serverDetails[$name];
		}
		return FALSE;
	}

	private function getZoneName($zoneID){
		$zones = array("CCPJIV5G5KJQM" => "CA1-Bot", "CLTPJ0435S102" => "CA1-Top", "EVQGGV1UQA792" => "VA2", "7J7KE1HR39AM1" => "VA1");
		return $zones[$zoneID];
	}

	/*
	* Run rightscript.
	*
	* @params      string  $ipAddress      IP address of the server.
	* @params      string  $scriptID       ID of script to be run.
	*
	* @return      bool                    TRUE on success, FALSE ON FAILURE
	*/
	public function runScript($ipAddress, $scriptID) {
		$ipAddress = preg_replace("/:.*/", "", $ipAddress);
		$postVar = "";
		$serverDetails = $this->getServerDetails($ipAddress);
		if($this->zCloud === FALSE){
			$url = $serverDetails["href"] . "/run_script";
			$postVar = "right_script=$scriptID";
			$returnCode = 201;

		} else {
			$url = $this->linkIterator($serverDetails["links"], "self");
			$url = "https://my.rightscale.com" . $url . "/run_executable";
			$postVar = "right_script_href=https://my.rightscale.com/api/right_scripts/$scriptID";
			$returnCode = 202;
		}
		$this->curl($url, $postVar);
		if ($this->lastHttpCode != $returnCode ){
			return FALSE;
		}
		return TRUE;
	}

	/*
	* Rename server.
	*
	* @params      string  $ipAddress      IP address of the server.
	* @params      string  $newName        New nickname.
	*
	* @return      bool                    TRUE on success, FALSE ON FAILURE
	*/
	public function renameServer($ipAddress, $newName) {
		$ipAddress = preg_replace("/:.*/", "", $ipAddress);
		$postVar = "";
		$serverDetails = $this->getServerDetails($ipAddress);
		if($this->zCloud === FALSE){
			$url = $serverDetails["href"];
			$postVar = "server[nickname]={$newName}";

		} else {
			$url = $this->linkIterator($serverDetails["links"], "parent");
			$url = "https://my.rightscale.com" . $url ;
			$postVar = "server[name]=$newName";
		}
		$this->put($url, $postVar);
		if ($this->lastHttpCode != 204) {
			return FALSE;
		}
		return TRUE;
	}


	/*
	* Set input paramters on rightscale for a given IP address.
	*
	* @params      string  $ipAddress      IP address of the server.
	* @params      array   $inputs      	Array inputs with name of paramter as key and value as "value_type:value".
	* 					Ex. array("SLAVE" => "text:NONE");
	*
	* @return      bool                    TRUE on success, FALSE ON FAILURE
	*/
	public function setInputs($ipAddress, $inputs) {
		$ipAddress = preg_replace("/:.*/", "", $ipAddress);
		$postVar = "";
		$serverDetails = $this->getServerDetails($ipAddress);
		if($this->zCloud === FALSE){
			foreach ($inputs as $key => $value) {
				$url = $serverDetails["href"] . "/current";
				if ($postVar == "") {
					$postVar = 'server[parameters]['.$key.']='. $value;
				} else {
					$postVar = 'server[parameters]['.$key.']='. $value . '&&' . $postVar;
				}

			}
		} else {
			$url = $this->linkIterator($serverDetails["links"], "inputs");
			foreach ($inputs as $key => $value) {
				if ($postVar == "") {
					$postVar = 'inputs[][name]='.$key.'&inputs[][value]='. $value;
				} else {
					$postVar = 'inputs[][name]='.$key.'&inputs[][value]='. $value . '&&' . $postVar;
				}
			}
			$url = "https://my.rightscale.com" . $url . "/multi_update";
		}
		$this->put($url, $postVar);
		if ($this->lastHttpCode != 204) {
			return FALSE;
		}
		return TRUE;			
	}

	public function put($url, $postData){

		$cmd = 'curl -s --write-out %{http_code} --output /dev/null  -b ' . $this->cookieFile . ' -H ' . $this->apiVersion . ' -X PUT -d ' . "'" . $postData . "' " . $url;
		exec ($cmd,$output,$status);
		$this->lastHttpCode = $output[0];
		if (!$status) {
			return FALSE;
		}
		return TRUE;
	}

	/*
	* Get server info for given IP address.
	* @params      string  $ipAddress      IP address of the server.
	*
	*
	* @return      array                   Returns details of server.
	*/
	public function getServerDetails($ipAddress) {

		$ipAddress = preg_replace("/:.*/", "", $ipAddress);

		if ($this->zCloud === TRUE){
			$url = "https://my.rightscale.com/api/clouds/" . $this->cloudID . "/instances" . "?filter%5B%5D=private_ip_address==" . $ipAddress . "&view=full";
		} else {
			$url = "https://my.rightscale.com/api/acct/" . $this->acctID . "/servers" . "?filter%5B%5D=private_ip_address=" . $ipAddress . "&format=js";
		}

		$serverDetailsJson = $this->curl($url);
		if ($this->lastHttpCode != 200){
			return FALSE;
		}

		$serverDetails = json_decode($serverDetailsJson);
		$serverDetails = (array)$serverDetails[0];

		if ($this->zCloud === TRUE) {
			return $serverDetails;
		}

		$url = $serverDetails["href"] . "/current/settings?format=js";	
		$serverSettingsJson = $this->curl($url);
		$serverSettings = json_decode($serverSettingsJson);

		if ($this->lastHttpCode != 200){
			return FALSE;
		}

		return array_merge($serverDetails, (array)$serverSettings);
	}

	/*
	* Get server info for given server name
	* @params      string  $serverName      Name of the server.
	*
	* @return      array                   Returns details of server.
	*/
	public function getServerDetailsByName($serverName) {


		if ($this->zCloud === TRUE){
			$url = "https://my.rightscale.com/api/clouds/" . $this->cloudID . "/instances" . "?filter%5B%5D=name==" . $serverName . "&view=full";
		} else {
			$url = "https://my.rightscale.com/api/acct/" . $this->acctID . "/servers" . "?filter%5B%5D=nickname=" . $serverName . "&format=js";
		}

		$serverDetailsJson = $this->curl($url);
		if ($this->lastHttpCode != 200){
			return FALSE;
		}

		$serverDetails = json_decode($serverDetailsJson);
		$serverDetails = (array)$serverDetails[0];

		if ($this->zCloud === TRUE) {
			return $serverDetails;
		}

		$url = $serverDetails["href"] . "/current/settings?format=js";	
		$serverSettingsJson = $this->curl($url);
		$serverSettings = json_decode($serverSettingsJson);

		if ($this->lastHttpCode != 200){
			return FALSE;
		}

		return array_merge($serverDetails, (array)$serverSettings);
	}

	/*
	* Get server info for given IP address.
	* @params      string  $deploymentID      
	*
	*
	* @return      array                   Returns details of deployment.
	*/
	public function getDeployment($deploymentID) {



		if ($this->zCloud === TRUE){
			//$url = "https://my.rightscale.com/api/clouds/" . $this->cloudID . "/instances" . "?filter%5B%5D=deployment_href==" . "https://my.rightscale.com/acct/" . $this->acctID . "/deployments/" . $deploymentID;
			$url = "https://my.rightscale.com/api/clouds/" . $this->cloudID . "/instances" . "?filter%5B%5D=deployment_href==" . "https://my.rightscale.com/api/deployments/" . $deploymentID . "&view=full";
		} else {
			$url = "https://my.rightscale.com/api/acct/" . $this->acctID . "/deployments/" . $deploymentID . "/servers?format=js" ;
		}

		$serverDetailsJson = $this->curl($url);
		if ($this->lastHttpCode != 200){
			return FALSE;
		}

		$serverDetails = json_decode($serverDetailsJson);
		$serverDetails = (array)$serverDetails[0];

		return $serverDetails;
	}

	/*
	* Get server array  Details.
	* @params      string  $arrayID
	*
	*
	* @return      array   Returns details of Array.
	*/
	public function getArrayDetails($arrayID) {


		if ($this->zCloud === TRUE){
			//https://my.rightscale.com/api/server_arrays/15722/current_instances
			$url = "https://my.rightscale.com/api/server_arrays/" . $arrayID . "/current_instances";
		} else {
			//https://my.rightscale.com/api/acct/9236/server_arrays/6252/instances?format=js
			$url = "https://my.rightscale.com/api/acct/" . $this->acctID . "/server_arrays/" . $arrayID . "/instances?format=js" ;
		}

		$serverDetailsJson = $this->curl($url);
		if ($this->lastHttpCode != 200){
			return FALSE;
		}

		$serverDetails = json_decode($serverDetailsJson);
		$serverDetails = (array)$serverDetails;

		return $serverDetails;
	}

	/*
	* Get array servers IPs
	* @params      string  $arrayID
	*
	*
	* @return      array   Returns IPaddresses
	*/
	public function getArrayIPs($arrayID) {

		$arrayDetails = $this->getArrayDetails($arrayID);
		$IPs = array();

		if ($arrayDetails === FALSE) {
			return FALSE;
		}

		if ($this->zCloud === TRUE){
			foreach ($arrayDetails as $arrayDetail) {
				$IPs[] = $arrayDetail->private_ip_addresses[0];
			}
		} else {
			foreach ($arrayDetails as $arrayDetail) {
				$IPs[] = $arrayDetail->private_ip_address;
			}
		}

		return implode(",", $IPs);;
	}

	/*
	* Executes a curl call and returns the output. Sets $this->lastHttpCode with CURLINFO_HTTP_CODE.
	*
	* @params 	string	$url		Url to be curled.
	* @params	string	$postdata	Optional data to be posted.
	*
	* @returns	mixed			Retuns the output of the curl call.
	*/
	public function curl($url, $postdata = NULL) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($this->apiVersion));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->headerOnly) {
			curl_setopt($ch, CURLOPT_HEADER, true);
		}

		if ($postdata !== NULL) {
			if ($postdata == "rs_ec2_login") {
				curl_setopt($ch, CURLOPT_USERPWD, $this->userName . ":" . $this->password);
			} else {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			}

			if (is_string($postdata) && ($postdata == "rs_ec2_login" || stristr($postdata, "&account_href=/api/accounts/") !== FALSE )) {
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
			}
		}
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
		$data = curl_exec($ch);
		$this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $data;
	}
}

?>
