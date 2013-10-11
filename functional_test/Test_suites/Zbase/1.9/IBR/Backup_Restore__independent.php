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

abstract class Basic_IBR_TestCase extends ZStore_TestCase {

    public function test_setup()   {
		$this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, True, True));
	        global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
            global $storage_server_pool;
                foreach ($storage_server_pool as $ss) {
                        backup_tools_functions::set_backup_const($ss, "BACKUP_INTERVAL", "300", False);
                 }
	}

	public function test_setup_pump() {
        $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
        global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
		$this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		zbase_backup_setup::start_cluster_backup_daemon();
		sleep(120);
		$count = enhanced_coalescers::get_total_backup_key_count();
		$this->assertEquals(25600, $count, "count mismatch");

	}


	public function test_incremental_backups() {
		$this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
        global $test_machine_list;
        foreach ($test_machine_list as $test_machine) {
              flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100,3,102400));
        zbase_backup_setup::start_cluster_backup_daemon();
		sleep(350);
        zbase_backup_setup::stop_cluster_backup_daemon();
        $count = enhanced_coalescers::get_total_backup_key_count();
        $this->assertEquals(25600, $count, "count mismatch");
        $this->assertTrue(Data_generation::pump_keys_to_cluster(51200, 100, 3,102400));
        zbase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
        zbase_backup_setup::stop_cluster_backup_daemon();
        for($i=0;$i<NO_OF_VBUCKETS;$i++) {
			$backups = enhanced_coalescers::list_incremental_backups_multivb($i, "split");
            foreach ($backups as $backup) {
    			$this->assertFalse(stristr($backup, "no such"), "backup not found for vb_$i");
            }
		}

	}


	public function test_integrity() {
        global $test_machine_list;
        global $moxi_machines;
	    $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, True));
        foreach ($test_machine_list as $test_machine) {
                flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        $cluster = new Memcache;
		$cluster->addserver($moxi_machines[0],MOXI_PORT_NO);
        $cluster->set("test_verify_key", "verify_value_123");

        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        zbase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
		$not_found = True;
		$value_backup = NULL;
		$backups = array();
		for($i=0;$i<NO_OF_VBUCKETS;$i++) {
			$backup_list = enhanced_coalescers::list_master_backups_multivb($i);
			$machine = diskmapper_functions::get_vbucket_ss("vb_".$i);
            foreach ($backup_list as $backup) {
    			$key = sqlite_functions::sqlite_select($machine, "key", "cpoint_op", $backup);
	    		if(stristr($key, "test_verify_key")) {
		    		$not_found = False;
		    		$value_backup = sqlite_functions::sqlite_select($machine, "val", "cpoint_op", $backup, "where key like 'test_verify_key'");
                    if(strcmp($value_backup,"")) {
                        break 2;
                    }
			    }
		    }
        }
		$value = $cluster->get("test_verify_key");
		$this->assertFalse($not_found, "Key not found");
		$this->assertEquals($value, $value_backup, "value found to be not equal");


	}

	public function test_integrity_restore() {
        global $test_machine_list;
        global $moxi_machines;
	    $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
        foreach ($test_machine_list as $test_machine) {
                flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        $cluster = new Memcache;
		$cluster->addserver($moxi_machines[0],MOXI_PORT_NO);
        $cluster->set("test_verify_key", "verify_value_123");
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        zbase_backup_setup::start_cluster_backup_daemon();
        sleep(180);
		$not_found = True;
		$value_backup = NULL;
		$backups = array();
		for($i=0;$i<NO_OF_VBUCKETS;$i++) {
			$backup_list = enhanced_coalescers::list_master_backups_multivb($i);
			$machine = diskmapper_functions::get_vbucket_ss("vb_".$i);
            foreach ($backup_list as $backup) {
    			$key = sqlite_functions::sqlite_select($machine, "key", "cpoint_op", $backup);
	    		if(stristr($key, "test_verify_key")) {
		    		$not_found = False;
		    		$value_backup = sqlite_functions::sqlite_select($machine, "val", "cpoint_op", $backup, "where key like 'test_verify_key'");
                    $vbucket_id = $i;
                    if(strcmp($value_backup,"")) {
                        break 2;
                    }
			    }
		    }
        }
 		$this->assertFalse($not_found, "Key not found");
        $vba = vba_functions::get_machine_from_id_active($vbucket_id);
        $restore_output = mb_restore_commands::restore_to_cluster($vba, $vbucket_id);
		$value = $cluster->get("test_verify_key");
		$this->assertEquals($value, $value_backup, "value found to be not equal");
        $this->assertContains("Restore completed successfully", $restore_output, "Success message not found");

	}




	public function test_backups_with_no_storage_allocated() {
        global $test_machine_list;
        global $storage_server_pool;
        diskmapper_setup::reset_diskmapper_storage_servers($storage_server_pool, True);
        cluster_setup::setup_zbase_cluster();
        sleep(30);
        foreach ($test_machine_list as $test_machine) {
            flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        zbase_backup_setup::stop_cluster_backup_daemon();
        zbase_backup_setup::clear_cluster_backup_log_file();
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        zbase_backup_setup::start_cluster_backup_daemon($storage_server_pool[0]);
        sleep(30);
        $log_content = remote_function::remote_execution($storage_server_pool[0], "cat ".CLUSTER_BACKUP_LOG_FILE);
        $this->assertContains("Fatal: Could not get mapping from disk mapper", $log_content, "error message not found");
        $this->assertContains("Failed to init backup daemon. Exiting...", $log_content, "second error message not found");
	}


	public function test_backups_when_VBA_down() {
                global $test_machine_list;
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                foreach ($test_machine_list as $test_machine) {
                    flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $ss = diskmapper_functions::get_vbucket_ss("vb_10");
                $ss_path = diskmapper_functions::get_vbucket_path("vb_10");
                $vba = vba_functions::get_machine_from_id_replica(10);
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100, 2));
                $pid = pcntl_fork();
                if($pid == -1) { die("could not fork");}
                else if ($pid) {
                    zbase_backup_setup::clear_cluster_backup_log_file($ss);
                    zbase_backup_setup::start_cluster_backup_daemon();
                    $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100, 2));
                     foreach($test_machine_list as $test_machine) {
                        echo "\n machine:$test_machine".remote_function::remote_execution($test_machine, "echo stats |nc 0 11211 | grep tap_mutation_received");
                    }
                   sleep(200);
                    $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100, 2));
                     foreach($test_machine_list as $test_machine) {
                        echo "\n machine: $test_machine ".remote_function::remote_execution($test_machine, "echo stats |nc 0 11211 | grep tap_mutation_received");
                    }
                   sleep(150);
                    $backups = enhanced_coalescers::list_incremental_backups_multivb(10);
                    var_dump($backups);
                    log_function::debug_log("after \n".remote_function::remote_execution("netops-demo-mb-339.va2.zynga.com","echo stats vbucket | nc 0 11211"));
                }
                else {
                    sleep(100);
                    log_function::debug_log("before \n".remote_function::remote_execution("netops-demo-mb-339.va2.zynga.com","echo stats vbucket | nc 0 11211"));
                    service_function::control_service($vba, VBA_SERVICE, "stop");
                    exit;
                }
	}


	public function test_backups_killed_in_progress() {
                global $storage_server_pool;
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                foreach ($test_machine_list as $test_machine) {
                    flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }

                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
       	        $pid = pcntl_fork();
                foreach ($storage_server_pool as $ss) {
                    remote_function::remote_execution($ss,"sudo killall -9 python26");
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(300);
                //verify incremental backups

        }


	public function test_backups_after_downshard() {
        		global $test_machine_list;
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, False, True));
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(30);
		        $this->assertTrue(vbs_functions::remove_server_from_cluster($test_machine_list[0]), "Couldn't downshard");
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                sleep(300);
                //verify incremental backups;
	}


	public function test_backups_after_upshard() {
        		global $spare_machine_list;
                global $test_machine_list;
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, False, True));
                foreach ($test_machine_list as $test_machine) {
                     flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		        $this->assertEquals(vbs_functions::add_server_to_cluster($spare_machine_list[0]), 1, "Couldn't upshard");
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();

	}


	public function est_backup_after_storage_disk_failover() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
//Start Daemon
//Stop Daemon
		$vb_group = diskmapper_functions::get_vbucket_group("vb_10");
		$this->assertTrue(diskmapper_functions::add_bad_disk($vb_group, "primary"),"Failed adding bad disk entry");
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		//Start Daemon
		// Stop Daemon
		$backups = enhanced_coalescers::list_incremental_backups_multivb(10, "split");
		$this->assertFalse(stristr($backups, "no such"), "incremental backups not found after disk failover");
	}

	public function est_backup_after_storage_server_failover() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
//Start Daemon
//Stop Daemon
		$server = diskmapper_functions::get_vbucket_ss("vb_5");
		$command_to_be_executed = "sudo killall -9 python26; sudo /etc/init.d/httpd stop;sudo killall -9 python26";
		remote_function::remote_execution($server, $command_to_be_executed);
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
//Start Daemon on all boxes except $server
                $backups = enhanced_coalescers::list_incremental_backups_multivb(10, "split");
                $this->assertFalse(stristr($backups, "no such"), "incremental backups not found after server failover");

	}





	public function test_restore_basic() {
        $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
        global $test_machine_list;
        global $storage_server_pool;
        foreach ($test_machine_list as $test_machine) {
                flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        zbase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
        zbase_backup_setup::stop_cluster_backup_daemon();
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        zbase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
		$count = vba_functions::get_keycount_from_vbucket("1", "replica");
		$machine = vba_functions::get_machine_from_id_replica("1");
		$restore_output = mb_restore_commands::restore_to_cluster($machine, 1);
		$count_new = vba_functions::get_keycount_from_vbucket("1", "replica");
        var_dump($count, $count_new);
		$this->assertEquals($count_new, $count, "mismatch in count");
        $this->assertContains("Restore completed successfully", $restore_output, "Success message not found");
	}

	public function est_restore_after_downshard() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        //      Start Daemon;
        //      Stop Daemon;
		        $machine = vba_functions::get_machine_from_id_replica(1);
                $this->assertTrue(vbs_functions::remove_server_from_cluster($machine), "Couldn't downshard");
                $machine_new = vba_functions::get_machine_from_id_replica(1);
                mb_restore_commands::restore_to_cluster($machine_new, 1);
	}



        public function est_restore_after_upshard() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, True, True));
                global $test_machine_list;
        		global $spare_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        //      Start Daemon;
        //      Stop Daemon;
                $machine_new = vba_functions::get_machine_from_id_replica(1);
                $this->assertTrue(vbs_functions::add_server_to_cluster($spare_machine_list[0]), "Couldn't upshard");
                mb_restore_commands::restore_to_cluster($machine_new, 1);


	}


	public function est_restore_after_storage_disk_failure() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        //      Start Daemon;
        //      Stop Daemon;
                $vb_group = diskmapper_functions::get_vbucket_group("vb_10");
                $this->assertTrue(diskmapper_functions::add_bad_disk($vb_group, "primary"),"Failed adding bad disk entry");
                $machine_new = vba_functions::get_machine_from_id_replica(10);
                mb_restore_commands::restore_to_cluster($machine_new, 1);

	}

        public function test_restore_basic_only_master() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                mb_restore_commands::restore_to_cluster($machine, 1);
                $count_new = vba_functions::get_keycount_from_vbucket("1", "replica");
                $this->assertEquals($count_new, $count, "mismatch in count");
        }




        public function test_restore_invalid_vbucket() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $machine = vba_functions::get_machine_from_id_active("1");
                $failure = mb_restore_commands::restore_to_cluster($machine, 100);
                $this->assertContains("Unable to parse output", $failure,  "Failure message not found");
        }

        public function test_restore_invalid_disk_mapper() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                $failure = mb_restore_commands::restore_to_cluster($machine, 1, "10.10.10.10");
                $this->assertContains("Unable to fetch disk mapping", $failure,  "Failure message not found");
        }

        public function test_restore_invalid_backups() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                $backup_array = enhanced_coalescers::list_master_backups_multivb(1);
                $ss = diskmapper_functions::get_vbucket_ss("vb_1");
                sqlite_functions::corrupt_sqlite_file($ss, $backup_array[0]);
                $failure = mb_restore_commands::restore_to_cluster($machine, 1);
                $this->assertContains("is corrupt (file is encrypted or is not a database)", $failure,  "Failure message not found");

        }

        public function test_restore_killed_in_progress() {
                $this->assertTrue(cluster_setup::setup_zbase_cluster_with_ibr());
                global $test_machine_list;
                global $storage_server_pool;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100, 2));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                zbase_backup_setup::stop_cluster_backup_daemon();
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100, 2));
                zbase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                $pid = pcntl_fork();
                if($pid == -1) { die("could not fork");}
                else if ($pid) {
                    $restore_output = mb_restore_commands::restore_to_cluster($machine, 1);
                    $this->assertFalse(stristr($restore_output, "Restore completed successfully"), "Restore succeeded despite kill");
                    $restore_output = mb_restore_commands::restore_to_cluster($machine, 1);
                    $count_new = vba_functions::get_keycount_from_vbucket("1", "replica");
                    $this->assertEquals($count_new, $count, "mismatch in count");
                    $this->assertContains("Restore completed successfully", $restore_output, "Success message not found");

                }
                else {
                    sleep(3);
                    remote_function::remote_execution($machine, "sudo killall -9 python26");
                    exit();
                }
        }



}


class Basic_IBR_TestCase_Full  extends Basic_IBR_TestCase {

        public function keyProvider() {
                return Data_generation::provideKeys();
        }

        public function keyValueProvider() {
                return Data_generation::provideKeyValues();
        }

        public function keyValueFlagsProvider() {
                return Data_generation::provideKeyValueFlags();
        }
}

?>

