<?php

abstract class Basic_TestCase extends ZStore_TestCase {

        public function test_setup()   {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
       global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }}

	public function test_setup_pump() {
		cluster_setup::setup_membase_cluster();
		sleep(30);
                #$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
		$this->assertTrue(Data_generation::pump_keys_to_cluster(25600, 100));
	}

	public function test_list() {

		for($i=0;$i<NO_OF_VBUCKETS;$i++) {
			$daily = enhanced_coalescers::list_master_backups_multivb($i,"2013-06-26");
			var_dump($daily);
		}
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

