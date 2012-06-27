<?php

require_once 'config.php';
require_once 'PHPUnit/Framework.php';
ini_set("memcache.compression_level", 0);
ini_set("memory_limit", "128M");

abstract class ZStore_TestCase extends PHPUnit_Framework_TestCase {
	
	static $_passes = array();
	
	public function setUp() {
		if (isset(self::$_passes[$this->name])) {
			$this->markTestSkipped();
		}
		
		$this->sharedFixture->setproperty("NullOnKeyMiss", false);
		$this->sharedFixture->flush();
		// delete it before we start
/*		if (isset($this->data[0])) {
			if (!(is_array($this->data[0]))){
				// delete it before we start 
				$this->sharedFixture->set($this->data[0], "dummy");  	// The extra Set is required to release the lock set by getl.
				$this->sharedFixture->delete($this->data[0]);
			}
		}
*/	}
	
	public function tearDown() {
		if ($this->status != PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
			self::$_passes[$this->name] = false;
		}
	}
}

class ZStoreTest extends PHPUnit_Framework_TestSuite {
	
	public static function suite() {
		global $argv, $argc;
		
		if($argc < 3){
			exit_log_message("Need atleast 1 machines to execute phpunit test");	
		}
		for($mc_count = 3 ; $mc_count < $argc ; $mc_count++){
			$count = $mc_count - 2;	// For getting the machine name through mc_count and assigning it to TEST_HOST_n
			define('TEST_HOST_'.$count, $argv[$mc_count]);
		}
			// Need to define machines (TEST_HOST_1, TEST_HOST_2, TEST_HOST_3 ) before including PECL_Constants and Test_Constants
		include_once "Constants/PECL_Constants.php";
		include_once "Constants/Test_Constants.php";
		$suite_name = $argv[2];
		if(strstr($suite_name, "Logger")){
			$pecl_logging_filename = str_replace(".php", ".log", basename($suite_name));
			define('PECL_LOGGING_FILE_PATH', "/tmp/pecl_memcache_".$pecl_logging_filename);
		}
		Data_generation::prepareData("igbinary");
		$suite = new ZStoreTest;
		$suite->addTestFile($suite_name);
		return $suite;
	}
	
	protected function setUp() {
		$this->sharedFixture = Connection::getMaster();
	}
	
	protected function tearDown() {
		$this->sharedFixture = NULL;
	}
}
