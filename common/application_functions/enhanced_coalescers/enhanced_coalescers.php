<?php

class enhanced_coalescers     {

	public function list_master_backups($hostname, $date = NULL)   {
		$hostname = general_function::get_hostname($hostname);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		if($date == NULL)	{
			$date = date("Y-m-d", time()+86400);
		}
		$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/master/$date/*.mbb";
		return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
	}
	
	public function list_master_backups_multivb($vb_id, $date = NULL) {
                $vb_id="vb_".$vb_id;
                $machine = diskmapper_functions::get_vbucket_ss($vb_id);
                $path = diskmapper_functions::get_vbucket_path($vb_id);
                if($date == NULL) {
			$date = date("Y-m-d", time());
                }
              	$command_to_be_executed = "ls ".$path."/master/$date/*.mbb"; 
                return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($machine, $command_to_be_executed)))));
		
	}

	public function get_total_backup_key_count() {
		$array_backups = array();
		$count = 0;
		for($vb_id = 0; $vb_id < NO_OF_VBUCKETS; $vb_id ++) {
			$machine[$vb_id] = diskmapper_functions::get_vbucket_ss("vb_".$vb_id);
			$array_backups[$vb_id] =array_merge($array_backups, self::list_master_backups_multivb($vb_id));
		}
		foreach ($array_backups as $id=> $backup_list) {
			foreach ($backup_list as $backup) {
				$temp_count = sqlite_functions::sqlite_count($machine[$vb_id], "cpoint_op", $backup);
				if(!stristr($temp_count,"no such")) {
					$count+=$temp_count;
				}
			}		
		}
		return $count;
	}
		


	public function list_daily_backups($hostname, $date = NULL)	{
		$hostname = general_function::get_hostname($hostname);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		if($date == NULL)	{
			$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/daily/*/*.mbb";
			return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
		} else	{
			$list = array();
			foreach($date as $d)	{
				$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/daily/$d/*.mbb";
				$list = array_merge($list, array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
			}
			return($list);
		}
	}
	
	public function list_daily_backups_multivb($vb_id, $date = NULL) {
                $vb_id="vb_".$vb_id;
                $machine = diskmapper_functions::get_vbucket_ss($vb_id);
                $path = diskmapper_functions::get_vbucket_path($vb_id);
                if($date == NULL) {
                        $command_to_be_executed = "ls ".$path."/daily/*/*.mbb";
         	        return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($machine, $command_to_be_executed)))));
                }
                else {
			$list = array();
			foreach($date as $d) {
         	               $command_to_be_executed = "ls ".$path."/daily/$d/*.mbb";
                               $list = array_merge($list, array_filter(array_map("trim", explode("\n", remote_function::remote_execution($machine, $command_to_be_executed)))));
                	}
        	        return($list);
		}

	}


	public function list_incremental_backups($hostname, $type="mbb")	{
		$hostname = general_function::get_hostname($hostname);
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		if($type == "mbb")	{
			$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/incremental/*.mbb";
		} else { $command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/incremental/*.split"; }
		return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));

	}

	public function list_incremental_backups_multivb($vb_id, $type="mbb") {
		$vb_id="vb_".$vb_id;
		$machine = diskmapper_functions::get_vbucket_ss($vb_id);
		$path = diskmapper_functions::get_vbucket_path($vb_id);
		if($type == "mbb") {
			$command_to_be_executed = "ls ".$path."/incremental/*.mbb";
		}
		else {
			$command_to_be_executed = "ls ".$path."/incremental/*.split";
		}
		return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($machine, $command_to_be_executed)))));
	}


	public function compare_dirty_file_after_daily_merge($hostname)	{
		$constructed_array = array();
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$hostname = general_function::get_hostname($hostname);
		//Obtaining dirty file from respective storage server for respective disk.
		remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);
		$dirty_file_array = explode("\n", file_function::read_from_file("/tmp/dirty"));
		//Query log file to get filename of newly created backup.mbb file.
		$backup_files_created = explode("\n", file_function::query_log_files("/var/log/membasebackup.log", "Creating backup file", $primary_mapping_ss));	
		foreach($backup_files_created as $backup_file_created)  {
			$backup_file = trim(substr($backup_file_created, strrpos($backup_file_created, " ")));
			array_push($constructed_array, $backup_file);
		}
		$general_daily_path = substr($backup_file, 0, strrpos($backup_file, "/"));
		$general_path = "/$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD;
		$date_daily = end(explode("/", $general_daily_path));
		$split_file = substr($backup_file, 0, -10);
                $dirty_file_array = array_filter($dirty_file_array);
		array_push($constructed_array , $split_file.".split", $general_daily_path."/done", $general_daily_path."/complete", $general_path."/incremental/done-$date_daily", $general_path."/incremental/manifest.del");
		log_function::debug_log("original\n:".print_r($dirty_file_array,true));
		log_function::debug_log("expected\n:".print_r($constructed_array,true));
		if(count(array_diff_assoc($constructed_array, $dirty_file_array))>0)	{ return False;}
		return True;
	}	


	public function compare_dirty_file_after_master_merge($hostname)	{
		$constructed_array = array();
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$primary_mapping_disk = $primary_mapping['disk'];
		$hostname = general_function::get_hostname($hostname);
		$sunday_day_difference = storage_server_functions::get_sunday_date_difference();
		$last_sunday = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $sunday_day_difference, date("Y")));
		//Obtaining dirty file from respective storage server for respective disk.
		remote_function::remote_file_copy($primary_mapping_ss, "/$primary_mapping_disk/dirty", "/tmp/dirty", True);
		$dirty_file_array = explode("\n", file_function::read_from_file("/tmp/dirty"));
		//Query log file to get filename of newly created backup.mbb file.
		$backup_files_created = explode("\n", file_function::query_log_files("/var/log/membasebackup.log", "Creating backup file", $primary_mapping_ss));
		foreach($backup_files_created as $backup_file_created)	{	
			$backup_file = trim(substr($backup_file_created, strrpos($backup_file_created, " ")));		
			array_push($constructed_array, $backup_file);
		}
		$general_master_path = substr($backup_file, 0, strrpos($backup_file, "/"));
		$split_file = substr($backup_file, 0, -10);
		$dirty_file_array = array_filter($dirty_file_array);
		array_push($constructed_array, $split_file.".split", $general_master_path."/merged-$last_sunday", $general_master_path."/done", $general_master_path."/complete");
		log_function::debug_log("original\n:".print_r($dirty_file_array,true));
		log_function::debug_log("expected\n:".print_r($constructed_array, true));
		if(count(array_diff_assoc($constructed_array, $dirty_file_array))>0)    { return False;}
		return True;

	}

	public function sqlite_total_count($hostname, $type, $date = NULL)    {
		if($type=="daily")      {
			if($date == NULL)	{
				$backup_list = self::list_daily_backups($hostname);
			} else 	{
				$backup_list = self::list_daily_backups($hostname, $date);
			}
		} else if($type=="incremental")   {
			$backup_list = self::list_incremental_backups($hostname);
		} else if($type == "master")	{
			$backup_list = self::list_master_backups($hostname, $date);
		}
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
		$primary_mapping_ss = $primary_mapping['storage_server'];
		$total_count = 0;
		foreach($backup_list as $file)  {
			if($file!=""){
				$count = sqlite_functions::sqlite_select($primary_mapping_ss, "count(*)", "cpoint_op", trim($file));
				$total_count = $total_count + $count;
			}
		}
		return($total_count);
	}

}
