/*
 *	 Copyright 2013 Zynga Inc
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
<?php
class Verify_Class{

	//Function to verify that the no of vbuckets is consistent 
	public function verify_no_of_vbuckets(){
		$vbucket_info=vba_functions::get_cluster_vbucket_information();

		if(vba_functions::vbucket_migrator_sanity())
			log_function::result_log("INFO Vbucket migrator  started");
		else
			log_function::result_log("ERROR Vbucket migrator started");	
		
		$output=vba_functions::vbucket_map_migrator_comparison();
		
		if($output)
			log_function::result_log("INFO Vbucketmigrator and vbucketmap are in sync");
		else
			log_function::result_log("ERROR Vbucketmigrator and vbucketmap out of sync");
		
		$output=vba_functions::vbucket_sanity();
		
		if($output)	
			log_function::result_log("INFO Vbucketmap and vbucket in cluster in sync");
		else
			log_function::result_log("ERROR Vbucketmap and vbucket in cluster out of sync");
		

		}
	
	//Function to verify that the no of key in the same
	public function verify_no_of_keys($original_vbucket_key_count_array){
		$present_vbucket_key_count_array=vba_functions::get_key_count_cluster_for_each_vbucket();
		$output=vba_functions::compare_vbucket_key_count($original_vbucket_key_count_array,$present_vbucket_key_count_array);
		if($output)
			log_function::result_log("INFO Keycounts of vbucketes matches");
		else
			log_function::result_log("ERROR Keycounts of vbucketes dont match");
		
		}
	
	//Function to verify that total keycount is constant
	public function verify_total_key_count($total_key_count){
		$active_key_count=vba_functions::get_keycount_from_cluster('active');
		$replica_key_count=vba_functions::get_keycount_from_cluster('replica');
		if($active_key_count==$total_key_count)
			 log_function::result_log("INFO Active key count is correct");
		else
			log_function::result_log("ERROR Active key count is not correct");
		
		if($replica_key_count==$total_key_count)
                         log_function::result_log("INFO Replica key count is correct");                 
                else
                        log_function::result_log("ERROR Replica key count is not correct");
		
	}
	
		
	public function verify_all($original_vbucket_key_count_array){
		global $no_of_keys; 
		self::verify_no_of_vbuckets();
		self::verify_no_of_keys($original_vbucket_key_count_array);
		self::verify_total_key_count($no_of_keys);
	}
}

?>
