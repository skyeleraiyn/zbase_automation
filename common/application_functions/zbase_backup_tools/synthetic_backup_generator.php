<?php
class synthetic_backup_generator{

	public function prepare_merge_backup($hostname, $type, $role = "primary", $no_of_backups = 5, $no_of_keys = 1000000, $no_of_checkpoints = 10, $size_of_value = 1000)  {
		//This function assumes that the storage server target is empty and clean.
		$hostname = general_function::get_hostname($hostname);
		$backup_path = "/tmp/temp_backup_storage_$type";
		$cloud = ZBASE_CLOUD;
		$disk = STORAGE_SERVER_DRIVE;
		if($role == "primary") {
			$storage_server = STORAGE_SERVER_1;
		}
		else if ($role == "secondary") {
			$storage_server = STORAGE_SERVER_2;
		}
		//Simulate an initial upload like zstore_cmd put
		self::make_directory($hostname, $role);
		//Check if file already exists in remote location already. if not, generate it.
		$command_to_be_executed = "du -sh $backup_path | awk '{ print $1 }'";
		if(trim(remote_function::remote_execution($storage_server, $command_to_be_executed)) == 0)	{
			$command_to_be_executed = "php /tmp/generate_merge_data.php $type $no_of_backups $no_of_keys $no_of_checkpoints $size_of_value $disk $hostname $cloud $role";
			$status = remote_function::remote_execution($storage_server, $command_to_be_executed);
		}
		$command_to_be_executed = "sudo cp -R $backup_path/* /$disk/$role/$hostname/$cloud/; sudo chown -R storageserver.storageserver /$disk/$role/$hostname/$cloud/";
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		self::link_backup_data($hostname, $role);
		return 1;

	}

    public function prepare_merge_backup_multivb($vb_id, $type, $role = "primary", $no_of_backups = 5, $no_of_keys = 1000000, $no_of_checkpoints = 10, $size_of_value = 1000) {
		$vb_group = diskmapper_functions::get_vbucket_group("vb_".$vb_id);
        log_function::debug_log("Preparing syntetic backup for vb id:".$vb_id." vb_group:".$vb_group);
        $backup_path = "/tmp/temp_backup_storage_$type";
		if($role == "primary") {
            $map = diskmapper_functions::get_primary_partition_mapping($vb_group);
            $disk = $map['disk'];
			$storage_server = $map['storage_server'];
		}
		else if ($role == "secondary") {
            $map = diskmapper_functions::get_secondary_partition_mapping($vb_group);
            $disk = $map['disk'];
			$storage_server = $map['storage_server'];
        }
		//Check if file already exists in remote location already. if not, generate it.
		$command_to_be_executed = "du -sh $backup_path | awk '{ print $1 }'";
		if(trim(remote_function::remote_execution($storage_server, $command_to_be_executed)) == 0)	{
			$command_to_be_executed = "php /tmp/generate_backup_multivb.php $type $no_of_backups $no_of_keys $no_of_checkpoints $size_of_value $disk $vb_group $vb_id $role";
			$status = remote_function::remote_execution($storage_server, $command_to_be_executed);
		}
		$command_to_be_executed = "sudo cp -R $backup_path/* /$disk/$role/$vb_group/vb_$vb_id/; sudo chown -R storageserver.storageserver /$disk/$role/$vb_group/vb_$vb_id/";
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		self::link_backup_data($vb_group, $role);
		return 1;

	}


	public function make_directory($hostname, $role = "primary") {
                $hostname = general_function::get_hostname($hostname);
                $disk = STORAGE_SERVER_DRIVE;
                if($role == "primary") {
                        $storage_server = STORAGE_SERVER_1;
                }
                else if ($role == "secondary") {
                        $storage_server = STORAGE_SERVER_2;
                }
		$command_to_be_executed = "sudo mkdir -p /$disk/$role/$hostname/".ZBASE_CLOUD."; sudo chown -R storageserver.storageserver /$disk/$role/;";
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		self::link_backup_data($hostname, $role);
		sleep(2);
		return 1;
	}


	public function link_backup_data($vb_group, $role = "primary") {
                if($role == "primary") {
                        $map = diskmapper_functions::get_primary_partition_mapping($vb_group);
                        $disk = $map['disk'];
                        $storage_server = $map['storage_server'];
                }
                else if ($role == "secondary") {
                        $map = diskmapper_functions::get_primary_partition_mapping($vb_group);
                        $disk = $map['disk'];
                        $storage_server = $map['storage_server'];
                }
		$command_to_be_executed = "sudo mkdir -p /var/www/html/".GAME_ID.";sudo ln -s /var/www/html/zbase_backup/".$disk."/$role/$vb_group  /var/www/html/".GAME_ID."/$vb_group ; sudo chown -R storageserver.storageserver /var/www/html/".GAME_ID;
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		sleep(5);
		return True;
	}

}
