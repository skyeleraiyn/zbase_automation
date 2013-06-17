<?php

	/*	
		Master: verify membase and vbucketmigrator are running, verify backup is disabled, verify parameters
		Slave: verify membase is running, verify parameters, verify replication and backup
		Durable Spare: verify membase is running, verify parameters		
		Volatile / Volatile Spare: verify membase is running, verify parameters		
	*/	


	$membase_file_list = array(
		"/etc/rc.d/init.d/memcached",
		"/etc/sysconfig/memcached",
		"/opt/membase/membase-init.sql",
		"/etc/rc.d/init.d/vbucketmigrator",
		"/opt/membase/bin/vbucketmigrator.sh");


	$backup_files_1_6 = array(
		"/opt/membase/backup.sh",
		"/etc/cron.d/membase-backup-cron");
		
	$backup_files_1_7 = array(
		"/etc/membase-backup/default.ini",
		"/opt/membase/membase-backup",
		"/opt/membase/membase-backup/membase-restore");
		
		// Main
	$pass_message = "";
	$fail_message = "";
	if($argc < 2) usage();
	if(($argc < 3) && ($argv[1] == "slave" || $argv[1] == "durable_spare")) usage();
	
	switch($argv[1]){
		case "master":
			verify_master();
			break;
		case "slave":	
			verify_slave($argv[2]);
			break;
		case "durable_spare":	
			verify_durable_spare($argv[2]);
			break;
		case "volatile":	
		case "volatile_spare":	
			verify_volatile();
			break;
		default:
			echo "unknown role \n";
			exit;	
	}
	
	print_verified_items();
	
	
		/// Function declarations
		
	function verify_master(){
				
		verify_swapiness();
		get_membase_version();
		verify_process_is_running("memcached", True);
		verify_process_is_running("vbucketmigrator", True);
		verify_process_is_running("backup.sh", False);
		
		verify_membase_files(True);
		verify_file_exists(array("/etc/sysconfig/vbucketmigrator"), True);
		if($backup_type == 1.6)
			verify_backup_files(); 
	
		$output1 = verify_stat(array("ep_flusher_state" => "running", "ep_min_data_age" => 600, "ep_queue_age_cap" => 900,
			"ep_max_data_size" => 64424509440, "ep_inconsistent_slave_chk" => 0));
		$output2 = verify_stat_from_process(array("ht_size" => 12582917, "chk_max_items" => 500000, "chk_period" => 3600, "keep_closed_chks" => "true",
			"restore_file_checks" => "false", "restore_mode" => "NA", "ht_locks" => "100000"));
		if($output1 && $output2) verification_pass("membase parameters");
	
	}
	
	function verify_slave($backup_type){
				
		verify_swapiness();
		get_membase_version();
		verify_process_is_running("memcached", True);
		verify_process_is_running("vbucketmigrator", False);
		
		verify_membase_files(True);
		verify_file_exists(array("/etc/sysconfig/vbucketmigrator"), False);
		verify_backup_files($backup_type); 
		
		$output1 = verify_stat(array("ep_flusher_state" => "running", "ep_min_data_age" => 0, "ep_queue_age_cap" => 900,
			"ep_max_data_size" => 64424509440, "ep_inconsistent_slave_chk" => 1));
		$output2 = verify_stat_from_process(array("ht_size" => 12582917, "chk_max_items" => 500000, "chk_period" => 3600, "keep_closed_chks" => "true",
			"restore_file_checks" => "false", "restore_mode" => "NA", "ht_locks" => "100000"));
		if($output1 && $output2) verification_pass("membase parameters");		
	}
	
	function verify_durable_spare($backup_type){
		
		verify_swapiness();
		get_membase_version();
		verify_process_is_running("memcached", True);
		verify_process_is_running("vbucketmigrator", False);
		
		verify_membase_files(True);
		verify_file_exists(array("/etc/sysconfig/vbucketmigrator"), False);
		verify_backup_files($backup_type); 
		
		$output1 = verify_stat(array("ep_flusher_state" => "running", "ep_min_data_age" => 0, "ep_queue_age_cap" => 900,
			"ep_max_data_size" => 64424509440, "ep_inconsistent_slave_chk" => 0));
		$output2 = verify_stat_from_process(array("restore_mode" => "NA", "ht_locks" => "100000"));
		if($output1 && $output2) verification_pass("membase parameters");		
	}
	
	function verify_volatile(){
				
		verify_swapiness();
		get_membase_version();
		verify_process_is_running("memcached", True);
		verify_process_is_running("vbucketmigrator", False);
		
		verify_membase_files(True);
		verify_file_exists(array("/etc/sysconfig/vbucketmigrator"), False);
		verify_backup_files(); 

		$output1 = verify_stat(array("ep_flusher_state" => "running", "ep_min_data_age" => 1800, "ep_queue_age_cap" => 3600,
			"ep_max_data_size" => 67914170368, "ep_mem_low_wat" => 59055800320, "ep_mem_high_wat" => 59055800320, "ep_inconsistent_slave_chk" => 0));
		$output2 = verify_stat_from_process(array("ht_locks" => "100000"));
		if($output1 && $output2) verification_pass("membase parameters");		
	}


	function get_membase_version(){
		
		$installed_version = trim(shell_exec("rpm -q membase"));
		echo "Membase version: ".$installed_version."\n";
		$mc = new memcache();
		$mc->addserver("localhost", 11211);
		$stats = $mc->getStats();
		echo "ep-version:".$stats["ep_version"]."\n";
	
	}
	
	function verify_membase_files($expected_status){
		global $membase_file_list;
		return verify_file_exists($membase_file_list, $expected_status);
	}

	function verify_backup_files($backup_type = NULL){
		global $backup_files_1_6, $backup_files_1_7;
	
		if($backup_type == NULL){
			verify_file_exists($backup_files_1_6, False);
			verify_file_exists($backup_files_1_7, False);
			verify_file_exists(array("/db_backup"), False);
		} else {
			if($backup_type == 1.6){
				verify_file_exists($backup_files_1_6, True);
				verify_file_exists($backup_files_1_7, False);
			} else {
				verify_file_exists($backup_files_1_6, False);
				verify_file_exists($backup_files_1_7, True);
			}
		}
	}
	
	function verify_process_is_running($process_name, $should_exists){	
		$check_process_exists = explode(" ", trim(shell_exec("/sbin/pidof $process_name")));
		if (is_numeric($check_process_exists[0])){
			if($should_exists){
				verification_pass("process $process_name is running");
			} else {
				verification_fail("process $process_name is running");
			}
		} else {	
			if($should_exists){
				verification_fail("process $process_name is not running");
			} else {
				verification_pass("process $process_name is not running");
			}
		}
	}

	function verify_file_exists($file_list, $expected_status){
		$file_verification = True;

		foreach($file_list as $filename){
			if(file_exists($filename)){
				if(!$expected_status){
					$file_verification = False;
					verification_fail("file $filename exist");
				}
			} else {
				if($expected_status){
					$file_verification = False;
					verification_fail("file $filename doesn't exist");
				}
			} 
		}
		return $file_verification;
	}	

	function verify_swapiness(){
		$value = trim(shell_exec("cat /proc/sys/vm/swappiness"));
		if($value > 0){
			verification_fail("swapiness is set to $value");
		} else {
			verification_pass("swapiness is set to $value");
		}
	}
	
	function verify_stat($stat_array){
		$stat_verification = True;
		$mc = new memcache();
		$mc->addserver("localhost", 11211);
		$stats = $mc->getStats();
		foreach($stat_array as $stat_name => $expected_value){
			$actual_value = $stats[$stat_name];
			if($actual_value <> $expected_value){
				$stat_verification = False;
				verification_fail("stat value for $stat_name differs Actual: $actual_value Expected: $expected_value");
			}
		}
		return $stat_verification;
	}

	function verify_stat_from_process($stat_array){
		$stat_verification = True;

		$output = trim(shell_exec("ps -elf | grep inconsistent_slave_chk | grep -v bash | grep -v grep"));
		$output = explode(" ", $output);
		$output = end($output);
		$output = explode(";", $output);
		$stat_value = array();
		foreach($output as $stat){
			$stat = explode("=", $stat);
			$stat_value[$stat[0]] =  $stat[1];
		}
		foreach($stat_array as $stat_name => $expected_value){
			if(array_key_exists($stat_name, $stat_value)){
				$actual_value = $stat_value[$stat_name];
			} else {
				$actual_value = "NA";
			}
			if($actual_value <> $expected_value){
				$stat_verification = False;
				verification_fail("stat value for $stat_name differs Actual: $actual_value Expected: $expected_value");
			}
		}
		return $stat_verification;	
	}

	function verification_pass($message){
		global $pass_message;
		if($pass_message == ""){
			$pass_message = "Verified following: \n"."\t".$message."\n";
		} else {
			$pass_message = $pass_message."\t".$message."\n";
		}
	}
	
	function verification_fail($message){
		global $fail_message;
		if($fail_message == ""){
			$fail_message = "Verification failed for the following:\n"."\t".$message."\n";
		} else {
			$fail_message = $fail_message."\t".$message."\n";
		}	
	}
	
	function print_verified_items(){
		global $pass_message, $fail_message;
		
		echo "\n";
		if($pass_message <> "") echo $pass_message;	
		if($fail_message <> "") echo $fail_message;	
		echo "\n";
	}
	
	function usage(){
		$file = $_SERVER["SCRIPT_NAME"];
		$break = Explode('/', $file);
		$pfile = $break[count($break) - 1];

		echo "\n 	Usage: $pfile <server_role> <backup_type> \n";
		echo "		server_role: master, slave, durable_spare, volatile, volatile_spare \n";
		echo "		backup_type: 1.6, 1.7 -- can be ignored for master, volatile, volatile_spare \n\n";
		exit;
	}	
?>
