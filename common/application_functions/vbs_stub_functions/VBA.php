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

class VBA extends Agent
{
        function __construct($h,$p,$id)
        {
                parent::__construct('VBA',$h,$p,$id);
        }
        function SendFail($destination)
        {
		$data = array();
		$data['Cmd'] = 'FAIL';
		$data['Destination'] = $destination;

                return($this->socketWrap->sendData(json_encode($data)));
        }
        function SendCapacityUpdate($capacity)
        {
		$data = array();
		$data['Cmd'] = 'CapacityUpdate';
		$data['DiskAlive'] = 14;
	
		return($this->socketWrap->sendData(json_encode($data)));
        }
        function SendDeadVbuckets($active,$replica,$no_of_discs)
        {
		$data = array();
		$data['Cmd'] = 'DEAD_VBUCKETS';
		$data['Status'] = 'ERROR';
		$data['Vbuckets'] = array();
		$data['Vbuckets']['Active'] = $active;
		$data['Vbuckets']['Replica'] = $replica;
		$data['DiskFailed'] = $no_of_discs;

		return($this->socketWrap->sendData(json_encode($data)));
        }
}

?>
