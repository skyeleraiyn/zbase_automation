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
global $moxi_machine;
global $no_of_keys;
global $value_size;
class Pump_class{
	protected $machine_array;
	protected $port;
	protected $no_of_keys;
	protected $value_size;
	public function __construct(){
		global $moxi_machine;
		global $no_of_keys;
		global $value_size;
		$this->machine_array = MOXI_MACHINE;
		//$this->machine_array=array("10.36.168.173");
		$this->port=MOXI_PORT;
		$this->total_no_of_keys = NO_OF_KEYS;
		$this->total_value_size = VALUE_SIZE;
		}
	
	
	public function pump(){
		/*$mc=new Memcache;
		$mc->addserver($this->machine_array,$this->port);	
		$value='#';
		for( $i=0; $i<$this->total_value_size; $i++ )
		{
			$value=$value.'#';
		}
		
		for ($i=0;$i<$this->total_no_of_keys;$i++)
		{		
			@$mc->set('testkey'.$i,$value);	
		}*/
		shell_exec("php misc/pump.php -i $this->machine_array -n $this->total_no_of_keys -p $this->port -s $this->total_value_size");
		
	}
}
?>
