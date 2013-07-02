<?php

abstract class Basic_TestCase extends ZStore_TestCase {

        public function test_setup()   {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
	        global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
	}

	public function test_setup_pump() {
		#cluster_setup::setup_membase_cluster();
		#sleep(30);
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
		$this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		Start daemon API
	#	global $storage_server_pool;
	#	foreach ($storage_server_pool as $ss) {
	#		remote_function::remote_execution($ss, "sudo /etc/init.d/backup_backupd start");
	#	}
		$count = enhanced_coalescers::get_total_backup_key_count();
		$this->assertEquals(25600, $count, "count mismatch");

	}

	public function test_integrity() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		$not_found = True;
		$value_backup = NULL;
		$backups = array();
		for($i=0;$i<NO_OF_VBUCKETS;$i++) {
			$backup = enhanced_coalescers::list_master_backups_multivb();
			$machine = diskmapper_functions::get_vbucket_ss("vb_".$vb_id);
			$key = sqlite_functions::sqlite_select($machine, "key", "cpoint_op", $backup);
			if(stristr($key, "testkey_100")) {
				$not_found = False;
				$value_backup = sqlite_functions::sqlite_select($machine, "key", "cpoint_op", $backup, "where key like 'testkey_100'");
			}
		}

		$cluster = new Memcache;
		$cluster->addserver($moxi_machines[0],MOXI_PORT_NO);
		$value = $cluster->get("testkey_100");
		$this->assertFalse($not_found, "Key not found");
		$this->assertEquals($value, $value_backup, "value found to be not equal");
 

	}

	public function test_restore_basic() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		Start Daemon;
		Stop Daemon;
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
		Start Daemon;	
		$count = vba_functions::get_keycount_from_vbucket("vb_1", "replica");
		$machine = vba_functions::get_machine_from_id_active("vb_1");
		mb_restore_commands::restore_to_cluster($machine, 1);
		$count_new = vba_functions::get_keycount_from_vbucket("vb_1", "replica");
		$this->assertEquals($count_new, $count, "mismatch in count");
	}

        public function test_restore_basic_only_master() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                Start Daemon;
                $count = vba_functions::get_keycount_from_vbucket("vb_1", "replica");
                $machine = vba_functions::get_machine_from_id_active("vb_1");
                mb_restore_commands::restore_to_cluster($machine, 1);
                $count_new = vba_functions::get_keycount_from_vbucket("vb_1", "replica");
                $this->assertEquals($count_new, $count, "mismatch in count");
        }

        public function test_restore_invalid_vbucket() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                Start Daemon;
                $machine = vba_functions::get_machine_from_id_active("vb_1");
                $failure = mb_restore_commands::restore_to_cluster($machine, 100);
                $this->assertContains($failure, "Failed",  "Failure message not found");
        }

        public function est_restore_invalid_disk_mapper() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr(True, True));
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
                $this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
                Start Daemon;
                $count = vba_functions::get_keycount_from_vbucket("vb_1", "replica");
                $machine = vba_functions::get_machine_from_id_active("vb_1");
                mb_restore_commands::restore_to_cluster($machine, 1, 10.10.10.10);
                $count_new = vba_functions::get_keycount_from_vbucket("vb_1", "replica");
                $this->assertEquals($count_new, $count, "mismatch in count");
        }




}


class Basic_TestCase_Full  extends Basic_TestCase {

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

