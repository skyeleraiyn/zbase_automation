<?php

class Data_generation{

	public function generate_data($object_size){
		$UserData = "GAME_ID_#@";
		while(1){
			if(strlen($UserData) >= $object_size) 
				break;
			else
				$UserData = $UserData.rand(11111, 99999);	
		}
		return serialize($UserData);
	}
}
?>