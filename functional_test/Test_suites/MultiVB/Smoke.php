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
