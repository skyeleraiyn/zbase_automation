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
abstract class Negative_CAS2_TestCase extends ZStore_TestCase{
	
		// This would need either old mcmux or Zbase which doesn't support CAS2 feature
		// Currently this has to be run manully by installing a wrong configuration. This cannot be part of Continous Intergration
		
		// Testcase for  SEG-9692 CAS2 variable is set to 0 when either mcmux or zbase doesn't support CAS2 and CAS2 operation succeeds 
	public function test_return_CAS_not_supported(){

	$instance = $this->sharedFixture;

		$instance->set("testkey", "testvalue");	
		
		// Verify a variable assinged from $returnCAS has not changed when CAS operation has failed
		$returnCAS = 123;		
		$success = $instance->cas("testkey", "testvalue1", 0, 0, $returnCAS);
		$this->assertFalse($success, "Memcache::cas (negative)");
		$this->assertNotEquals(0, $returnCAS, "CAS variable is set to 0 with negative CAS");
		
		// Verify a variable assinged from $returnCAS has not changed when CAS operation is successful
		$returnFlags = NULL ;
		$returnCAS = NULL;
		
		$instance->get("testkey", $returnFlags, $returnCAS);
		$success = $instance->cas("testkey", "testvalue1", 0, 0, $returnCAS);
		$this->assertTrue($success, "Memcache::cas (positive)");
		$this->assertNotEquals(0, $returnCAS, "CAS variable is set to 0 with positive CAS");
			
	}

}

class Negative_CAS2_TestCase_Full extends Negative_CAS2_TestCase{

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}
}
