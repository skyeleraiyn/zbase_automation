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

class SocketWrapper
{
        var $host;
        var $port;
        var $socket;

        function __construct($h,$p)
        {
		$this->host = $h;
		$this->port = $p;
        }
        function sendData($data)                        // Encode and send the JSON data
        {
		$len = strlen($data);
		$str = pack("N",$len);
		$str = $str.$data;

		$len_sent = 0;
		while($len_sent < $len+4)
		{
			$len_sent = @socket_write($this->socket,substr($str,$len_sent),$len+4 - $len_sent);
			if($len_sent === FALSE)
				return(FALSE);
		}	
        	return($len_sent);
	}
	function readData($len,$timeout)				// Decode (if $len=4) and return associative array (in case of string)
        {
		$chunk = "";
		$data = "";
		$start_time = time();

		while(strlen($data) < $len)
		{
			$diff = time() - $start_time;
			if($diff <= $timeout)
			{
				$status =  @socket_recv($this->socket,$chunk,$len - strlen($data),MSG_DONTWAIT);
			//	echo $status;
				if($status === 0)
					return(FALSE);
				else if($status !== FALSE)
				{
					$data = $data.$chunk;
				}
			}
			else
				return(" ");
		}
	
		if($len == 4)
		{
			$array = unpack("NDATA",$data);
			return($array['DATA']);
		}
		else
			return ($data);	
		
	}
	function set_nonBlock()
	{
		socket_set_nonblock($this->socket);
	}
        function connect($ip=0,$port=null)				//Create Socket and Bind the Source IP (only for VBA) before connecting to VBS [-1=ERROR, 0=CONNECTED]
        {
		if(FALSE != ($this->socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)))
		{
			socket_set_option($this->socket,SOL_SOCKET,SO_REUSEADDR,1);
			if($port != null)
			{
				if(FALSE != socket_bind($this->socket,$ip,$port));
				else{
						log_function::debug_log("could not connect socket:".$this->socket." on host:".$this->host." on port:".$this->port);

						return(FALSE);
			}
			}
			else
			{
				if(FALSE != socket_bind($this->socket,$ip));
				else {
					log_function::debug_log("could not connect socket:".$this->socket." on host:".$this->host." on port:".$this->port);
					return(FALSE);
			}
			}
	
			if(@socket_connect($this->socket,$this->host,(int)$this->port) != FALSE)
				return(TRUE);
			else {
				log_function::debug_log("could not connect socket:".$this->socket." on host:".$this->host." on port:".$this->port);
				return(FALSE);	
			}
		}
		else
			log_function::debug_log("could not connect socket:".$this->socket." on host:".$this->host." on port:".$this->port);

			return(-1);
        }
        function settimeout($timeout)			//To set the Socket Timeout [RETURN VALUE OF stream_set_timeout()] 
        {
		return(stream_set_timeout($this->socket,$timeout));
        }
	function close_socket()
	{
		socket_close($this->socket);
	}
	
}
?>
