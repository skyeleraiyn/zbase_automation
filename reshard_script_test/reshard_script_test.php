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

include_once "config.php";

		
Main();


function Main(){
	global $test_machine_list, $build_combination_list;
	
	Reshard_test_function::initial_setup($test_machine_list);	

	foreach($build_combination_list as $build_combination){
		$build_combination = explode("=>", $build_combination);
		$source_build_list = trim($build_combination[0]);
		$destination_build_list = trim($build_combination[1]);
		
		log_function::result_log("Source config: ".$source_build_list);
		log_function::result_log("Destination config: ".$destination_build_list);	

		log_function::result_log("Testing scaling up servers ...");		
		$source_machine_list = array($test_machine_list[0], $test_machine_list[1]);
		$destination_machine_list = array($test_machine_list[2], $test_machine_list[3], $test_machine_list[4]);
		
		Reshard_test_function::test_reshard($source_machine_list, $destination_machine_list, $source_build_list, $destination_build_list);
				
		log_function::result_log("Testing scaling down servers ...");
		$source_machine_list = array($test_machine_list[0], $test_machine_list[1], $test_machine_list[2]);
		$destination_machine_list = array($test_machine_list[3], $test_machine_list[4]);
		Reshard_test_function::test_reshard($source_machine_list, $destination_machine_list, $source_build_list, $destination_build_list);
			
	}
}

?>
