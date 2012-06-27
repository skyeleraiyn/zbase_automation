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
	private static $_dataArrayKeyArrayAsciiValueFlags=array();
	
	
	
	public static function prepareData($feature_check = NULL) {

		
		$flags = array(
		0,
		MEMCACHE_COMPRESSED, 
		MEMCACHE_COMPRESSED_LZO,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO,
		0x50	// dummy flag
		); 
		$old_flags = $flags;
		
		$serialize_flags = array(
		1,
		MEMCACHE_COMPRESSED | 1, 
		MEMCACHE_COMPRESSED_LZO | 1,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | 1,
		); 
		
		if($feature_check == "igbinary"){

			$new_flags = array(
			MEMCACHE_COMPRESSED_BZIP2,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2,
			MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,
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
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,
			); 
			
			$flags = array_merge($flags, $new_flags);
			$serialize_flags = array_merge($serialize_flags, $new_serialize_flags);	 
		}

		
		
		// dataFlags
		foreach ($flags as $flag) {
			self::$_dataFlags[] = array($flag);
		}
		// dataKeys
		$key = uniqid('key_');
		self::$_dataKeys[] = array($key);
		
		// dataKeyValues
		$key = uniqid('key_');
		$value = self::makeData();
		self::$_dataKeyValues[] = array($key, $value);
		$key = uniqid('key_');
		$value = chr(255) . self::makeData() . chr(254);
		self::$_dataKeyValues[] = array($key, $value);

		// dataKeyValueFlags
		foreach ($serialize_flags as $flag) {
			
			// mock textual values
			$key = uniqid('key_');
			$value = self::getUserializedData();
			self::$_dataKeyValueFlags[] = array($key, $value, $flag);
			
		}		
		foreach ($flags as $flag) {
			
			// mock textual values
			$key = uniqid('key_');
			$value = self::makeData();
			self::$_dataKeyValueFlags[] = array($key, $value, $flag);
			
			// mock binary values
			$key = uniqid('key_');
			$value = chr(255) . self::makeData() . chr(254);
			self::$_dataKeyValueFlags[] = array($key, $value, $flag);

		}

		// dataKeySerializeValueFlags
		foreach ($flags as $flag) {
			
			// mock textual values
			$key = uniqid('key_');
			$value = self::makeData();
			self::$_dataKeySerializeValueFlags[] = array($key, $value, $flag);

			
			// mock binary values
			$key = uniqid('key_');
			$value = chr(255) . self::makeData() . chr(254);
			self::$_dataKeySerializeValueFlags[] = array($key, $value, $flag);

		}
		
		// dataKeyValueFlags_old_set
		foreach ($old_flags as $flag) {
			
			// mock textual values
			$key = uniqid('key_');
			$value = self::makeData();
			self::$_dataKeyValueFlags_old_set[] = array($key, $value, $flag);

			
			// mock binary values
			$key = uniqid('key_');
			$value = chr(255) . self::makeData() . chr(254);
			self::$_dataKeyValueFlags_old_set[] = array($key, $value, $flag);

		}

		// dataArrayKeyArrayValueFlags
		foreach ($serialize_flags as $flag) {
			
			// mock textual values
			$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
			$value = array(self::getUserializedData(), self::getUserializedData(), self::getUserializedData());
			self::$_dataArrayKeyArrayValueFlags[] = array($key, $value, $flag);
			
		}		
		foreach ($flags as $flag) {
			
			// mock textual values
			$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
			$value = array(self::makeData(), self::makeData(), self::makeData());
			self::$_dataArrayKeyArrayValueFlags[] = array($key, $value, $flag);
			
			// mock binary values
			$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
			$value = array(chr(255).self::makeData().chr(254), chr(255).self::makeData().chr(254), chr(255).self::makeData().chr(254));
			self::$_dataArrayKeyArrayValueFlags[] = array($key, $value, $flag);
		}

		// dataKeyAsciiValueFlags		
		foreach ($flags as $flag) {
			
			// mock textual values
			$key = uniqid('key_');
			$value = self::makeData();
			self::$_dataKeyAsciiValueFlags[] = array($key, $value, $flag);
			
		}		

		// dataArrayKeyArrayAsciiValueFlags
		foreach ($serialize_flags as $flag) {
			
			// mock textual values
			$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
			$value = array(self::getUserializedData(), self::getUserializedData(), self::getUserializedData());
			self::$_dataArrayKeyArrayAsciiValueFlags[] = array($key, $value, $flag);  
			
		}		
		foreach ($flags as $flag) {
			
			// mock textual values
			$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
			$value = array(self::makeData(), self::makeData(), self::makeData());
			self::$_dataArrayKeyArrayAsciiValueFlags[] = array($key, $value, $flag);
		}
		
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
	
	public function provideKeys() {
		return self::$_dataKeys;
	}

	public function provideKeyValues() {
		return self::$_dataKeyValues;
	}

	public function provideFlags() {
		return self::$_dataFlags;
	}
	
	public function provideKeyValueFlags() {
		return self::$_dataKeyValueFlags;
	}
	public function provideKeyValueSerializeFlags() {
		return self::$_dataKeySerializeValueFlags;
	}	
	
	
	public function provideKeyValueFlags_old_set() {
		return self::$_dataKeyValueFlags_old_set;
	}	
	
	public function provideArrayKeyArrayValueFlags() {
		return self::$_dataArrayKeyArrayValueFlags;
	}

	public function provideKeyAsciiValueFlags() {
		return self::$_dataKeyAsciiValueFlags;
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