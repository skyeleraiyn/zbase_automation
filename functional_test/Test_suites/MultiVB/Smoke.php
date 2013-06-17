<?php

abstract class MultiVBSmoke_TestCase extends ZStore_TestCase {

        public function test_Initial_config() {
		echo "Boo Boo\n";
		$map = vbs_cmd::getVBMap(VBS_IP, "cluster1", 6060);
//		$this->assertTrue(vbs_cms::check_vbs_in_map($map, range(0, 31)), "Check all vb's in map");
		vbs_cmd::get_vbs_config_json();
	}
}

class MultiVBSmoke_TestCase_Full extends MultiVBSmoke_TestCase{

        public function keyProvider() {
                return Utility::provideKeys();
        }

}

?>
