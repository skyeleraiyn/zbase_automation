<?php

abstract class Basic_TestCase extends ZStore_TestCase {

        public function test_setup()   {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
      }

	public function test_setup_pump() {
                $this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
                global $test_machine_list;
                foreach ($test_machine_list as $test_machine) {
                        flushctl_commands::set_flushctl_parameters($test_machine, "chk_max_items", 100);
                }
		$this->assertTrue(Data_generation::add_keys_to_cluster(25600, 100, 0, 102400));
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

