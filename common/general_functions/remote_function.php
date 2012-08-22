<?php

class remote_function{
	public function remote_service_control($remote_machine_name, $service_name, $command) {
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name." ".$service_name." ".$command);
		
		$output = self::remote_execution($remote_machine_name, "sudo /etc/init.d/$service_name $command");
		if(stristr($output, "failed")) {
			log_function::debug_log($output);
			return 0;
		} else {
			sleep(2);
			if($command == "start" or $command == "restart") $check_status = "running";
			if($command == "stop") $check_status = "stopped";
			$output = self::remote_execution($remote_machine_name, "sudo /etc/init.d/$service_name status");
			if(stristr($output, $check_status)) 
				return 1;
			else
				return 0;
		}	
	}


	public function remote_execution($remote_machine_name, $command_to_be_executed, $parse_output = True){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name." ".$command_to_be_executed);
		
		ini_set("expect.loguser", "Off");
		$stream = expect_popen("ssh ".TEST_USERNAME."@".$remote_machine_name);

				$cases = array (
					array ("password:", "password"),
					array ("yes/no)?", "yesno"), 
					array ("]$ ", "shell", EXP_EXACT)			
				);

		while (true) {
			switch (expect_expectl ($stream,$cases)){
				case "password":
					fwrite ($stream, TEST_PASSWORD."\n");
					break;

				case "yesno":
					fwrite ($stream, "yes\n");
					break;

				case "shell":
					fwrite ($stream, $command_to_be_executed." \n");
					fwrite ($stream, "exit \n");
					break 2;

				case EXP_TIMEOUT:
				case EXP_EOF:
					break 2;

				default:
					die ("Error has occurred!\n");
			}
		}
		$output = "";
		while ($line = fgets($stream)) {
			$output = $output.$line;
		}
		
		$output = trim(str_replace($command_to_be_executed, "", $output));
		$output = explode("\n", $output);
		$output = array_splice($output, 0, -3);
		$output = implode("\n", $output);
		fclose ($stream);
		log_function::debug_log($output);
		return $output;		
	
	}
	
	public function remote_file_copy($remote_machine_name, $file_source, $file_destination, $reverse_copy = False, $parse_output = True, $sudo_copy = False){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name." ".$file_source." ".$file_destination);
			
		ini_set("expect.loguser", "Off");

		if($reverse_copy){
			$stream = expect_popen ("scp -r ".TEST_USERNAME."@$remote_machine_name:$file_source $file_destination");
		} else {
			if($sudo_copy){
				$stream = expect_popen ("scp -r $file_source ".TEST_USERNAME."@$remote_machine_name:/tmp/".basename($file_source));
			} else {
				$stream = expect_popen ("scp -r $file_source ".TEST_USERNAME."@$remote_machine_name:$file_destination");	
			}	
		}

				$cases = array (
					array ("password:", "password"),
					array ("yes/no)?", "yesno"), 
					array ("]$ ", "shell", EXP_EXACT)			
				);
			
		while (true) {
			switch (expect_expectl ($stream,$cases)){
				case "password":
					fwrite ($stream, TEST_PASSWORD."\n");
					break;

				case "yesno":
					fwrite ($stream, "yes\n");
					break;
				case EXP_TIMEOUT:
				case EXP_EOF:
					break 2;

				default:
					die ("Error has occurred!\n");
			}
		}
		
		$output = "";
		while ($line = fgets($stream)) {
			$output = $output.$line;
		}
								
		fclose ($stream);
			if ($sudo_copy){
				$output = $output.self::remote_execution($remote_machine_name, "sudo mv /tmp/".basename($file_source)." ".$file_destination);
				self::remote_execution($remote_machine_name, "sudo chmod +x ".$file_destination." ; sudo chown root ".$file_destination);
			}			
		log_function::debug_log($output);
		return $output;	

	}	
	
	public function remote_process_check($remote_machine_name, $process){	
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name."".$process);
		
		$process_list = explode(" ", trim(self::remote_execution($remote_machine_name, "/sbin/pidof $process")));
		if (empty($process_list[0]))
				return 0;
			else
				return 1;		
	}

	public function old_remote_execution($remote_machine_name, $command_to_be_executed, $parse_output = True){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name." ".$command_to_be_executed);
		
		ini_set("expect.loguser", "Off");
		$stream = fopen("expect://ssh -o StrictHostKeyChecking=no ".TEST_USERNAME."@$remote_machine_name ".escapeshellarg($command_to_be_executed), "r");
		$cases = array (
			array (0 => "password:", 1 => "password"),
			array ("yes/no)?", "yesno")	
		);

		switch (expect_expectl ($stream, $cases)) {
			case "password":
				fwrite ($stream, TEST_PASSWORD."\n");
				break;
		 
			case "yesno":
				fwrite ($stream, "yes\n");
				break; 

			default:
				log_function::debug_log("Error in connecting to the remote host: $remote_machine_name");
		}

		if($parse_output) {
			$output = "";
			while ($line = fgets($stream)) {
				$output = $output.$line;
				if(stristr($output, "Starting memcached: [  OK  ]"))break;
				if(stristr($output, "Spawning multiple servers completed")) break;
				if(stristr($output, "Permission denied, please try again")) break;
			}
			fclose ($stream);
			log_function::debug_log($output);
			return $output;
		}
		else
			return 1;
	}

	public function old_remote_file_copy($remote_machine_name, $file_source, $file_destination, $reverse_copy = False, $parse_output = True, $sudo_copy = False){
		log_function::debug_log(general_function::get_caller_function());
		log_function::debug_log($remote_machine_name."".$file_source." ".$file_destination);
			
		ini_set("expect.loguser", "Off");

		if($reverse_copy){
			$stream = fopen ("expect://scp ".TEST_USERNAME."@$remote_machine_name:$file_source $file_destination", "r");
		} else {
			if($sudo_copy){
				$stream = fopen ("expect://scp $file_source ".TEST_USERNAME."@$remote_machine_name:/tmp/".basename($file_source), "r");
			} else {
				$stream = fopen ("expect://scp $file_source ".TEST_USERNAME."@$remote_machine_name:$file_destination", "r");	
			}	
		}
		

		$cases = array (
			array (0 => "password:", 1 => "password"),
			array ("yes/no)?", "yesno")	
		);

		switch (expect_expectl ($stream, $cases)) {
			case "password":
				fwrite ($stream, TEST_PASSWORD."\n");
				break;
		 
			case "yesno":
				fwrite ($stream, "yes\n");
				break; 

			default:
				log_function::debug_log("Error in connecting to the remote host: $remote_machine_name");
		}

		if($parse_output) {
			$output = "";
			while ($line = fgets($stream)) {
				  $output = $output.$line;
			}
			fclose ($stream);
			if ($sudo_copy){
				$output = $output.self::remote_execution($remote_machine_name, "sudo mv /tmp/".basename($file_source)." ".$file_destination);
				self::remote_execution($remote_machine_name, "sudo chmod +x ".$file_destination." ; sudo chown root ".$file_destination);
			}			
			log_function::debug_log($output);
			return $output;
		}
		else
			return 1;
	}	
}
?>