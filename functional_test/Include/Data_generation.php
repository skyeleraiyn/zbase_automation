<?php

class Data_generation{

	private static $_dataFlags=array();
	private static $_dataKeys=array();
	private static $_dataKeyValues=array();
	private static $_dataKeyValueFlags=array();
	private static $_dataKeySerializeValueFlags=array();
	private static $_dataKeyValueFlags_old_set=array();
	private static $_dataArrayKeyArrayValueFlags=array();
	private static $_dataKeyAsciiValueFlags=array();

	public static function getflags(){
		
		$old_flags = array(
			0,
			MEMCACHE_COMPRESSED, 
			MEMCACHE_COMPRESSED_LZO,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO,
			0x50	// dummy flag
		); 
		
		$old_serialize_flags = array(
			1,
			MEMCACHE_COMPRESSED | 1, 
			MEMCACHE_COMPRESSED_LZO | 1,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | 1
		); 
		
		if(TEST_IGBINARY_FLAGS){

			$new_flags = array(
				MEMCACHE_COMPRESSED_BZIP2,
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2,
				MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2
			); 
			
			$new_serialize_flags = array(
				MEMCACHE_SERIALIZED_IGBINARY,
				MEMCACHE_COMPRESSED_BZIP2 | 1,
				MEMCACHE_COMPRESSED | MEMCACHE_SERIALIZED_IGBINARY,
				MEMCACHE_COMPRESSED_LZO | MEMCACHE_SERIALIZED_IGBINARY,
				MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,	
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2 | 1,
				MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | 1,
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | 1,
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_SERIALIZED_IGBINARY,
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,
				MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,
				MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY
			); 
			
			return array(array_merge($old_flags, $new_flags), array_merge($old_serialize_flags, $new_serialize_flags), $old_flags);	 
		}	
		return array($old_flags, $old_serialize_flags, $old_flags);
	}
	
	
	public function provideKeys() {
		// dataKeys
		if(empty(self::$_dataKeys)){	
			self::$_dataKeys[] = array(uniqid('key_'));
		}
		return self::$_dataKeys;
	}

	public function provideKeyValues() {
			// dataKeyValues
		if(empty(self::$_dataKeyValues)){	
			$value = self::makeData();
			self::$_dataKeyValues[] = array(uniqid('key_'), $value);
			$value = chr(255) . $value . chr(254);
			self::$_dataKeyValues[] = array(uniqid('key_'), $value);
		}
		return self::$_dataKeyValues;
	}

	public function provideFlags() {
		// dataFlags
		if(empty(self::$_dataFlags)){
			$flag_list = self::getflags();
			self::$_dataFlags = $flag_list[0];
		}
		return self::$_dataFlags;
	}
	
	public function provideKeyValueFlags() {
			// dataKeyValueFlags
		if(empty(self::$_dataKeyValueFlags)){
		
			$flag_list = self::getflags();
			$compression_flags = $flag_list[0];
			$serialize_flags = $flag_list[1];
		
			foreach ($serialize_flags as $flag) {
				// mock textual values
				$value = self::getUserializedData();
				self::$_dataKeyValueFlags[] = array(uniqid('key_'), $value, $flag);	
			}		
			foreach ($compression_flags as $flag) {
				// mock textual values
				$value = self::makeData();
				self::$_dataKeyValueFlags[] = array(uniqid('key_'), $value, $flag);
				
				// mock binary values
				$value = chr(255) . $value . chr(254);
				self::$_dataKeyValueFlags[] = array(uniqid('key_'), $value, $flag);
			}
		}
		return self::$_dataKeyValueFlags;
	}	

	
		

	public function provideKeyValueSerializeFlags(){
	
		if(empty(self::$_dataKeySerializeValueFlags)){
					// dataKeySerializeValueFlags
					
			$flag_list = self::getflags();
			$compression_flags = $flag_list[0];
			
			foreach ($compression_flags as $flag) {
				// mock textual values
				$value = self::makeData();
				self::$_dataKeySerializeValueFlags[] = array(uniqid('key_'), $value, $flag);
				
				// mock binary values
				$value = chr(255) . $value . chr(254);
				self::$_dataKeySerializeValueFlags[] = array(uniqid('key_'), $value, $flag);

			}
		}
		return self::$_dataKeySerializeValueFlags;
	}	
	
	
	public function provideKeyValueFlags_old_set() {
		
		if(empty(self::$_dataKeyValueFlags_old_set)){
				// dataKeyValueFlags_old_set
			
			$flag_list = self::getflags();
			$old_flags = $flag_list[2];
			
			foreach ($old_flags as $flag) {
				// mock textual values
				$value = self::makeData();
				self::$_dataKeyValueFlags_old_set[] = array(uniqid('key_'), $value, $flag);
				
				// mock binary values
				$value = chr(255) . $value . chr(254);
				self::$_dataKeyValueFlags_old_set[] = array(uniqid('key_'), $value, $flag);
			}
		}
		return self::$_dataKeyValueFlags_old_set;
	}	
	
	public function provideArrayKeyArrayValueFlags() {
	
	
		if(empty(self::$_dataArrayKeyArrayValueFlags)){
				// dataArrayKeyArrayValueFlags
				
			$flag_list = self::getflags();
			$compression_flags = $flag_list[0];
			$serialize_flags = $flag_list[1];			
				
			foreach ($serialize_flags as $flag) {
				
				// mock textual values
				$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
				$value = array(self::getUserializedData(), self::getUserializedData(), self::getUserializedData());
				self::$_dataArrayKeyArrayValueFlags[] = array($key, $value, $flag);
				
			}		
			foreach ($compression_flags as $flag) {
				
				// mock textual values
				$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
				$value1 = self::makeData();
				$value2 = self::makeData();
				$value3 = self::makeData();
				$value = array($value1, $value2, $value3);
				self::$_dataArrayKeyArrayValueFlags[] = array($key, $value, $flag);
				
				// mock binary values
				$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
				$value = array(chr(255).$value1.chr(254), chr(255).$value2.chr(254), chr(255).$value3.chr(254));
				self::$_dataArrayKeyArrayValueFlags[] = array($key, $value, $flag);
			}
		}
		return self::$_dataArrayKeyArrayValueFlags;
	}

	public function provideKeyAsciiValueFlags() {
		if(empty(self::$_dataKeyAsciiValueFlags)){
				// dataKeyAsciiValueFlags	
				
			$flag_list = self::getflags();
			$compression_flags = $flag_list[0];
				
			foreach ($compression_flags as $flag) {
				
				// mock textual values
				$value = self::makeData();
				self::$_dataKeyAsciiValueFlags[] = array(uniqid('key_'), $value, $flag);
				
			}	
		}
		return self::$_dataKeyAsciiValueFlags;
	}
	

	public static function PrepareHugeData($no_of_keys = 200, $key_size = 0){
		
		$key_list = array();
		$value_list = array();
		$append_to_key_name = "";
		for ($count = 0; $count < $key_size - 20; $count = $count + 20) {
			$append_to_key_name = $append_to_key_name.uniqid('testkey');
		}
		for ($count = $no_of_keys; $count; --$count) {
			$key_list[] = uniqid('testkey').$append_to_key_name;
			$value_list[] = self::makeData();
		}
		return array($key_list, $value_list);	
	}
	
	public static function makeData() {

		return serialize(self::getUserializedData());
	}


	public static function getUserializedData(){
		
		$blob = array();
		$fieldCount = mt_rand(10, 20);
		
		for (; $fieldCount; --$fieldCount) {
			$fieldName = uniqid("field_");
			$fieldValue = uniqid("value_");
			
			if (mt_rand() % 3) {
				$count = mt_rand(5, 10);
				$fieldValue = array();
				for (; $count; --$count) {
					$fieldValue[] = mt_rand(0,65536);
				}
			}
			
			$blob[$fieldName] = $fieldValue;
		}
		return $blob;
	}
	



	

	

	public function delete_keys($number_of_keys_to_be_deleted, $key_start_id, $chk_max_items = NULL){

		$instance = Connection::getMaster();
		if($chk_max_items){
			$counter_chk_max_items = $chk_max_items;
			$open_checkpoint_id = stats_functions::get_open_checkpoint_id(TEST_HOST_1);
		}
		for($inum_keys=0 ; $inum_keys<$number_of_keys_to_be_deleted ; $inum_keys++){

			$instance->delete("testkey_".$key_start_id);
			$key_start_id++;
			if($chk_max_items){
				if($counter_chk_max_items == 1){
					for($iattempt_check_checkpoint_closure=0; $iattempt_check_checkpoint_closure<10 ; $iattempt_check_checkpoint_closure++){
						$temp_open_checkpoint_id = stats_functions::get_open_checkpoint_id(TEST_HOST_1);
						if($temp_open_checkpoint_id == $open_checkpoint_id + 1){
							$open_checkpoint_id = $temp_open_checkpoint_id;
							break;
						}
							// Failed to close checkpoint after 5 seconds
						if($iattempt_check_checkpoint_closure == 9) return False;
						usleep(500000);
					}					
					$counter_chk_max_items = $chk_max_items;
				} else {
					$counter_chk_max_items--;
				}
			}
		}
		return True;	
	}
	
	public function add_keys($number_of_keys_to_be_pumped, $chk_max_items = NULL, $key_start_id = 0, $object_size = 1024) {

		$instance = Connection::getMaster();
		$value = self::generate_data($object_size);
		if($chk_max_items){
			$counter_chk_max_items = $chk_max_items;
			$open_checkpoint_id = stats_functions::get_open_checkpoint_id(TEST_HOST_1);
		}
		for($inum_keys=0 ; $inum_keys<$number_of_keys_to_be_pumped ; $inum_keys++){
			if($key_start_id){
				$instance->set("testkey_".$key_start_id, $value);
				$key_start_id++;
			} else {
				$instance->set(uniqid("testkey_"), $value);
			}
			if($chk_max_items){
				if($counter_chk_max_items == 1){
					for($iattempt_check_checkpoint_closure=0; $iattempt_check_checkpoint_closure<10 ; $iattempt_check_checkpoint_closure++){
						$temp_open_checkpoint_id = stats_functions::get_open_checkpoint_id(TEST_HOST_1);
						if($temp_open_checkpoint_id == $open_checkpoint_id + 1){
							$open_checkpoint_id = $temp_open_checkpoint_id;
							break;
						}
							// Failed to close checkpoint after 5 seconds
						if($iattempt_check_checkpoint_closure == 9) return False;
						usleep(500000);
					}					
					$counter_chk_max_items = $chk_max_items;
				} else {
					$counter_chk_max_items--;
				}
			}
		}
		return True;
	}

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

class ComplexObject{
    
	private $value;
	private $set_sleep = 5;
    private $get_sleep = 5;
    // constructor 
    public function __construct($testvalue){
		$this->value=$testvalue;
	}
			// This gets executed during serialize
    public function __sleep(){
        sleep($this->set_sleep);
        return array('value');
    }
		// This gets executed during un-serialize
    public function __wakeup(){
        sleep($this->get_sleep);
        return array('value');
    }
}
?>