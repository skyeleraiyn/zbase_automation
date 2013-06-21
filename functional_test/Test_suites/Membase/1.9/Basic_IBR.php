<?php

abstract class Basic_TestCase extends ZStore_TestCase {

        public function test_setup()   {
		$this->assertTrue(cluster_setup::setup_membase_cluster_with_ibr());
		
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

