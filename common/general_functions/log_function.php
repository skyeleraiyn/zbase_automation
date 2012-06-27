<?php
class log_function{
	public function result_log($message_to_log){

		global $result_file;
		echo $message_to_log."\n";
		self::log_to_file($result_file, $message_to_log);
		self::debug_log($message_to_log);
	}

	public function debug_log($message_to_log) {
		global $debug_file, $debug;
		if(!isset($debug)) $debug = False;
		if(is_array($message_to_log)) 
			$message_to_log = serialize($message_to_log);
		if ($debug) echo $message_to_log."\n";
		self::log_to_file($debug_file, $message_to_log);

	}

	public function exit_log_message($message_to_log){
		self::error_log_message($message_to_log);
		exit;
	}
	
	public function error_log_message($message_to_log){
		return self::result_log("Error: ".$message_to_log);
	}	
	public function log_to_file($file_name, $message_to_log){
		return file_function::write_to_file($file_name, $message_to_log, "a");
	}		
}	
?>