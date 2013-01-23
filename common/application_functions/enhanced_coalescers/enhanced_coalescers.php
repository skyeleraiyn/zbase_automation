<?php

class enhanced_coalescers     {

      public function list_master_backups($hostname, $date = NULL)   {
                $primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
                $primary_mapping_ss = $primary_mapping['storage_server'];
                $primary_mapping_disk = $primary_mapping['disk'];
		if($date == NULL)	{
                $date = date("Y-m-d", time()+86400);
		}
                $command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/master/$date/*.mbb";
                return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
        }
		
	public function list_daily_backups($hostname, $date = NULL)	{
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
                $primary_mapping_ss = $primary_mapping['storage_server'];
                $primary_mapping_disk = $primary_mapping['disk'];
		if($date == NULL)	{
			#$date = date("Y-m-d", time()+86400);
			$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/daily/*/*.mbb";
			return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
		}
		else	{
			$list = array();
			foreach($date as $d)	{
				$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/daily/$d/*.mbb";
	                        $list = array_merge($list, array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
			}
			print("This is the list \n");
			print_r($list);
			return($list);
		}
	}

	public function list_incremental_backups($hostname)	{
		$primary_mapping = diskmapper_functions::get_primary_partition_mapping($hostname);
                $primary_mapping_ss = $primary_mapping['storage_server'];
                $primary_mapping_disk = $primary_mapping['disk'];
		$command_to_be_executed = "ls /$primary_mapping_disk/primary/$hostname/".MEMBASE_CLOUD."/incremental/*.mbb";
		return(array_filter(array_map("trim", explode("\n", remote_function::remote_execution($primary_mapping_ss, $command_to_be_executed)))));
		
	}


}
