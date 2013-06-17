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
