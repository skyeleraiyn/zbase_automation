<?php
//error_reporting ( E_WARNING );


abstract class Logger_TestCase extends ZStore_TestCase {

	/**
     * @dataProvider SimpleKeyValueProvider
    */
	public function test_invalid_rules($testKey, $testValue) {
	
			$instance = $this->sharedFixture;
			
			for($icount=0; $icount<22 ; $icount++){
				$instance->setLogName("rule$icount");
				$instance->add("$testKey$icount",$testValue);
				$instance->set("$testKey$icount",$testValue);
				$instance->add("$testKey$icount",$testValue);
				$instance->get("$testKey$icount");
				$instance->replace("$testKey$icount",$testValue);
				$instance->increment("$testKey$icount",1);
				$instance->set("$testKey$icount",1);
				$instance->increment("$testKey$icount",1);
				$instance->decrement("$testKey$icount",1);
				$instance->delete("$testKey$icount");
				$instance->replace("$testKey$icount",$testValue);
				$instance->set("$testKey$icount",1);
				$instance->getl("$testKey$icount");
				$instance->getl("$testKey$icount");
				$instance->unlock("$testKey$icount");
		}
	}

}

class Logger_TestCase_Quick extends Logger_TestCase{
	public function simpleKeyValueProvider() {
		return array(array("test_key", "test_value"));
	}
}
?>

