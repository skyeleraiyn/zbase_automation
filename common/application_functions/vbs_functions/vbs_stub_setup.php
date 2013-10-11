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
class vbs_stub_setup{

	public function copy_ip_mapper_files($remote_machine_name = VBS_IP) 
	{
		remote_function::remote_execution($remote_machine_name, "sudo mkdir -p /var/tmp/IPMapper");
                remote_function::remote_execution($remote_machine_name, "sudo rm /var/tmp/IPMapper/Constants.py*;sudo rm /var/tmp/IPMapper/IPMapper.py*");
		remote_function::remote_file_copy($remote_machine_name, HOME_DIRECTORY."common/misc_files/1.9_files/Constants.py", "/var/tmp/IPMapper/", False, True, True);
                remote_function::remote_file_copy($remote_machine_name, HOME_DIRECTORY."common/misc_files/1.9_files/IPMapper.py", "/var/tmp/IPMapper/", False, True, True);
		remote_function::remote_execution($remote_machine_name, "sudo chmod 777 -R /var/tmp/IPMapper/");
		remote_function::remote_file_copy($remote_machine_name, HOME_DIRECTORY."common/misc_files/1.9_files/IPM", "/etc/init.d/", False, True, True);
	}

	public function vbucket_server_service($command, $remote_machine_name = VBS_IP) {
		return service_function::control_service($remote_machine_name, VBS_SERVICE, $command);
	}

	public function ip_mapper_service($command, $remote_machine_name = VBS_IP) {
                return service_function::control_service($remote_machine_name, IP_MAPPER_SERVICE, $command);
	}

	public function clear_vbs_log_file($remote_machine){
                file_function::clear_log_files($remote_machine, array(VBS_LOG));
        }
        
	public function clear_ip_mapper_log_file($remote_machine){
                file_function::clear_log_files($remote_machine, array(IPMAPPER_LOG));
        }

	public function initial_setup($remote_machine_name = VBS_IP) 
	{
		self::copy_ip_mapper_files();
		self::build_vbucketserver_config(NO_OF_VBA,NO_OF_VBUCKETS,VBS_IP);
		remote_function::remote_file_copy($remote_machine_name, HOME_DIRECTORY."common/misc_files/1.9_files/vbucketserver_sysconfig", "/etc/sysconfig/vbucketserver", False, True, True);
		self::reset_vbs();
	}

	public function build_vbucketserver_config($no_of_vba = NO_OF_VBA, $no_of_vbuckets = NO_OF_VBUCKETS, $remote_machine_name = VBS_IP) // will just change the config file 
	{
		$sysConfig = Array();
		$sysConfig['cluster1'] = Array();
		$sysConfig['cluster1']['Port'] = 11114;
		$sysConfig['cluster1']['Vbuckets'] = $no_of_vbuckets;
		$sysConfig['cluster1']['Replica'] = 1;
		$sysConfig['cluster1']['SecondaryIps'] = Array();
		$sysConfig['cluster1']['Capacity'] = 100;
		$sysConfig['cluster1']['Servers'] = Array();

		for($i=0;$i<$no_of_vba;$i++)
			$sysConfig['cluster1']['Servers'][$i] = '127.0.0.'.strval(VBA_START_IP+$i).':11211';

		file_put_contents(HOME_DIRECTORY.'common/misc_files/1.9_files/vbucketserver_sysconfig',json_encode($sysConfig));
	}

	public function push_vbucketserver_config($no_of_vba = NO_OF_VBA, $no_of_vbuckets = NO_OF_VBUCKETS, $remote_machine_name = VBS_IP)	// wil push the config to the remote server
	{
		self::build_vbucketserver_config($no_of_vba, $no_of_vbuckets, $remote_machine_name);
		file_function::add_modified_file_to_list($remote_machine_name, '/etc/sysconfig/vbucketserver');
		remote_function::remote_file_copy($remote_machine_name, HOME_DIRECTORY."common/misc_files/1.9_files/vbucketserver_sysconfig", "/etc/sysconfig/vbucketserver", False, True, True);
	}
	public function reset_vbs($remote_machine_name = VBS_IP) 
	{
		//self::clear_vbs_log_file($remote_machine_name);
                //self::clear_ip_mapper_log_file($remote_machine_name);
        	self::vbucket_server_service("restart");
		remote_function::remote_execution_popen(VBS_IP, "sudo /etc/init.d/IPM restart", FALSE);
	}


}
?>
