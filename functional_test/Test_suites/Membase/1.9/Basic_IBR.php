<?php

abstract class Basic_IBR_TestCase extends ZStore_TestCase {

    public function test_setup()   {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
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
        $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
        global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
		$this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		membase_backup_setup::start_cluster_backup_daemon();
		sleep(120);
		$count = enhanced_coalescers::get_total_backup_key_count();
		$this->assertEquals(25600, $count, "count mismatch");

	}


	public function test_incremental_backups() {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
        global $test_machine_list;
        foreach ($test_machine_list as $test_machine) {
              flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        membase_backup_setup::start_cluster_backup_daemon();
		sleep(350);
        membase_backup_setup::stop_cluster_backup_daemon();
        $count = enhanced_coalescers::get_total_backup_key_count();
        $this->assertEquals(25600, $count, "count mismatch");
        $this->assertTrue(Data_generation::pump_keys_to_cluster(51200, 100, 3));
        membase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
        membase_backup_setup::stop_cluster_backup_daemon();
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
	    $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
        foreach ($test_machine_list as $test_machine) {
                flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        $cluster = new Memcache;
		$cluster->addserver($moxi_machines[0],MOXI_PORT_NO);
        $cluster->set("test_verify_key", "verify_value_123");

        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        membase_backup_setup::start_cluster_backup_daemon();
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


	public function test_backups_with_no_storage_allocated() {
        global $test_machine_list;
        global $storage_server_pool;
        diskmapper_setup::reset_diskmapper_storage_servers($storage_server_pool, True);
        cluster_setup::setup_membase_cluster();
        sleep(30);
        foreach ($test_machine_list as $test_machine) {
            flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        membase_backup_setup::stop_cluster_backup_daemon();
        remote_function::remote_execution($storage_server_pool[0], "echo > /var/log/vbucketbackupd.log");
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        membase_backup_setup::start_cluster_backup_daemon($storage_server_pool[0]);
        sleep(30);
        $log_content = remote_function::remote_execution($storage_server_pool[0], "cat /var/log/vbucketbackupd.log");
        $this->assertContains("Fatal: Could not get mapping from disk mapper", $log_content, "error message not found");
        $this->assertContains("Failed to init backup daemon. Exiting...", $log_content, "second error message not found");
	}


	public function test_backups_when_VBA_down() {
                global $test_machine_list;
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                foreach ($test_machine_list as $test_machine) {
                    flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $ss = diskmapper_functions::get_vbucket_ss("vb_10");
                $ss_path = diskmapper_functions::get_vbucket_path("vb_10");
                $vba = vba_functions::get_machine_from_id_replica(10);
                echo "\n ss:".$ss." vba:".$vba;
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                $pid = pcntl_fork();
                if($pid == -1) { die("could not fork");}
                else if ($pid) {
                    remote_function::remote_execution($ss, "sudo su -c 'echo > /var/log/vbucketbackupd.log'");
                    membase_backup_setup::start_cluster_backup_daemon($ss);
                    sleep(300);
                    $log_content = remote_function::remote_execution($ss, "cat /var/log/vbucketbackupd.log | grep -C2 \"$ss_path\"");
                    $this->assertContains("Errno connection from ('".$vba, $log_content, "VBA down not detected");
                    $this->assertContais("Info: client closed connection",$log_content, "connection close not detected");

                }
                else {
                    sleep(10);
                    remote_function::remote_execution($vba, "sudo /etc/init.d/vba stop");
                    exit();
                }
	}


	public function est_backups_killed_in_progress() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
       	        $pid = pcntl_fork();
                if($pid == -1) { die("could not fork");}
                else if ($pid) {
                //Start Daemon
                //Verify failure
                }
                else {
                       //Kill Backup process
                        exit();
                }
        }


	public function est_backups_after_downshard() {
		global $test_machine_list;
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True, True));
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
//Start Daemon
//Stop Daemon
		$this->assertTrue(vbs_functions::remove_server_from_cluster($test_machine_list[0]), "Couldn't downshard");

	}


	public function est_backups_after_upshard() {
		global $spare_machine_list;
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True, True));
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
//Start Daemon
//Stop Daemon
		$this->assertTrue(vbs_functions::add_server_to_cluster($spare_machine_list[0]), "Couldn't upshard");

	}


	public function est_backup_after_storage_disk_failover() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
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
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
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
        $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
        global $test_machine_list;
        global $storage_server_pool;
        foreach ($test_machine_list as $test_machine) {
                flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
        }
        foreach ($storage_server_pool as $ss) {
                remote_function::remote_execution($ss, "echo > /var/log/vbucketbackupd.log");
        }
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        membase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
        membase_backup_setup::stop_cluster_backup_daemon();
        $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
        membase_backup_setup::start_cluster_backup_daemon();
        sleep(120);
		$count = vba_functions::get_keycount_from_vbucket("1", "replica");
		$machine = vba_functions::get_machine_from_id_active("1");
		$restore_output = mb_restore_commands::restore_to_cluster($machine, 1);
		$count_new = vba_functions::get_keycount_from_vbucket("1", "replica");
		$this->assertEquals($count_new, $count, "mismatch in count");
        $this->assertContains("Restore completed successfully", $restore_output, "Success message not found");
	}

	public function est_restore_after_downshard() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
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
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True, True));
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
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
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
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                membase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                mb_restore_commands::restore_to_cluster($machine, 1);
                $count_new = vba_functions::get_keycount_from_vbucket("1", "replica");
                $this->assertEquals($count_new, $count, "mismatch in count");
        }




        public function test_restore_invalid_vbucket() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                membase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $machine = vba_functions::get_machine_from_id_active("1");
                $failure = mb_restore_commands::restore_to_cluster($machine, 100);
                $this->assertContains("Unable to parse output", $failure,  "Failure message not found");
        }

        public function test_restore_invalid_disk_mapper() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                membase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                $failure = mb_restore_commands::restore_to_cluster($machine, 1, "10.10.10.10");
                $this->assertContains("Unable to fetch disk mapping", $failure,  "Failure message not found");
        }

        public function test_restore_invalid_backups() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                membase_backup_setup::start_cluster_backup_daemon();
                sleep(120);
                $count = vba_functions::get_keycount_from_vbucket("1", "replica");
                $machine = vba_functions::get_machine_from_id_active("1");
                $backup_array = enhanced_coalescers::list_master_backups_multivb(1);
                $ss = diskmapper_functions::get_vbucket_ss("vb_1");
                sqlite_functions::corrupt_sqlite_file($ss, $backup_array[0]);
                $failure = mb_restore_commands::restore_to_cluster($machine, 1);
                $this->assertContains("is corrupt (file is encrypted or is not a database)", $failure,  "Failure message not found");

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

