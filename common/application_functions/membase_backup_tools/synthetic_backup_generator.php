<?php
class synthetic_backup_generator{

	public function prepare_merge_backup($hostname, $type)	{
		//This function assumes that the storage server target is empty and clean.
		$hostname = general_function::get_hostname($hostname);
		$backup_path = "/tmp/temp_backup_storage_$type";
		$cloud = MEMBASE_CLOUD;
		$disk = STORAGE_SERVER_DRIVE;
		$storage_server = STORAGE_SERVER_1;
		//Simulate an initial upload like zstore_cmd put 
		$command_to_be_executed = "sudo mkdir -p /$disk/primary/$hostname/".MEMBASE_CLOUD."; sudo chown -R storageserver.storageserver /$disk/primary/;";
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		sleep(2);
		//Check if file already exists in remote location already. if not, generate it.
		$command_to_be_executed = "du -sh $backup_path | awk '{ print $1 }'";
		if(trim(remote_function::remote_execution($storage_server, $command_to_be_executed)) == 0)	{ 
			$command_to_be_executed = "php /tmp/generate_merge_data.php $type 5 1000000 10 1000 $disk $hostname $cloud";
			$status = remote_function::remote_execution($storage_server, $command_to_be_executed);
			return $status;
		}
		$command_to_be_executed = "sudo cp -R $backup_path/* /$disk/primary/$hostname/$cloud/; sudo chown -R storageserver.storageserver /$disk/primary/$hostname/$cloud/";
		remote_function::remote_execution($storage_server, $command_to_be_executed);
		return 1;

	}
}
