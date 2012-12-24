<?php

class Reshard_test_function{

	public function initial_setup($remote_machine_list){	
		global $result_file;
		
		general_function::initial_setup($remote_machine_list);
		shell_exec("chmod +x ".RESHARD_SCRIPT_FOLDER."/reshard_membase.sh");
		$result_file =  RESULT_FOLDER."/"."result.log";
		
			// install pdsh on the local box
		general_function::execute_command("sudo yum install -y -q pdsh 2>&1");
	
			/// install python-simplejson python-pycurl on the first 3 machines
		foreach($remote_machine_list as $remote_machine_count => $remote_machine){
			general_function::execute_command("sudo yum install -y -q python-simplejson python-pycurl 2>&1", $remote_machine);
			if($remote_machine_count == 2) break;
		}
		
	}

	
	public function test_reshard($source_machine_list, $destination_machine_list, $source_build_list, $destination_build_list){

		$source_build_list = explode(":::", $source_build_list);
		$destination_build_list = explode(":::", $destination_build_list);

			// Install membase builds
		self::install_membase($source_machine_list, $source_build_list[0]);
		self::install_membase($destination_machine_list, $destination_build_list[0]);
		
					// Reset membase server
		membase_function::reset_membase_servers(array_merge($source_machine_list, $destination_machine_list));
		
			// Install php-pecl on source if declared
		if(count($source_build_list) == 2){
			self::install_php_pecl($source_build_list[1]);
		}
					// Install php-pecl on destination if declared
		if(count($destination_build_list) == 2){
			self::install_php_pecl($destination_build_list[1]);
		}
		sleep(1);
		
		log_function::result_log("	Adding keys to source pool ...");
		self::set_keys($source_machine_list, $destination_machine_list);
		
		log_function::result_log("	Starting resharing script ...");
		self::start_resharding($source_machine_list, $destination_machine_list);
		
		log_function::result_log("	Verifying keys on the destination pool ...");
				
		if(self::get_keys($source_machine_list, $destination_machine_list)){
			log_function::result_log("Reshard successful \n\n");
		} else {
			log_function::result_log("Reshard failed \n\n");
		}
			
	}	

	public function start_resharding($source_machine_list, $destination_machine_list){
	
		// create source destination list
		
		$fh = fopen(RESHARD_SCRIPT_FOLDER."/source_list", 'w'); 
		$source_list_contents = implode("\n", $source_machine_list);
		fwrite($fh, $source_list_contents);
		fclose($fh);

		$fh = fopen(RESHARD_SCRIPT_FOLDER."/destination_list", 'w'); 
		$source_list_contents = implode(":11211\n", $destination_machine_list).":11211";
		fwrite($fh, $source_list_contents);
		fclose($fh);
	
		log_function::debug_log(shell_exec(RESHARD_SCRIPT_FOLDER."/reshard_membase.sh start ".RESHARD_SCRIPT_FOLDER."/source_list ".RESHARD_SCRIPT_FOLDER."/destination_list"));
		sleep(5);
	}
	
	public function install_membase($machine_list, $membase_rpm){
		foreach($machine_list as $remote_machine_name){
			if(!(SKIP_BUILD_INSTALLATION)){
				installation::clean_install_rpm($remote_machine_name, BUILD_FOLDER_PATH.$membase_rpm, MEMBASE_PACKAGE_NAME);
			}
			if(stristr($membase_rpm, "1.6")){
				membase_function::stop_membase_server_service($remote_machine_name);
				$membase_version = 1.6;
			} else {
				$membase_version = 1.7;
			}
			$base_files_path = HOME_DIRECTORY."common/misc_files/".$membase_version."_files/";
			self::copy_memcached_files($remote_machine_name, $base_files_path);
			
		}
	}
	
	public function copy_memcached_files($remote_server, $base_files_path){
		remote_function::remote_file_copy($remote_server, $base_files_path."memcached_init.d", MEMCACHED_INIT, False, True, True);
		remote_function::remote_file_copy($remote_server, $base_files_path."memcached_sysconfig", MEMCACHED_SYSCONFIG, False, True, True);
		remote_function::remote_file_copy($remote_server, $base_files_path."membase-init.sql", MEMBASE_INIT_SQL, False, True, True);		
	}
	
	public function install_php_pecl($pecl_rpm){
		if(!(SKIP_BUILD_INSTALLATION)){
			installation::clean_install_rpm("localhost", BUILD_FOLDER_PATH.$pecl_rpm, PHP_PECL_PACKAGE_NAME);
		}
	}

	public function set_keys($source_machine_list, $destination_machine_list){
		global $installed_corrupted_keys, $blob_value, $flags_list;
		$installed_corrupted_keys = False;
		
		sleep(2);
				
			// set corrupt keys only if Pecl and Membase supports DI 
		if(installation::verify_php_pecl_DI_capable() and installation::verify_membase_DI_capable($source_machine_list[0])){
				
			$mc = new memcache();
			foreach($source_machine_list as $source_machine){
				$mc->addserver($source_machine, 11211);
			}
			
			$mc_rejected_key = new memcache();
			$mc_rejected_key->addserver($source_machine_list[0], 11211);
			$mc_rejected_key->setproperty("EnableChecksum", True);
		
			$mc->setproperty("EnableChecksum", True);	
			$mc->set("testkey_corrupt_keys_1", "testvalue");	 // corrupt value
			$mc->set("testkey_corrupt_keys_3", "testvalue");	// corrupt cksum
			$mc->set("testkey_corrupt_keys_4", "testvalue");	// corrupt value in memory
			$mc->set("testkey_corrupt_keys_6", "testvalue");	// corrupt cksum in memory
			
			$mc_rejected_key->set("testkey_corrupt_keys_0", "testvalue");	// for rejected key which is corrupted
			sleep(1);
			membase_function::memcached_service($source_machine_list[0], "stop");
			sleep(1);
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite 'update kv set v=\"value\" where k like \"testkey_corrupt_keys_1\";'", $source_machine_list[0]);
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-3.sqlite 'update kv set cksum=\"0002:ff\" where k like \"testkey_corrupt_keys_3\";'", $source_machine_list[0]);			
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-2.sqlite 'update kv set v=\"value\" where k like \"testkey_corrupt_keys_4\";'", $source_machine_list[0]);
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-0.sqlite 'update kv set cksum=\"0002:ff\" where k like \"testkey_corrupt_keys_6\";'", $source_machine_list[0]);			
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-2.sqlite 'update kv set v=\"value\" where k like \"testkey_corrupt_keys_0\";'", $source_machine_list[0]);			
			membase_function::memcached_service($source_machine_list[0], "start");
			sleep(1);

			foreach($source_machine_list as $source_machine){
				flushctl_commands::set_flushctl_parameters($source_machine, "chk_max_items", 100);
			}			
			$mc = new memcache();
			foreach($source_machine_list as $source_machine){
				$mc->addserver($source_machine, 11211);
			}
			
			@$mc->get("testkey_corrupt_keys_4"); // get corrupted value to memory
			@$mc->get("testkey_corrupt_keys_6"); // get corrupted cksum to memory
			
			log_function::result_log("		Added corrupted keys");
			$installed_corrupted_keys = True;
		} else {
			$installed_corrupted_keys = False;
		}

		$mc = new memcache();
		foreach($source_machine_list as $source_machine){
			$mc->addserver($source_machine);
		}		
		
		foreach($source_machine_list as $source_machine){
			flushctl_commands::set_flushctl_parameters($source_machine, "chk_max_items", 100);
		}
			// install keys for checking rejected keys
		$mc_rejected_keys = new memcache();	
		$mc_rejected_keys->addserver($source_machine_list[0], 11211);
		
		for($icount=0 ; $icount<10 ; $icount++){
			$mc_rejected_keys->set("testkey_reject_keys_$icount", "testvalue");
		}
		log_function::result_log("		Added keys to test rejected keys function");
				
		
		// set 1000 keys without checksum
		$mc->setproperty("EnableChecksum", False);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$mc->set("testkey_without_checksum_$ikey", "testvalue_without_checksum_$ikey");
			if($ikey % 100 == 0) sleep(1);
		}
		log_function::result_log("		Added keys without checksum");
		
			// set keys with 10s and 60s expiry
		for($ikey = 0; $ikey<10 ; $ikey++){
			$mc->set("testkey_with_expiry10_$ikey", "testvalue_with_expiry10_$ikey", 0, 10);
		}
		for($ikey = 0; $ikey<10 ; $ikey++){
			$mc->set("testkey_with_expiry60_$ikey", "testvalue_with_expiry60_$ikey", 0, 60);
		}		
		log_function::result_log("		Added keys with expiry");
		
			// set 1000 keys with checksum
		$mc->setproperty("EnableChecksum", True);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$mc->set("testkey_with_checksum_$ikey", "testvalue_with_checksum_$ikey");
		}
		log_function::result_log("		Added keys with checksum");
		
			// set keys with different flags
		$blob_value = self::generate_value(1024);
		
		$flags_list = array(
			MEMCACHE_COMPRESSED, 
			MEMCACHE_COMPRESSED_LZO,
			MEMCACHE_COMPRESSED_BZIP2,
			MEMCACHE_SERIALIZED_IGBINARY,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2,
			MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,
			MEMCACHE_COMPRESSED | MEMCACHE_SERIALIZED_IGBINARY,
			MEMCACHE_COMPRESSED_LZO | MEMCACHE_SERIALIZED_IGBINARY,
			MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,			
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2,	
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_SERIALIZED_IGBINARY,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,
			MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY,
			MEMCACHE_COMPRESSED | MEMCACHE_COMPRESSED_LZO | MEMCACHE_COMPRESSED_BZIP2 | MEMCACHE_SERIALIZED_IGBINARY	
		);
		
		$mc->setproperty("EnableChecksum", True);
		foreach($flags_list as $key => $flag){
			$mc->set("testkey_with_flag_chksum_$key", $blob_value, $flag);
		}
		
		$mc->setproperty("EnableChecksum", False);
		foreach($flags_list as $key => $flag){
			$mc->set("testkey_with_flag_without_cksum_$key", $blob_value, $flag);
		}
		log_function::result_log("		Added keys with flags");
		
		sleep(2);
		
	}


	public function generate_value($data_size){
		$value = "GAME_ZID_#@";
		while(1){
			if(strlen($value) >= $data_size) 
				break;
			else
				$value = $value.rand(11111, 99999);	
		}
		return $value;
	}	
	
	// All the verification happens in this function
	public function get_keys($source_machine_list, $destination_machine_list){
		global $installed_corrupted_keys, $blob_value, $flags_list;
		$bReshardVerification = True;
		
		$mc = new memcache();
		foreach($destination_machine_list as $destination_machine){
			$mc->addserver($destination_machine);
		}
			// get 1000 keys without checksum, but with checksum set False
		$bVerification = True;	
		$mc->setproperty("EnableChecksum", False);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_without_checksum_$ikey");
			if( $output <> "testvalue_without_checksum_$ikey") {
				log_function::result_log("		Get on keys without checksum with EnableChecksum False: Fail testkey_without_checksum_$ikey ".$output);
				$bReshardVerification = $bVerification = False;
				break;
			}
		}
		if($bVerification){
			log_function::result_log("		Get on keys without checksum with EnableChecksum False: Pass");
		}
			// get 1000 keys without checksum, but with checksum set True
		$bVerification = True;		
		$mc->setproperty("EnableChecksum", True);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_without_checksum_$ikey");
			if( $output <> "testvalue_without_checksum_$ikey") {
				log_function::result_log("		Get on keys without checksum with EnableChecksum True: Fail testkey_without_checksum_$ikey ".$output);
				$bReshardVerification = $bVerification = False;
				break;
			}
		}
		if($bVerification){	
			log_function::result_log("		Get on keys without checksum with EnableChecksum True: Pass");
		}
		
			// get 1000 keys with checksum
		$bVerification = True;		
		$mc->setproperty("EnableChecksum", True);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_with_checksum_$ikey");
			if( $output <> "testvalue_with_checksum_$ikey"){
				log_function::result_log("		Get on keys with checksum with EnableChecksum True: Fail testkey_with_checksum_$ikey ".$output);
				$bReshardVerification = $bVerification = False;
				break;
			}	
		}
		if($bVerification){
			log_function::result_log("		Get on keys with checksum with EnableChecksum True: Pass");
		}
		
			// get 1000 keys with checksum with checksum set False
		$bVerification = True;		
		$mc->setproperty("EnableChecksum", False);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_with_checksum_$ikey");
			if( $output <> "testvalue_with_checksum_$ikey"){
				log_function::result_log("		Get on keys with checksum with EnableChecksum False: Fail testkey_with_checksum_$ikey ".$output);
				$bReshardVerification = $bVerification = False;
				break;
			}	
		}	
		if($bVerification){
			log_function::result_log("		Get on keys with checksum with EnableChecksum False: Pass");
		}
		
			// verify checksum if source and destination supports checksum
		foreach($destination_machine_list as $destination_machine){
			$table_schema_destination = general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite '.schema'", $destination_machine);
			if(stristr($table_schema_destination, "cksum")){
				$output = trim(shell_exec("echo -ne 'get testkey_with_checksum_448\r\n' | nc ".$destination_machine." 11211 | grep testkey_with_checksum_448"));
				if($output <> "" or $output <> NULL){
					
					if(stristr($output, "0002:")){
						log_function::result_log("		Verify checksum on key with checksum: Pass");
					} else {
						$table_schema_source = general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite '.schema'", $source_machine_list[0]);
						if(stristr($table_schema_source, "cksum")){
							log_function::result_log("		Verify checksum on key with checksum: Fail ".$output);
							$bReshardVerification = False;
							break;
						} else {
							if(stristr($output, "0001:")){
								log_function::result_log("		Verify checksum on key with checksum: Pass");
							} else {
								log_function::result_log("		Verify checksum on key with checksum: Fail ".$output);
							}
						}
					}
				}
			} else {
				log_function::debug_log("	checksum not supported by destination. Skipping checksum check");
			} 
		}		
			// Verify rejected keys
		$rejected_key_list = file_get_contents("/tmp/rejected-keys");
		if( $rejected_key_list == "" or $rejected_key_list == NULL){
			log_function::result_log("		Verify rejected keys: Fail");
			log_function::result_log("		".$rejected_key_list);
			$bReshardVerification = False;	
		} else {
			log_function::result_log("		Verify rejected keys: Pass");
		}
			// verification for checksum failed keys
		if($installed_corrupted_keys){
			if(file_exists("/tmp/cksum-failed-keys")){
				$cksum_failed_list = file_get_contents("/tmp/cksum-failed-keys");
				if(stristr($cksum_failed_list, "testkey_corrupt_keys_1") && stristr($cksum_failed_list, "testkey_corrupt_keys_3")){
					log_function::result_log("		Verify checksum failed keys: Pass");
				} else {
					log_function::result_log("		Verify checksum failed keys: Fail");
					$bReshardVerification = False;
				}
			}
		}
		
		
			// Verify Memcache API's on the new pool
		log_function::result_log("	Verifying Memcache API's (Set, Replace, Append, Prepend, CAS, Delete) on the new pool...");	
		
		if(self::verify_basic_operations($destination_machine_list, "testkey_without_checksum_")){
			log_function::result_log("		Verify Memcache API's on keys without chksum: Pass");
		} else {
			log_function::result_log("		Verify Memcache API's on keys without chksum: Fail");
			$bReshardVerification = False;
		}
		
		if(self::verify_basic_operations($destination_machine_list, "testkey_with_checksum_")){
			log_function::result_log("		Verify Memcache API's on keys with chksum: Pass");
		} else {
			log_function::result_log("		Verify Memcache API's on keys with chksum: Fail");
			$bReshardVerification = False;
		}
		
		// verify keys with flags
		$bVerification = True;
		$mc->setproperty("EnableChecksum", True);
		foreach($flags_list as $key => $flag){
			$output = $mc->get("testkey_with_flag_chksum_$key");
			if($output <> $blob_value){
				log_function::result_log("		Verify keys set with flag $flag: Fail");
				$bReshardVerification = $bVerification = False;
				break;			
			}
		}
		if($bVerification){
			log_function::result_log("		Verify keys set with flag and checksum: Pass");
		}
		
		$bVerification = True;
		$mc->setproperty("EnableChecksum", False);
		foreach($flags_list as $key => $flag){
			$output = $mc->get("testkey_with_flag_without_cksum_$key");
			if($output <> $blob_value){
				log_function::result_log("		Verify keys set with flag $flag: Fail");
				$bReshardVerification = $bVerification = False;
				break;			
			}			
		}
		if($bVerification){
			log_function::result_log("		Verify keys set with flag without checksum: Pass");
		}
		
			// verify keys set with expiry
		log_function::result_log("	Verify keys with expiry ...");
		log_function::result_log("		Sleeping 10s ...");	
		sleep(10);	
		$bVerification = True;	
		for($ikey = 0; $ikey<10 ; $ikey++){
			if($mc->get("testkey_with_expiry10_$ikey")){
				log_function::result_log("		Verify keys set with expiry 10s are expiried: Fail");
				$bReshardVerification = $bVerification = False;
				break;
			}
		}
		if($bVerification){
			log_function::result_log("		Verify keys set with expiry 10s are expiried: Pass");
		}
		
		$bVerification = True;	
		for($ikey = 0; $ikey<10 ; $ikey++){
			if(!$mc->get("testkey_with_expiry60_$ikey")){
				log_function::result_log("		Verify keys set with expiry 60s are not expiried: Fail");
				$bReshardVerification = $bVerification = False;
				break;
			}
		}
		if($bVerification){
			log_function::result_log("		Verify keys set with expiry 60s are not expiried: Pass");
		}
		log_function::result_log("		Sleeping 50s ...");
		sleep(50);
		$bVerification = True;	
		for($ikey = 0; $ikey<10 ; $ikey++){
			if($mc->get("testkey_with_expiry60_$ikey")){
				log_function::result_log("		Verify keys set with expiry 60s are expiried: Fail");
				$bReshardVerification = $bVerification = False;
				break;
			}
		}
		if($bVerification){
			log_function::result_log("		Verify keys set with 60s expiry: Pass");
		}
			
		return $bReshardVerification;
	}	
		
	
	public function verify_basic_operations($destination_machine_list, $key_prefix){
		global $installed_corrupted_keys;
		
		$bBasicOperations = True;
		$ikeyindex = 1;
		
		$mc = new memcache();
		foreach($destination_machine_list as $destination_machine){
			$mc->addserver($destination_machine);
		}
		
		foreach(array(True, False) as $bool_value){
			$mc->setproperty("EnableChecksum", $bool_value);
	
				// Verify Set
			$mc->set($key_prefix.$ikeyindex, "new_value");
			if(!$mc->get($key_prefix.$ikeyindex) == "new_value"){
				log_function::result_log("		Verify Set operation on $key_prefix$ikeyindex in the new pool: Fail");
				$bBasicOperations = False;
			}	
			$ikeyindex++;
			
				// Verify Replace
			$mc->replace($key_prefix.$ikeyindex, "new_value");
			if(!$mc->get($key_prefix.$ikeyindex) == "new_value"){
				log_function::result_log("		Verify Replace operation on $key_prefix$ikeyindex in the new pool: Fail");
				$bBasicOperations = False;
			}	
			$ikeyindex++;
			
			if($installed_corrupted_keys){
					// Verify Append
				$old_value = $mc->get($key_prefix.$ikeyindex);	
				@$mc->append($key_prefix.$ikeyindex, "new_value");
				if(!$mc->get($key_prefix.$ikeyindex) == $old_value."new_value"){
					log_function::result_log("		Verify Append operation on $key_prefix$ikeyindex in the new pool: Fail");
					$bBasicOperations = False;
				}	
				$ikeyindex++;

					// Verify Prepend
				$old_value = $mc->get($key_prefix.$ikeyindex);	
				@$mc->prepend($key_prefix.$ikeyindex, "new_value");
				if(!($mc->get($key_prefix.$ikeyindex) == "new_value".$old_value)){
					log_function::result_log("		Verify Prepend operation on $key_prefix$ikeyindex in the new pool: Fail");
					$bBasicOperations = False;
				}	
				$ikeyindex++;			
			}
				// Verify CAS
			$returnFlags = NULL;
			$returnCAS = NULL;	
			$old_value = $mc->get($key_prefix.$ikeyindex, $returnFlags, $returnCAS);	
			$mc->cas($key_prefix.$ikeyindex, "new_value", $returnFlags, $returnCAS);
			if(!$mc->get($key_prefix.$ikeyindex) == "new_value"){
				log_function::result_log("		Verify CAS operation on $key_prefix$ikeyindex in the new pool: Fail");
				$bBasicOperations = False;
			}	
			$ikeyindex++;			
			
				// Verify Delete
			$mc->delete($key_prefix.$ikeyindex);
			if($mc->get($key_prefix.$ikeyindex)){
				log_function::result_log("		Verify Delete operation on $key_prefix$ikeyindex in the new pool: Fail");
				$bBasicOperations = False;
			}	
			$ikeyindex++;			
			
		}
		return $bBasicOperations;
	}
}	
?>