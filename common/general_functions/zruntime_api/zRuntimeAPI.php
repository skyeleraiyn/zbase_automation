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

/*
* Interface for zRT API
* This class is a wrapper over the zRT API.
* The constructor would not test the credentials. 
* Every request is standalone and the class is will
* to store the common information.
* 
* @author: Mahesh Gattani (mgattani@zynga.com)
*/

class zRuntimeAPI {

	const API_URL = 'https://api.runtime.zynga.com:8994';

	protected $username = '';
	protected $password = '';
	protected $game = '';
	protected $environment = '';
	protected $url = '';

	public function __construct($username, $password, $game, $environment) {

		$this->username = $username;
		$this->password = $password;
		$this->game = $game;
		$this->environment = $environment;		
		$this->url = self::API_URL. "/" . $game . '/' . $environment . '/';
	}

	/*
	* Verify if the given array is associative or not.
	*/
	function isAssoc($arr){

		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	/*
	* Verify if the given array is indexed or not.
	*/
	function isIndexed($arr){

		return array_keys($arr) === range(0, count($arr) - 1);
	}

	/*
	* Common curl function. 
	* @parameter   $request        string              'GET' or 'POST'
	* @parameter   $additionalUrl  string              'changelog', 'current' or <revision>
	* @parameter   $filter         associative array   Information to be passed to the server. Can be filter or data in case of post requests
	* 
	* @return      $output         JSON                The json object (Server output)
	*/
	protected function doCurl($request, $additionalUrl, $filter){

		$handle = curl_init();
		curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $request);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($handle, CURLOPT_TIMEOUT, 0);
		curl_setopt($handle, CURLOPT_URL, $this->url . $additionalUrl);
		curl_setopt($handle, CURLOPT_USERPWD, $this->username . ':' . $this->password);

		if ( count($filter) != 0 ){
			curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($filter));
		}

		$data = curl_exec($handle);
		$output = json_decode($data, true);

		return $output;
	}

	/*
	* Get the current blob for the mentioned kets and at given time
	* @parameter   $keys   Indexed array       Keys for which the value is queried
	* @parameter   $time   Number              Unix Epoch timestamp
	* 
	* @return      $output Associative array   The output containing the revision and the blob
	* @return      error   Associative array   Failure cases
	*/
	public function zRTGetLive($keys = null, $time = null){

		if( $keys != null && !$this->isIndexed($keys) ){
			return array('error' => 'Keys should be an indexed array (not associative) of keys. Please provide input accordingly.');
		}		

		$filter = array();
		if( $keys != null ){
			$filter['key'] = $keys;
		}
		if( $time != null ){
			$filter['time'] = $time;
		}

		$output = $this->doCurl('GET', 'current', $filter);

		if( !isset($output['status']) ){
			return array('error' => 'Credentials provided dont have the required permissions.');	
		}
		if( $output['status'] != 0 ){
			return array('error' => $output['output']);
		}

		return array( 'rev' => $output['rev'], 'output' => $output['output'] );
	}

	public function zRTGetLiveForKeys($keys){
		return $this->zRTGetLive($keys);
	}

	public function zRTGetLiveOnTime($time){
		return $this->zRTGetLive(null, $time);
	}

	/*
	* Get the current blob for the mentioned kets and at given time
	* @parameter   $revision   Number          The revision for which the blob is requested
	* @parameter   $keys       Indexed array   Keys for which the value is queried 
	* 
	* @return      $output     Associative array   The output containing the revision and the blob
	* @return      error       Associative array   Failure cases
	*/
	public function zRTGetRevision($revision, $keys = null){

		if( $keys != null && !$this->isIndexed($keys) ){
			return array('error' => 'Keys should be an indexed array (not associative) of keys. Please provide input accordingly.');
		}

		$filter = array();
		if( $keys != null ){
			$filter['key'] = $keys;
		}

		$output = $this->doCurl('GET', $revision, $filter);

		if( !isset($output['status']) ){
			return array('error' => 'Credentials provided dont have the required permissions.');       
		}
		if( $output['status'] != 0 ){
			return array('error' => $output['output']);
		}

		return array( 'rev' => $output['rev'], 'output' => $output['output'] );				
	}

	/*
	* Get the current blob for the mentioned kets and at given time
	* @parameter   $keys           Indexed array       Keys for which the value is queried 
	* @parameter   $user           string                  User for which the value is queried 
	* @parameter   $starttime      Unix Epoch timestamp    time after which the value is queried 
	* @parameter   $endtime        Unix Epoch timestamp    time before which the value is queried 
	* @parameter   $count          Number                  Count of output neede. Default is 10 
	* 
	* @return      $output     Associative array   The output containing the changelog
	* @return      error       Associative array   Failure cases
	*/
	public function zRTGetChangeLog($keys = null, $user = null, $starttime = null, $endtime = null, $count = 10){

		if( $keys != null && !$this->isIndexed($keys) ){
			return array('error' => 'Keys should be an indexed array (not associative) of keys. Please provide input accordingly.');
		}

		$filter = array();
		if( $keys != null ){
			$filter['key'] = $keys;
		}
		if( $user != null ){
			$filter['user'] = $user;
		}
		if( $starttime != null ){
			$filter['start'] = $starttime;
		}
		if( $endtime != null ){
			$filter['end'] = $endtime;
		}
		if( $count != null ){
			$filter['count'] = $count;
		}

		$output = $this->doCurl('GET', 'changelog', $filter);

		if( !isset($output['status']) ){
			return array('error' => 'Credentials provided dont have the required permissions.');       
		}
		if( $output['status'] != 0 ){
			return array('error' => $output['output']);
		}

		return array('output' => $output['output']);
	}

	public function zRTGetChangeLogForKeys($keys, $count = 10){
		return $this->zRTGetChangeLog($keys, null, null, null, $count);
	}

	public function zRTGetChangeLogForUser($user, $count = 10){
		return $this->zRTGetChangeLog(null, $user, null, null, $count);
	}

	public function zRTGetChangeLogForPeriod($starttime, $endtime, $count = 10){
		return $this->zRTGetChangeLog(null, null, $starttime, $endtime, $count);
	}

	public function zRTGetChangeLogWithSize($count){
		return $this->zRTGetChangeLog(null, null, null, null, $count);
	}

	/*
	* Get the current blob for the mentioned kets and at given time
	* @parameter   $revision       Number                  The revision for which post is made
	* @parameter   $adds           associative array       Keys to be added 
	* @parameter   $updates        associative array       Keys to be updated 
	* @parameter   $deletes        associative array       Keys to be deleted
	*  
	* @return      return          boolean                 True
	* @return      error           associative array       Failure cases
	*/
	public function zRTPost($revision, $adds = null, $updates = null, $deletes = null){

		if( $adds != null && !$this->isAssoc($adds) ){
			return array('error' => 'Adds is not an assosiative. Please send an associative array as the input');
		}
		if( $updates != null && !$this->isAssoc($updates) ){
			return array('error' => 'Updates is not an assosiative. Please send an associative array as the input');
		}
		if( $deletes != null && !$this->isAssoc($deletes) ){
			return array('error' => 'Deletes is not an associative. Please send an associative array as the input');
		}

		$filter = array();
		if( $adds != null ){
			$filter['add'] = $adds;
		}
		if( $updates != null ){
			$filter['update'] = $updates;
		}
		if( $deletes != null ){
			$filter['delete'] = $deletes;
		}

		$output = $this->doCurl('POST', $revision, $filter);

		if( !isset($output['status']) ){
			return array('error' => 'Credentials provided dont have the required permissions.');       
		}
		if( $output['status'] != 0 ){
			return array('error' => $output['output']);
		}

		return true;
	}

	public function zRTAddKeys($revision, $adds){
		return $this->zRTPost($revision, $adds);
	}

	public function zRTUpdateKeys($revision, $updates){
		return $this->zRTPost($revision, null, $updates);
	}

	public function zRTDeleteKeys($revision, $deletes){
		return $this->zRTPost($revision, null, null, $deletes);
	}

}

?>
