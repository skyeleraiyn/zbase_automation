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

class PeclSocket {
	private $sock;
	private $mb;
	private $port;
	private $proxy_string;

	public function __construct($mb, $port) {

		$this->mb = $mb;
		$this->port = $port;
		if (PROXY_RUNNING){
			if (!($this->sock = @socket_create(AF_UNIX, SOCK_STREAM, 0) and
				(socket_connect($this->sock, str_replace("unix://", "", PROXY_RUNNING), 0)))) {// '/var/run/mcmux/mcmux.sock'
				socket_close($this->sock);
				unset($this->sock);
			}
			$this->proxy_string = "A:$mb:$port ";
		} else {
			if (!($this->sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP) and
						(socket_connect($this->sock, $mb, $port)))) {
				socket_close($this->sock);
				unset($this->sock);
			}
			$this->proxy_string = "";
		}
		$dialgo = $this->proxy_string."options version=1.2.3.4 DIAlgo=crc32\r\n";

		$i = socket_send($this->sock, $dialgo, strlen($dialgo), 0);
		$buf = "";
		$i = socket_recv($this->sock, $buf, 100 , 0);

	}

	public function __destruct() {
		if($this->sock)
		unset($this->sock);
	}

	private function send( $send){
		$send = $this->proxy_string.$send;
		if (isset($this->sock)){
			socket_send($this->sock, $send, strlen($send), 0);
		}
	}

	private function read($size=10240){
		$buffer = "";
		if (isset($this->sock)) {
			$bytes = socket_recv($this->sock, $buffer, $size, 0);
			if ($bytes == 0){
				return 0;
			}	
			return $buffer;
		} 
	}

	private function closeSock(){
		if (isset($this->sock)){
			socket_close($this->sock);
			unset($this->sock);
		}
	}

	public function get($key){
		$this->send("get $key\r\n");
		$pos = false; 
		$buf = '';
		while ($pos === false) {
			$buf .=  $this->read();
			$pos = strpos($buf, "END");
		}

		//strpos to skip the header line in the response: VALUE....
		$body_offset = strpos($buf, "\r\n") + 2;
		$pos = strpos($buf , "END");
		return substr($buf, $body_offset, $pos- $body_offset -2);
	}

	public function set($key, $value, $flag=0, $expire =0, $algo=2, $crc=0, $crc2=-1) {
		$pat1 = '%04u:%08s';
		$pat2 = '%04u:%08s:%08s';
		if ($algo == 1) {
			$crc = "0001:";
		}elseif ($crc2 == -1){
			$crc = sprintf($pat1, $algo, $crc);
		}else {
			$crc = sprintf($pat2, $algo, $crc, $crc2);
		}
		$this->send("set $key $flag $expire ".strlen($value)." $crc\r\n$value\r\n");
		$buf = $this->read();

		if (strpos($buf, "STORED") !== false)
		return true;
		if (strpos($buf, "SERVER_ERROR checksum failed") !== false)
		return 'CHECKSUM_FAILED';

	}

	public function append($key, $value, $flag=0, $expire =0, $algo=2, $crc=0, $crc2=-1) {
		$pat1 = '%04u:%08s';
		$pat2 = '%04u:%08s:%08s';
		if ($algo == 1) {
			$crc = "0001:";
		}elseif ($crc2 == -1){
			$crc = sprintf($pat1, $algo, $crc);
		}else {
			$crc = sprintf($pat2, $algo, $crc, $crc2);
		}
		$this->send("append $key $flag $expire ".strlen($value)." $crc\r\n$value\r\n");
		$buf = $this->read();

		if (strpos($buf, "STORED") !== false)
		return true;
		if (strpos($buf, "SERVER_ERROR checksum failed") !== false)
		return 'CHECKSUM_FAILED';

	}


	public function prepend($key, $value, $flag=0, $expire =0, $algo=2, $crc=0, $crc2=-1) {
		$pat1 = '%04u:%08s';
		$pat2 = '%04u:%08s:%08s';
		if ($algo == 1) {
			$crc = "0001:";
		}elseif ($crc2 == -1){
			$crc = sprintf($pat1, $algo, $crc);
		}else {
			$crc = sprintf($pat2, $algo, $crc, $crc2);
		}
		$this->send("prepend $key $flag $expire ".strlen($value)." $crc\r\n$value\r\n");
		$buf = $this->read();
		if (strpos($buf, "STORED") !== false)
		return true;
		if (strpos($buf, "SERVER_ERROR checksum failed") !== false)
		return 'CHECKSUM_FAILED';

	}
}

?>
