<?php

class Reshard_test_function{

	public function initial_setup($remote_machine_list){	
		global $result_file;
		
		general_function::initial_setup($remote_machine_list);
		shell_exec("chmod +x membase-reshard/reshard_membase.sh");
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
		
			// Install php-pecl if declared
		if(count($source_build_list) == 2){
			self::install_php_pecl($source_build_list[1]);
		}
		
		log_function::result_log("Adding keys to source pool ...");
		self::set_keys($source_machine_list, $destination_machine_list);
		
		log_function::result_log("Starting resharing script ...");
		self::start_resharding($source_machine_list, $destination_machine_list);
		
		sleep(1);
		log_function::result_log("Verifying keys on the destination pool ...");
			// Install php-pecl if declared
		if(count($source_build_list) == 2){
			self::install_php_pecl($source_build_list[1]);
		}		
		if(self::get_keys($source_machine_list, $destination_machine_list)){
			log_function::result_log("Reshard successful \n\n");
		} else {
			log_function::result_log("Reshard failed \n\n");
		}
			
	}	

	public function start_resharding($source_machine_list, $destination_machine_list){
	
		// create source destination list
		
		$fh = fopen("membase-reshard/source_list", 'w'); 
		$source_list_contents = implode("\n", $source_machine_list);
		fwrite($fh, $source_list_contents);
		fclose($fh);

		$fh = fopen("membase-reshard/destination_list", 'w'); 
		$source_list_contents = implode(":11211\n", $destination_machine_list).":11211";
		fwrite($fh, $source_list_contents);
		fclose($fh);
	
		log_function::debug_log(shell_exec("membase-reshard/reshard_membase.sh start membase-reshard/source_list membase-reshard/destination_list"));
		sleep(5);
	}
	
	public function install_membase($machine_list, $membase_rpm){
		foreach($machine_list as $remote_machine_name){
			if(!(SKIP_BUILD_INSTALLATION_AND_SETUP)){
				rpm_function::clean_install_rpm($remote_machine_name, BUILD_FOLDER_PATH.$membase_rpm, MEMBASE_PACKAGE_NAME);
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
		if(!(SKIP_BUILD_INSTALLATION_AND_SETUP)){
			rpm_function::clean_install_rpm("localhost", BUILD_FOLDER_PATH.$pecl_rpm, PHP_PECL_PACKAGE_NAME);
		}
	}

	public function set_keys($source_machine_list, $destination_machine_list){
		global $installed_corrupted_keys;
		$installed_corrupted_keys = False;
		
		sleep(2);
				
			// set corrupt keys only if pecl 2.5.0.0 is installed and source supports checksum
		$table_schema_source = general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite '.schema'", $source_machine_list[0]);
		if(stristr(rpm_function::get_installed_pecl_version("localhost"), "2.5.0") and stristr($table_schema_source, "cksum")){
		
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
			membase_function::stop_memcached_service($source_machine_list[0]);
			sleep(1);
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite 'update kv set v=\"value\" where k like \"testkey_corrupt_keys_1\";'", $source_machine_list[0]);
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-3.sqlite 'update kv set cksum=\"0002:ff\" where k like \"testkey_corrupt_keys_3\";'", $source_machine_list[0]);			
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-2.sqlite 'update kv set v=\"value\" where k like \"testkey_corrupt_keys_4\";'", $source_machine_list[0]);
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-0.sqlite 'update kv set cksum=\"0002:ff\" where k like \"testkey_corrupt_keys_6\";'", $source_machine_list[0]);			
			general_function::execute_command("sudo sqlite3 /db/membase/ep.db-2.sqlite 'update kv set v=\"value\" where k like \"testkey_corrupt_keys_0\";'", $source_machine_list[0]);			
			membase_function::start_memcached_service($source_machine_list[0]);
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
			
			log_function::result_log("	Added corrupted keys");
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
		log_function::result_log("	Added keys to test rejected keys function");
				
		
		// set 1000 keys without checksum
		$mc->setproperty("EnableChecksum", False);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$mc->set("testkey_without_checksum_$ikey", "testvalue_without_checksum_$ikey");
			if($ikey % 100 == 0) sleep(1);
		}
		log_function::result_log("	Added keys without checksum");
			// set 1000 keys with checksum
		$mc->setproperty("EnableChecksum", True);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$mc->set("testkey_with_checksum_$ikey", "testvalue_with_checksum_$ikey");
		}
		log_function::result_log("	Added keys with checksum \n");
		
		sleep(2);
		
	}

	
	
	// All the verification happens in this function
	public function get_keys($source_machine_list, $destination_machine_list){
		global $installed_corrupted_keys;
		
		$mc = new memcache();
		foreach($destination_machine_list as $source_machine){
			$mc->addserver($source_machine);
		}
			// get 1000 keys without checksum, but with checksum set False
		$mc->setproperty("EnableChecksum", False);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_without_checksum_$ikey");
			if( $output <> "testvalue_without_checksum_$ikey") {
				log_function::result_log("	Get on keys without checksum with EnableChecksum False: Fail testkey_without_checksum_$ikey ".$output);
				return  False;
			}
		}
		log_function::result_log("	Get on keys without checksum with EnableChecksum False: Pass");
		
			// get 1000 keys without checksum, but with checksum set True
		$mc->setproperty("EnableChecksum", True);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_without_checksum_$ikey");
			if( $output <> "testvalue_without_checksum_$ikey") {
				log_function::result_log("	Get on keys without checksum with EnableChecksum True: Fail testkey_without_checksum_$ikey ".$output);
				return  False;
			}
		}	
		log_function::result_log("	Get on keys without checksum with EnableChecksum True: Pass");
		
			// get 1000 keys with checksum
		$mc->setproperty("EnableChecksum", True);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_with_checksum_$ikey");
			if( $output <> "testvalue_with_checksum_$ikey"){
				log_function::result_log("	Get on keys with checksum with EnableChecksum True: Fail testkey_with_checksum_$ikey ".$output);
				return  False;
			}	
		}
		log_function::result_log("	Get on keys with checksum with EnableChecksum True: Pass");
		
		
			// get 1000 keys with checksum with checksum set False
		$mc->setproperty("EnableChecksum", False);
		for($ikey = 0; $ikey<1000 ; $ikey++){
			$output = $mc->get("testkey_with_checksum_$ikey");
			if( $output <> "testvalue_with_checksum_$ikey"){
				log_function::result_log("	Get on keys with checksum with EnableChecksum False: Fail testkey_with_checksum_$ikey ".$output);
				return  False;
			}	
		}	
		log_function::result_log("	Get on keys with checksum with EnableChecksum False: Pass");

			// verify checksum if source and destination supports checksum
		foreach($destination_machine_list as $source_machine){
			$table_schema_destination = general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite '.schema'", $source_machine);
			
			if(stristr($table_schema_destination, "cksum")){
				$output = trim(shell_exec("echo -ne 'get testkey_with_checksum_448\r\n' | nc ".$source_machine." 11211 | grep testkey_with_checksum_448"));
				if($output <> "" or $output <> NULL){
					
					if(stristr($output, "0002:")){
						log_function::result_log("	Verify checksum on key with checksum: Pass");
					} else {
						$table_schema_source = general_function::execute_command("sudo sqlite3 /db/membase/ep.db-1.sqlite '.schema'", $source_machine_list[0]);
						if(stristr($table_schema_source, "cksum")){
							log_function::result_log("	Verify checksum on key with checksum: Fail ".$output);
							return False;
						} else {
							if(stristr($output, "0001:")){
								log_function::result_log("	Verify checksum on key with checksum: Pass");
							} else {
								log_function::result_log("	Verify checksum on key with checksum: Fail ".$output);
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
			log_function::result_log("	Verify rejected keys: Fail");
			log_function::result_log("	".$rejected_key_list);
			return  False;	
		} else {
			log_function::result_log("	Verify rejected keys: Pass");
		}
			// verification for checksum failed keys
		if($installed_corrupted_keys){
			if(file_exists("/tmp/cksum-failed-keys")){
				$cksum_failed_list = file_get_contents("/tmp/cksum-failed-keys");
				if(stristr($cksum_failed_list, "testkey_corrupt_keys_1") && stristr($cksum_failed_list, "testkey_corrupt_keys_3")){
					log_function::result_log("	Verify checksum failed keys: Pass");
				} else {
					log_function::result_log("	Verify checksum failed keys: Fail");
					return  False;
				}
			}
		}
		return True;
	}	
}	
?>