<?php
class Verify_Class{

	//Function to verify that the no of vbuckets is consistent 
	public function verify_no_of_vbuckets(){
		$vbucket_info=vba_functions::get_cluster_vbucket_information();

		if(vba_functions::vbucket_migrator_sanity())
			log_function::debug_log("Vbucket migrator  started");
		else
			log_function::debug_log("Vbucket migrator started");	
		
		$output=vba_functions::vbucket_map_migrator_comparison();
		
		if($output)
			log_function::debug_log("Vbucketmigrator and vbucketmap are in sync");
		else
			log_function::debug_log("Vbucketmigrator and vbucketmap out of sync");
		
		$output=vba_functions::vbucket_sanity();
		
		if($output)	
			log_function::debug_log("Vbucketmap and vbucket in cluster in sync");
		else
			log_function::debug_log("Vbucketmap and vbucket in cluster out of sync");
		

		}
	
	//Function to verify that the no of key in the same
	public function verify_no_of_keys($original_vbucket_key_count_array){
		$present_vbucket_key_count_array=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($original_vbucket_key_count_array,$present_vbucket_key_count_array);
		if($output)
			log_function::debug_log("Keycounts of vbucketes matches");
		else
			log_function::debug_log("Keycounts of vbucketes dont match");
		
		}
	
	//Function to verify that total keycount is constant
	public function verify_total_key_count($total_key_count){
		$active_key_count=vba_functions::get_keycount_from_cluster('active');
		$replica_key_count=vba_functions::get_keycount_from_cluster('replica');
		if($active_key_count==$total_key_count)
			 log_function::debug_log("Active key count is correct");
		else
			log_function::debug_log("Active key count is not correct");
		
		if($replica_key_count==$total_key_count)
                         log_function::debug_log("Replica key count is correct");                 
                else
                        log_function::debug_log("Replica key count is not correct");
		
	}
	
		
	public function verify_all($original_vbucket_key_count_array){
		global $no_of_keys; 
		self::verify_no_of_vbuckets();
		self::verify_no_of_keys($original_vbucket_key_count_array);
		self::verify_total_key_count($no_of_keys);
	}
}

?>
