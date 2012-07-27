<?php

require_once 'config.php';
require_once 'PHPUnit/Framework.php';
ini_set("memcache.compression_level", 0);
ini_set("memory_limit", "128M");

abstract class ZStore_TestCase extends PHPUnit_Framework_TestCase {
	
	static $_passes = array();
	
	public function setUp() {
		global $argv, $selected_testcases;
		
			// option to run testcase selectively
		if(count($argv) > 4){
			$current_testcase = explode(" ", PHPUnit_Framework_TestCase::getName());
			$current_testcase = trim(str_replace("Value", "", $current_testcase[0]));
			
			if(array_search($current_testcase, $selected_testcases) === False){
				$this->markTestSkipped();
			}			
		}
		
		if (isset(self::$_passes[$this->name])) {
			$this->markTestSkipped();
		}
		// Add suite names which would need membase to be restarted for each testcase execution
		// Cannot be used for suites which work with more than one machine
		$suite_list_restart_membase_servers = array(
			"Stats_basic");
		foreach($suite_list_restart_membase_servers as $suite){	
			if(stristr($argv[2], $suite)){
				membase_function::reset_membase_servers(array(TEST_HOST_1));
				break;
			}
		}
		
		// Need to investiage this property
		$this->sharedFixture->setproperty("NullOnKeyMiss", false);
		
		
		// delete it before we start
		if (isset($this->data[0])) {
			if (!(is_array($this->data[0]))){
				// delete it before we start 
				$this->sharedFixture->set($this->data[0], "dummy");  	// The extra Set is required to release the lock set by getl.
				$this->sharedFixture->delete($this->data[0]);
			}
		}
	}
	
	public function tearDown() {
		if ($this->status != PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
			self::$_passes[$this->name] = false;
		}
	}
}

class ZStoreTest extends PHPUnit_Framework_TestSuite {
	
	public static function suite() {
		global $argv, $selected_testcases;

		if(count($argv) < 4){
			log_function::exit_log_message("Need atleast 1 machines to execute the test \n".
			"Usage: php phpunit membase.php <testsuite name> <test_machine1:test_machine2:...> <testcase name> optional");	
		}
		
		if(count($argv) > 4){
			$selected_testcases = explode(":", $argv[4]);
		}
		
		foreach(explode(":", $argv[3]) as $key => $testmachine){
			$key = $key + 1;
			define('TEST_HOST_'.$key, $testmachine);
		}

			// Need to define machines (TEST_HOST_1, TEST_HOST_2, TEST_HOST_3 ) before including PECL_Constants and Test_Constants
		include_once "Constants/PECL_Constants.php";
		include_once "Constants/Test_Constants.php";
		$suite_name = $argv[2];
		if(strstr($suite_name, "Logger")){
			$pecl_logging_filename = str_replace(".php", ".log", basename($suite_name));
			define('PECL_LOGGING_FILE_PATH', "/tmp/pecl_memcache_".$pecl_logging_filename);
		}

		$suite = new ZStoreTest;
		$suite->addTestFile($suite_name);
		return $suite;
	}
	
	protected function setUp() {
		$this->sharedFixture = Connection::getMaster();
	}
	
	protected function tearDown() {
		global $argv;
		
		// Add suite names which would need membase to be restarted in the end
		// Cannot be used for suites which work with more than one machine
		$suite_list_restart_membase_servers = array(
			"Stats_basic");
		foreach($suite_list_restart_membase_servers as $suite){	
			if(stristr($argv[2], $suite)){
				membase_function::reset_membase_servers(array(TEST_HOST_1));
				break;
			}
		}
			
		$this->sharedFixture = NULL;
	}
}
