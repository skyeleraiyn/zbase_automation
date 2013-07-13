<?php

class Data_generation{
	public static $GetlInputs = array(
	'GetlFormatBasic' => '1',
	'GetlNoTimeout' => '2',
	'GetlNoMetadata' => '3',
	'GetlFormatFull' => '4',
	);

	private static $_dataFlags=array();
	private static $_dataKeys=array();
	private static $_dataKeyValues=array();
	private static $_dataKeyValueFlags=array();
	private static $_dataKeySerializeValueFlags=array();
	private static $_dataKeyValueFlags_old_set=array();
	private static $_dataArrayKeyArrayValueFlags=array();
	private static $_dataKeyAsciiValueFlags=array();
	private static $_dataArrayKeyArrayValueFlagsOpts=array();
	private static $_dataKeyOpts = array();


	public static function getflags(){
		$old_compression_flags = array(
		0,
		MEMCACHE_COMPRESSED,
		MEMCACHE_COMPRESSED_LZO,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO
		);

		$compression_flags = array(
		0,
		MEMCACHE_COMPRESSED,
		MEMCACHE_COMPRESSED_LZO,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO,
		MEMCACHE_COMPRESSED_BZIP2,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2,
		MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,
		0x50	// dummy flag
		);

		$serialize_flags = array(
		1,
		MEMCACHE_COMPRESSED | 1,
		MEMCACHE_COMPRESSED_LZO | 1,
		MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | 1,
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

		return array($compression_flags, $serialize_flags, $old_compression_flags);
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

	public function provideArrayKeyArrayValueFlagsOpts() {
		if (empty(self::$_dataArrayKeyArrayValueFlagsOpts)) {
			$d = self::provideArrayKeyArrayValueFlags();
			foreach($d as $ent) {
				foreach (self::$GetlInputs as $k=>$v) {
					$entry = $ent;
					$entry[] = $v;
					self::$_dataArrayKeyArrayValueFlagsOpts[] = $entry;
				}
			}
		}
		return self::$_dataArrayKeyArrayValueFlagsOpts;
	}

	public function provideArrayKeyOpts() {
		$key = array(uniqid('key_'), uniqid('key_'), uniqid('key_'));
		foreach (self::$GetlInputs as $k=>$v) {
			self::$_dataKeyOpts[] = array($key, $v);
		}
		return self::$_dataKeyOpts;
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
		log_function::debug_log("delete_keys keys:$number_of_keys_to_be_deleted start:$key_start_id chk_max:$chk_max_items ");

		$instance = Connection::getMaster();
		if($chk_max_items){
			$counter_chk_max_items = $chk_max_items;
			$open_checkpoint_id = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		}
		for($inum_keys=0 ; $inum_keys<$number_of_keys_to_be_deleted ; $inum_keys++){

			$instance->delete("testkey_".$key_start_id);
			$key_start_id++;
			if($chk_max_items){
				if($counter_chk_max_items == 1){
					for($iattempt_check_checkpoint_closure=0; $iattempt_check_checkpoint_closure<10 ; $iattempt_check_checkpoint_closure++){
						$temp_open_checkpoint_id = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
						if($temp_open_checkpoint_id == $open_checkpoint_id + 1){
							$open_checkpoint_id = $temp_open_checkpoint_id;
							break;
						}
						// Failed to close checkpoint after 20 seconds
						if($iattempt_check_checkpoint_closure == 9) return False;
						sleep(2);
					}
					$counter_chk_max_items = $chk_max_items;
				} else {
					$counter_chk_max_items--;
				}
			}
		}
		return True;
	}

	public function verify_added_keys($remote_machine, $number_of_keys_to_be_verified, $object_value, $key_start_id = 0){
		log_function::debug_log("verify_added_keys remote_machine:$remote_machine keys:$number_of_keys_to_be_verified object_value:$object_value");
		$instance = Connection::getConnection($remote_machine);

		for($inum_keys=0 ; $inum_keys<$number_of_keys_to_be_verified ; $inum_keys++){
			$key = "testkey_".$key_start_id;
			$key_start_id++;
			$value = $key."_".$object_value;
			$get_output = $instance->get($key);
			if($get_output <> $value){
				log_function::debug_log("Failed to verify blob value for $key: Acutual: $get_output Expected: $value");
				return False;
			}
		}
		return True;
	}


	public function pump_keys_to_cluster($number_of_keys_to_be_pumped, $chk_max_items = NULL, $tolerance = 1) {
		global $moxi_machines;
                if($chk_max_items and ($number_of_keys_to_be_pumped % (NO_OF_VBUCKETS))) {
                        log_function::debug_log("total keys should be a multiple of the number of vbuckets for checkpoints to close properly");
                        return False;
                }
		if($chk_max_items) {
		      $expected_min_diff = ($number_of_keys_to_be_pumped/($chk_max_items * NO_OF_VBUCKETS))-$tolerance;
              $expected_max_diff = ($number_of_keys_to_be_pumped/($chk_max_items * NO_OF_VBUCKETS))+$tolerance;
                      for($vb_id = 0; $vb_id < NO_OF_VBUCKETS; $vb_id++) {
                                $machine[$vb_id] = vba_functions::get_machine_from_id_active($vb_id);
                                if(!$machine[$vb_id]) {
                                log_function::debug_log("vb_"."$vb_id not found in cluster");
                                return False;
                                }
                                $open_checkpoint_id[$vb_id] = stats_functions::get_checkpoint_stats($machine[$vb_id], "open_checkpoint_id", $vb_id);
			}
		}
		$command_output = remote_function::remote_execution($moxi_machines[0], "php /tmp/pump.php -i 0 -n $number_of_keys_to_be_pumped -p ".MOXI_PORT_NO);
		if(!stristr($command_output, "Success")) {
			log_function::debug_log("Success Message not found");
			return False;
		}
		if($chk_max_items) {
			for($vb_id =0; $vb_id < NO_OF_VBUCKETS; $vb_id++) {
				$new_open_id[$vb_id] = stats_functions::get_checkpoint_stats($machine[$vb_id], "open_checkpoint_id", $vb_id);
				$diff[$vb_id] = $new_open_id[$vb_id] - $open_checkpoint_id[$vb_id];
				if($diff[$vb_id] < $expected_min_diff && $diff[$vb_id] > $expected_max_diff) {
					log_function::debug_log("checkpointdiff for vb_".$vb_id." is less than expected difference range :$expected_min_diff to $expected_max_diff New:".$new_open_id[$vb_id]." Old:".$open_checkpoint_id[$vb_id]);
					return False;
				}
			}
		}
		return True;

	}



	public function add_keys_to_cluster($number_of_keys_to_be_pumped, $chk_max_items_common = NULL, $key_start_id = 0, $object_size_value = 1024, $sleep_interval = 20000) {
		global $test_machine_list;
		$nodes = count($test_machine_list);
		log_function::debug_log("add_keys nodes:$nodes keys:$number_of_keys_to_be_pumped start:$key_start_id chk_max:$chk_max_items_common object_size_value:$object_size_value sleep_time:$sleep_interval micro seconds");
		if($chk_max_items_common and ($number_of_keys_to_be_pumped % (NO_OF_VBUCKETS))) {
			log_function::debug_log("total keys should be a multiple of the number of vbuckets for checkpoints to close properly");
			return False;
		}
		$instance = Connection::getMoxiCluster();
		if(is_numeric($object_size_value)){
			$value = self::generate_data($object_size_value);
		}
		if($chk_max_items_common){
			for($vb_id = 0; $vb_id < NO_OF_VBUCKETS; $vb_id++) {
				$retry_cpoint_close[$vb_id] = 0;
				$success_closed[$vb_id] = False;
				$machine[$vb_id] = vba_functions::get_machine_from_id_active($vb_id);
				if(!$machine[$vb_id]) {
				log_function::debug_log("vb_"."$vb_id not found in cluster");
				return False;
				}
				$open_checkpoint_id[$vb_id] = stats_functions::get_checkpoint_stats($machine[$vb_id], "open_checkpoint_id", $vb_id);
			}
		}

		for($inum_keys=0 ; $inum_keys<$number_of_keys_to_be_pumped ; $inum_keys++){
			if($key_start_id){
				$key = "testkey_".$key_start_id;
				$key_start_id++;
			} else {
				$key = uniqid("testkey_");
			}
			if(!is_numeric($object_size_value)){
				$value = $key."_".$object_size_value;
			}
			$instance->set($key, $value);
			if($chk_max_items_common and ($inum_keys%($chk_max_items_common * NO_OF_VBUCKETS)==0) and $inum_keys >= ($chk_max_items_common * NO_OF_VBUCKETS)){
				for($vb_id = 0; $vb_id< NO_OF_VBUCKETS; $vb_id++) {
					while(!$success_closed[$vb_id] and $retry_cpoint_close[$vb_id] <= ($chk_max_items_common * NO_OF_VBUCKETS)){
							$temp_open_checkpoint_id[$vb_id] = stats_functions::get_checkpoint_stats($machine[$vb_id], "open_checkpoint_id", $vb_id);
							if($temp_open_checkpoint_id[$vb_id] == $open_checkpoint_id[$vb_id] + 1){
								$open_checkpoint_id[$vb_id] = $temp_open_checkpoint_id[$vb_id];
								$success_closed[$vb_id]= True;
							}
							else {
								$success_closed[$vb_id] = False;
								$retry_cpoint_close[$vb_id]++;
								$key=uniqid("test_key_");
								$instance->set($key, $value);
								usleep($sleep_interval);
							}
							// Failed to close checkpoint after 20 seconds
							if($retry_cpoint_close[$vb_id] > ($chk_max_items_common * NO_OF_VBUCKETS)) {
								log_function::debug_log("retries exceeded for vb_".$vb_id);
								 return False;
							}
						}

				}
				for($vb_id = 0; $vb_id < NO_OF_VBUCKETS;$vb_id++) {
					if($success_closed[$vb_id] == False) {
						log_function::debug_log("error in code path");
						return False;
					}
					else {
						$success_closed[$vb_id] = False;
					}
				}

			}
		}
		sleep(2);
		return True;
	}


	public function add_keys($number_of_keys_to_be_pumped, $chk_max_items = NULL, $key_start_id = 0, $object_size_value = 1024, $sleep_interval = 2) {
		// $object_size_value can take numeric or string
		//Passing a numeric generates blobs of that size whereas passing a string appends it to the key name to get the blob value
		log_function::debug_log("add_keys keys:$number_of_keys_to_be_pumped start:$key_start_id chk_max:$chk_max_items object_size_value:$object_size_value sleep_time:$sleep_interval");

		$instance = Connection::getMaster();
		if(is_numeric($object_size_value)){
			$value = self::generate_data($object_size_value);
		}
		if($chk_max_items){
			$counter_chk_max_items = $chk_max_items;
			$open_checkpoint_id = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
		}
		for($inum_keys=0 ; $inum_keys<$number_of_keys_to_be_pumped ; $inum_keys++){
			if($key_start_id){
				$key = "testkey_".$key_start_id;
				$key_start_id++;
			} else {
				$key = uniqid("testkey_");
			}
			if(!is_numeric($object_size_value)){
				$value = $key."_".$object_size_value;
			}
			$instance->set($key, $value);
			if($chk_max_items){
				if($counter_chk_max_items == 1){
					for($iattempt_check_checkpoint_closure=0; $iattempt_check_checkpoint_closure<10 ; $iattempt_check_checkpoint_closure++){
						$temp_open_checkpoint_id = stats_functions::get_checkpoint_stats(TEST_HOST_1, "open_checkpoint_id");
						if($temp_open_checkpoint_id == $open_checkpoint_id + 1){
							$open_checkpoint_id = $temp_open_checkpoint_id;
							break;
						}
						// Failed to close checkpoint after 20 seconds
						if($iattempt_check_checkpoint_closure == 9) return False;
						sleep($sleep_interval);
					}
					$counter_chk_max_items = $chk_max_items;
				} else {
					$counter_chk_max_items--;
				}
			}
		}
		sleep(2);
		return True;
	}

	public function generate_data($object_size){
		$UserData = "GAME_ID_#@";
		if($object_size > 1048576){
			while(1){
				if(strlen($UserData) >= 524288)
				break;
				else
				$UserData = $UserData.rand(11111, 99999);

			}
			$tempUserData = $UserData;
			while(1){
				if(strlen($UserData) >= $object_size)
				break;
				else
				$UserData = $UserData.rand(1,9).$tempUserData;
			}
			return serialize($UserData);
		} else {
			while(1){
				if(strlen($UserData) >= $object_size)
				break;
				else
				$UserData = $UserData.rand(11111, 99999);
			}
			return serialize($UserData);
		}
	}

	public function generate_key($key_size){
		$KeyData = "KEYGAME_ID_#@";
		while(1){
			if(strlen($KeyData) >= $key_size)
			break;
			else
			$KeyData = $KeyData.rand(11111, 99999);
		}
		return $KeyData;
	}

	public function HugeValueProvider($size) {
		static $testvalue;

		if(!isset($testvalue[$size])){
			$testvalue[$size] = Data_generation::generate_data($size * 1048576);
		}
		return $testvalue[$size];
	}

}

class Blob_Object_Serialize_Unserialize{

	public $value;
	private $serialize_testkey = "testkey_sleep";
	private $unserialize_testkey = "testkey_wakeup";
	private $set_sleep = 5;
	private $get_sleep = 5;

	// constructor
	public function __construct($testvalue){
		$this->value = $testvalue;
	}
	// This gets executed during serialize
	public function __sleep(){
		$instance = Connection::getMaster();
		switch($this->value){
		case "set":
			$instance->set($this->serialize_testkey, $this->value."value_serialize");
			break;
		case "add":
			$instance->add($this->serialize_testkey, $this->value."value_serialize");
			break;
		case "replace":
			$instance->replace($this->serialize_testkey, $this->value."value_serialize");
			break;
		case "cas":
			$instance->get($this->serialize_testkey, $returnFlags, $returnCAS);
			$instance->cas($this->serialize_testkey, $this->value."value_serialize", 0, 0, $returnCAS);
			break;
		case "append":
			$instance->append($this->serialize_testkey, $this->value."value_serialize");
			break;
		case "prepend":
			$instance->prepend($this->serialize_testkey, $this->value."value_serialize");
			break;
		default:
			sleep($this->set_sleep);
			break;
		}
		return array('value');
	}

	public function __wakeup(){
		$instance = Connection::getMaster();
		switch($this->value){
		case "set":
			$instance->set($this->unserialize_testkey, $this->value."value_unserialize");
			break;
		case "add":
			$instance->add($this->unserialize_testkey, $this->value."value_unserialize");
			break;
		case "replace":
			$instance->replace($this->unserialize_testkey, $this->value."value_unserialize");
			break;
		case "cas":
			$instance->get($this->unserialize_testkey, $returnFlags, $returnCAS);
			$instance->cas($this->unserialize_testkey, $this->value."value_unserialize", 0, 0, $returnCAS);
			break;
		case "append":
			$instance->append($this->unserialize_testkey, $this->value."value_unserialize");
			break;
		case "prepend":
			$instance->prepend($this->unserialize_testkey, $this->value."value_unserialize");
			break;
		default:
			sleep($this->get_sleep);
			break;
		}
	}
}


?>
