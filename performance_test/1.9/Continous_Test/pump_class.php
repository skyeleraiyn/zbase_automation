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
