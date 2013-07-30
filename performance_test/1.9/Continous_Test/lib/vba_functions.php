<?php
class vba_functions {

	public function mark_disk_down_active($vb_id) {
			$disk= self::get_disk_from_id_active($vb_id);
			$machine = self::get_machine_from_id_active($vb_id);
			$command_to_be_executed = "sudo umount -l /".$disk."\n";

			return remote_function::remote_execution($machine, $command_to_be_executed);
		}
	
	public function mark_disk_down_replica($vb_id) {
			$disk= self::get_disk_from_id_replica($vb_id);
			$machine = self::get_machine_from_id_replica($vb_id);
                        $command_to_be_executed = "sudo umount -l /".$disk."\n";

                        return remote_function::remote_execution($machine, $command_to_be_executed);
                }


	public function mark_disk_up_active($vb_id) {
                        $disk= self::get_disk_from_id_active($vb_id);
                        $machine = self::get_machine_from_id_active($vb_id);
                        $command_to_be_executed = "sudo mount -l /$disk";
			echo $command_to_be_executed;
                        return remote_function::remote_execution($machine, $command_to_be_executed);
                }

	public function mark_disk_down($disk,$machine){
			$command_to_be_executed = "sudo umount -l /".$disk."\n";
			return remote_function::remote_execution($machine,$command_to_be_executed);
		}	
	public function get_disk_stats($vb_id) {
                        $disk= self::get_disk_from_id_active($vb_id);
                        $machine = self::get_machine_from_id_active($vb_id);
			$free_space = remote_function::remote_execution($machine, "df | grep /$disk | awk '{print $4}'");
			$total_space= remote_function::remote_execution($machine, "df | grep /$disk | awk '{print $2}'");
			$list_vbuckets = self::get_all_vbuckets($machine, $disk);
			return array($free_space, $total_space, $list_vbuckets);
		}

	public function get_machine_from_id($vb_id, $role) {
			global $test_machine_list;
			$not_found = True;
			foreach ($test_machine_list as $machine) {
				$vbuckets = stats_functions::get_vbucket_stats($machine);
				if($vbuckets!=False)
				{
				foreach ($vbuckets as $vb_key => $vb_details) {
					 if (!(strcmp($vb_key,"vb_".$vb_id)) and stristr($vb_details, $role)) {
						$not_found = False;
						break 2;
					 }
				}
				}
			}
			if($not_found) 
				return False;
			else
				return $machine;
		}
		
	public function get_machine_from_id_active($vb_id) {
			return self::get_machine_from_id($vb_id, "active");
		}
		
	public function get_machine_from_id_replica($vb_id) {
			return self::get_machine_from_id($vb_id, "replica");				
		}

	public function get_machine_from_id_dead($vb_id) {
			return self::get_machine_from_id($vb_id, "dead");
		}

	public function get_kvstore_from_id($vb_id, $role) {
			global $test_machine_list;
			$not_found = True;
                        foreach ($test_machine_list as $machine) {
                                $vbuckets = stats_functions::get_vbucket_stats($machine);
                                foreach ($vbuckets as $vb_key => $vb_details) {
                                         if (!(strcmp($vb_key, "vb_".$vb_id)) and stristr($vb_details, $role)) {
                                                $not_found = False;
                                                break 2;
                                         }
                                }
                        }
                        if($not_found)
                                return False;
                        else {
				$arr = explode(" ", $vb_details);
				return ($arr[count($arr) - 1]);
			}	
                                
	        }


	public function get_vbuckets_in_kvstore($machine, $kv_store, $role)	{
		$return_array = array();
		$vbuckets = stats_functions::get_vbucket_stats($machine);
		foreach($vbuckets as $vb_key => $vb_details)	{
			if(stristr($vb_details, "kvstore ".$kv_store))	{
				if(stristr($vb_details, $role))	{
					$arr = explode(" ", $vb_details);
					$return_array[] = $vb_key;
				}
			}
		}
		return $return_array;
	}

	public function get_kvstore_from_id_active($vb_id) {
			return self::get_kvstore_from_id($vb_id, "active");
		}
	
	public function get_kvstore_from_id_replica($vb_id) {
			return self::get_kvstore_from_id($vb_id, "replica");
		}		

	public function get_disk_from_id($vb_id, $role) {
			$not_found = True;
			$kv_id = self::get_kvstore_from_id($vb_id, $role);
			$machine  = self::get_machine_from_id($vb_id, $role);
			$kv_stats = stats_functions::get_kvstore_stats($machine);
			unset($kv_stats["num_kvstores"]);
			foreach ($kv_stats as $kv_key => $kv_details) {
				if($kv_details["id"] == $kv_id) {
					$not_found = False;
					$db_path = $kv_details["dbname"];
					break;
				}
			}
			if($not_found)
				return False;
			else {
				$disk = explode("/", $db_path);
				return $disk[1];
			}
		}

        public function get_disk_from_id_active($vb_id) {
                        return self::get_disk_from_id($vb_id, "active");
                }

        public function get_disk_from_id_replica($vb_id) {
                        return self::get_disk_from_id($vb_id, "replica");
                }
	
	#Accepts a vbucket_id and kills the vbucket process on the corresponding server
	public function kill_vbucketmigrator($vb_id)	{
		global $test_machine_list;
		$machine = self::get_machine_from_id($vb_id, "active");
		$vb_array = self::get_server_vbucket_information($machine);
		$pid = $vb_array[$vb_id]['pid'];
		$command_to_be_executed = "sudo kill -9 $pid";
		remote_function::remote_execution_popen($machine, $command_to_be_executed, False);
	}

	public function stop_vba($machine) {
		$command_to_be_executed = "sudo /etc/init.d/vba stop";
		remote_function::remote_execution_popen($machine,$command_to_be_executed,False);
	}
	
	

	
	#Returns an array with vbucketid as key and vbucketmigrator related info as value for a cluster(PID, SOURCE_IP, DESTINATION_IP, TAP_NAME, NIC)
	public function get_cluster_vbucket_information()	{
		global $test_machine_list;
		$return_array = array();
		for($i=0;$i<count($test_machine_list);$i++)	{
			#print_r(self::get_server_vbucket_information($test_machine_list[$i]));
			#$return_array = array_merge($return_array, self::get_server_vbucket_information($test_machine_list[$i]));
			$return_array = $return_array + self::get_server_vbucket_information($test_machine_list[$i]);
		}
		return $return_array;
	}

	#Returns an array with vbucketid as key and vbucketmigrator related info as value for a single server(PID, SOURCE_IP, DESTINATION_IP, TAP_NAME, NIC)
	public function get_server_vbucket_information($machine)	{
		$temp = array();
		$return_array = array();
		#This will change if the format of the vbucket process changes. Other option would be to use awk and look for certain keywords like -h, -b and then populate the array -> Future improvement.
		$command_to_be_executed = "ps -elf | grep vbucketmigrator | grep -v \"grep vbucketmigrator\" | grep -v \"sudo\" | tr -s ' ' | awk '{ print $19, $4, $17, $21, $23, \$NF}'";
		$vb_info = remote_function::remote_execution_popen($machine, $command_to_be_executed);
		$vb_info_array = explode("\n", remote_function::remote_execution_popen($machine, $command_to_be_executed));
		foreach($vb_info_array as $value)	{
			$vb_induvidual = explode(" ", trim($value));
			#The following if is necessary to ensure that those vbucketmigrators that are catering to more than one vbucket are handled differenlty in the else.
			if(count(explode(",", $vb_induvidual[0])) == 1)	{
				if(is_numeric($vb_induvidual[0]))	{
					$temp[$vb_induvidual[0]]['pid'] = $vb_induvidual[1];
					$source_ip = explode(":", $vb_induvidual[2]);
					$temp[$vb_induvidual[0]]['source'] = $source_ip[0];
					$dest_ip = explode(":", $vb_induvidual[3]);
					$temp[$vb_induvidual[0]]['dest'] = $dest_ip[0];
					$temp[$vb_induvidual[0]]['tapname'] = $vb_induvidual[4];
					$temp[$vb_induvidual[0]]['interface'] = $vb_induvidual[5];
					#print("$vb_induvidual[0]\n");
				}
			}
			#This case accomodates situations where there are more than one vbucket being catered to by a vbucketmigrator
			#E.g - /opt/membase/bin/vbucketmigrator -h 10.36.194.61:11211 -b 17,23 -d 10.36.166.52:11211 -N repli--5E6E8365 -A -i eth1
			else	{
				$vbuckets = explode(",", $vb_induvidual[0]);
				for($x=0;$x<count($vbuckets);$x++)	{
					$temp[$vbuckets[$x]]['pid'] = $vb_induvidual[1];
					$source_ip = explode(":", $vb_induvidual[2]);
                                        $temp[$vbuckets[$x]]['source'] = $source_ip[0];
					$dest_ip = explode(":", $vb_induvidual[3]);
                                        $temp[$vbuckets[$x]]['dest'] = $dest_ip[0];
        	                        $temp[$vbuckets[$x]]['tapname'] = $vb_induvidual[4];
	                                $temp[$vbuckets[$x]]['interface'] = $vb_induvidual[5];
				}
				
			}
		}
		return $temp;		
	}








	#Returns a list of all the vbuckets in a cluster.
	public function get_vbuckets_from_cluster( $type = "active")	{
		global $test_machine_list;
		$complete_vbucket_list = array();
		for($i=0;$i<count($test_machine_list);$i++)	{
			$complete_vbucket_list = array_merge($complete_vbucket_list, self::get_vbuckets_from_server($test_machine_list[$i], $type));
		}
		return $complete_vbucket_list;
	}
	#Returns the total no of keys stored in the cluster for active or replica vbuckets
	public function get_keycount_from_cluster($role)	{
		$total_count = 0;
		global $test_machine_list;
		for($i=0;$i<count($test_machine_list);$i++)     {
			$total_count += self::get_keycount_from_membase($test_machine_list[$i], $role);
		}
		return $total_count;
	}

	#Returns the total no of keys stored in the membase server for active or replica vbuckets
	public function get_keycount_from_membase($machine, $role)	{
		$total_count = 0;		
		$vbucket_stats = stats_functions::get_vbucket_stats(trim($machine));
	        foreach($vbucket_stats as $key => $value)       {	
        		$value_split = explode(" ", $value);
                	if($value_split[0] == $role)        {
                        	$total_count += $value_split[2];
        	        }
            	}
		return $total_count;		
	}
	#Returns the total no of keys stored in a vbucket for active or replica vbuckets
	public function get_keycount_from_vbucket($vbucket_id, $type = "active")	{
		if($type == "active")	
			$machine = self::get_machine_from_id_active($vbucket_id);
		else	
			$machine = self::get_machine_from_id_replica($vbucket_id);
		$vbucket_array = array();
                $vbucket_stats = stats_functions::get_vbucket_stats($machine);
                foreach($vbucket_stats as $key => $value)       {
                        $value_split = explode(" ", $value);
                        if($value_split[0] == $type)        {
                                $temp_key = explode("_", $key);
				if($temp_key[1] == $vbucket_id)	{
					$key_count = $value_split[2];
	                                $vbucket_array[$temp_key[1]] = $key_count;
				}
                        }
                }
		return $vbucket_array;

	}

	#Returns an array of keycount for each vbucket in cluster	
	public function get_key_count_cluster_for_each_vbucket()	{
		$vbucket_key_count_array=array();
		$vbucket_key_count_array['active']=array();
		$vbucket_key_count_array['replica']=array();
		for($i=0;$i<NO_OF_VBUCKETS;$i++)
		{	
			$vbucket_key_count_array['active'][$i]=self::get_keycount_from_vbucket($i);
			$vbucket_key_count_array['replica'][$i]=self::get_keycount_from_vbucket($i);
		}
		return $vbucket_key_count_array;
	}

	#Returns whether the key count is same for 2 vbucket key_count_arrays
        public function compare_vbucket_key_count($vbucket_key_count1,$vbucket_key_count2)      {
                $active_comparison=array_diff_assoc($vbucket_key_count1['active'],$vbucket_key_count2['active']);
                $replica_comparison=array_diff_assoc($vbucket_key_count1['replica'],$vbucket_key_count2['replica']);
                if(empty($active_comparison) and empty($replica_comparison))
                        return True;
                else
                        return False;
        }
	
		


	#Returns an array of vbuckets in a membase server for active, replica or dead vbuckets
	public function get_vbuckets_from_server($machine, $type = "active")	{
		$return_array = array();
		$vbucket_array = array();
		$vbucket_stats = stats_functions::get_vbucket_stats($machine);
		if(!$vbucket_stats)
		return False;
		$return_array = general_function::get_dual_ip($machine);
		foreach($vbucket_stats as $key => $value)	{
			$vb_type = explode(" ", $value);
			if($vb_type[0] == $type)	{
				$temp_key = explode("_", $key);
				$vbucket_array[$temp_key[1]] = $return_array;
			}
		}
		return $vbucket_array;
	}

	public function get_vbucket_from_server_active($machine)	{
		$vbucket_array=self::get_vbuckets_from_server($machine);
		return $vbucket_array;
	}

	public function get_vbucket_from_server_replica($machine){
		$vbucket_array=self::get_vbucket_from_server($machine,'replica');
		return $vbucket_array;
	}

	public function get_vbuckets_per_disk($machine, $disk) {
			$not_found = True;
			$list_vbuckets = array();
			$kv_stats = stats_functions::get_kvstore_stats($machine);
			unset($kv_stats["num_kvstores"]);
                        foreach ($kv_stats as $kv_key => $kv_details) {
				$db_name = $kv_details["dbname"];
				$disk_arr = explode("/", $db_name);
                                if($disk_arr[1] == $disk) {
                                        $not_found = False;
                                        $kv_id = $kv_details["id"];
                                        break;
                                }
                        }
			if($not_found)
				return False;
			else {
				$vbuckets = stats_functions::get_vbucket_stats($machine);
				$list_vbuckets=array('active'=>array(),'replica'=>array(),'dead'=>array(),'unknown'=>array());
                                foreach ($vbuckets as $vb_key => $vb_details) {
					 $arr = explode(" ", $vb_details);
					 
					 
					 $kv_id_from_vb = $arr[count($arr) -1];
					 $role = $arr[0];

					 if($kv_id == $kv_id_from_vb) {
						
						$vb_key=preg_replace("#vb_#","",$vb_key);
						if($role === 'active')	
							array_push($list_vbuckets['active'], $vb_key); 
						else if($role === 'replica')
							array_push($list_vbuckets['replica'], $vb_key);
						else if($role === 'dead')
							array_push($list_vbuckets['dead'],$vb_key);
						else
							array_push($list_vbuckets['unknown'],$vb_key);

						
                             		}
				
			     }
			}

			return $list_vbuckets;
		}	

	#Compares the vbucket info from the VBS and verify it with actual cluster info
	public function vbucket_sanity(){
			$vbucket_map=vbs_functions::get_vb_map();
			for($i=0;$i<NO_OF_VBUCKETS;$i++)
			{
				$secondary_ip=general_function::get_secondary_ip(self::get_machine_from_id_replica($i));
				$primary_ip = self::get_machine_from_id_replica($i);			
	
				if( $vbucket_map[$i]['replica'] == $secondary_ip or $vbucket_map[$i]['replica'] == $primary_ip)
					{  }	
				else
					{
					echo $vbucket_map[$i]['replica']." ".$primary_ip." ".$secondary_ip;
					log_function::debug_log( "Vbucket mismatch in the replica ".$i);
					return False;
					}
				
	
				
				$secondary_ip=general_function::get_secondary_ip(self::get_machine_from_id_active($i));
                                $primary_ip = self::get_machine_from_id_active($i);
	

				if( $vbucket_map[$i]['active'] == $secondary_ip or $vbucket_map[$i]['active'] == $primary_ip )
					{   }
				else
					{
					log_function::debug_log( "Vbucket mismatch in the active ".$i);
					return False;
					}

			}
			
			
			return True;
		}

	public function vbucket_distribution_sanity(){
			global $test_machine_list;
			//$vbucket_per_server=
			echo NO_OF_VBUCKETS;
			$vbucket_per_machine=2*NO_OF_VBUCKETS/count($test_machine_list);
			foreach($test_machine_list as $machine)
			{
				
			}	
		}		
	public function vbucket_migrator_sanity(){
			$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
			for($i=0 ;$i< NO_OF_VBUCKETS ;$i++)
			{	
				if ( !isset($vbucketmigrator_map[$i]) )
				{
					log_function::debug_log( "Vbucketmigrator not started for vbid ".$i);
					return False;
				}
			}
			return True;
		}	

	public function vbucket_map_migrator_comparison(){
			global $test_machine_list;
			$vbucketmigrator_map=vba_functions::get_cluster_vbucket_information();
			$vbucket_map=vbs_functions::get_vb_map();
			$flag=True;
			for($i=0;$i< NO_OF_VBUCKETS ;$i++)
			{
				if($vbucket_map[$i]['active']==$vbucketmigrator_map[$i]['source'] and $vbucket_map[$i]['replica']==$vbucketmigrator_map[$i]['dest'])
					$flag = True;
				else
					{
					$flag= False;
					log_function::debug_log("Vbucketmigrator distribution and vbucketmap configuration  different for ".$i);
					log_function::debug_log("Active vbucket ip from vbucket map ".$vbucket_map[$i]['active']);
					log_function::debug_log("Replica vbucket ip from vbucket map ".$vbucket_map[$i]['replica']);
					log_function::debug_log("Vbucketmigrator source ".$vbucketmigrator_map[$i]['source']);
					log_function::debug_log("Vbucketmigrator destination ".$vbucketmigrator_map[$i]['dest']);
					break;
					}
			}
			return $flag;
		}
	public function verify_server_not_present_in_map($server){
			$vb_map=vbs_functions::get_vb_map();
			$secondary_server = general_function::get_secondary_ip($server);
			foreach($vb_map as $vb_id=>$machine)
			{
				if($machine == $server or $machine == $secondary_server)
					return False;
			}
			return True;
		}

}	
?>
