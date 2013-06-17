<?php
class synthetic_backup_generator{

	public function prepare_merge_backup($hostname, $type, $role = "primary", $no_of_backups = 5, $no_of_keys = 1000000, $no_of_checkpoints = 10, $size_of_value = 1000)  {
		//This function assumes that the storage server target is empty and clean.
		$hostname = general_function::get_hostname($hostname);
		$backup_path = "/tmp/temp_backup_storage_$type";
		$cloud = MEMBASE_CLOUD;
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

	public function make_directory($hostname, $role = "primary") {
                $hostname = general_function::get_hostname($hostname);
                $disk = STORAGE_SERVER_DRIVE;
                if($role == "primary") {
                        $storage_server = STORAGE_SERVER_1;
                }
                else if ($role == "secondary") {
                        $storage_server = STORAGE_SERVER_2;
                }
		$command_to_be_executed = "sudo mkdir -p /$disk/$role/$hostname/".MEMBASE_CLOUD."; sudo chown -R storageserver.storageserver /$disk/$role/;";
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		self::link_backup_data($hostname, $role);
		sleep(2);
		return 1;
	}


	public function link_backup_data($hostname, $role = "primary") {
                $hostname = general_function::get_hostname($hostname);
                $disk = STORAGE_SERVER_DRIVE;
                if($role == "primary") {
                        $storage_server = STORAGE_SERVER_1;
                }
                else if ($role == "secondary") {
                        $storage_server = STORAGE_SERVER_2;
                }
		$command_to_be_executed = "sudo mkdir -p /var/www/html/".GAME_ID.";sudo ln -s /var/www/html/membase_backup".$disk."/$role/$hostname  /var/www/html/".GAME_ID."/$hostname ; sudo chown -R storageserver.storageserver /var/www/html/".GAME_ID;
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		sleep(5);
		return True;
	} 

}
