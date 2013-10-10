<?php
 
require_once 'config.php';
require_once 'PHPUnit/Framework.php';
ini_set("memcache.compression_level", 0);
ini_set("memory_limit", "128M");

abstract class ZStore_TestCase extends PHPUnit_Framework_TestCase {
	
	static $_passes = array();
	
	public function setUp() {
		global $argv, $filter_testcases, $start_time;
		$start_time = time();
		$current_testcase = explode(" ", PHPUnit_Framework_TestCase::getName());
			// option to run testcase selectively
		if(count($argv) > 4){	
			if(array_search($current_testcase[0], $filter_testcases) === False){
				$this->markTestSkipped();
			}			
		}
		if (isset(self::$_passes[$this->name])) {
			$this->markTestSkipped();
		}
		log_function::debug_log($current_testcase[0]);
		
			// Setup test bed for each testcase
		$suite_list_restart_zbase_servers = array();
		foreach($suite_list_restart_zbase_servers as $suite){	
			if(stristr($argv[2], $suite)){
				zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
				break;
			}
		}
		
		
		// Need to investiage this property
		$this->sharedFixture->setproperty("NullOnKeyMiss", false);
		
		
		// delete the key before we start the test
		if (isset($this->data[0])) {
			if (is_array($this->data[0])){
				foreach($this->data[0] as $key){
					$this->sharedFixture->delete($key);
				}
			} else {
				if(stristr($argv[2], "getl") || stristr($argv[2], "serialize")){
					$this->sharedFixture->set($this->data[0], "dummy");  	// The extra Set is required to release the lock set by getl.
				}
				$this->sharedFixture->delete($this->data[0]);
			}
		}
	}
	
	public function tearDown() {
		global $modified_file_list, $start_time, $filter_testcases, $argv;
		
			// log time for each testcase. Skips if a test
		$end_time = time() - $start_time;
		$current_testcase = explode(" ", PHPUnit_Framework_TestCase::getName());
		if(count($argv) > 4){	
			if(array_search($current_testcase[0], $filter_testcases) !== False){
				log_function::debug_log($current_testcase[0]." took ".$end_time."secs");
			}			
		} else {
			log_function::debug_log($current_testcase[0]." took ".$end_time."secs");		
		}
		

				// Any file modified in TEST_HOST_2 will be reverted back for the next testcase
		while(count($modified_file_list)){
			$file_name = array_pop($modified_file_list);
			$file_name = explode("::", $file_name);
			remote_function::remote_execution($file_name[0], "sudo cp ".$file_name[1].".org ".$file_name[1]);
		}
		if ($this->status != PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
			self::$_passes[$this->name] = false;
		}
	}
}

class ZStoreTest extends PHPUnit_Framework_TestSuite {
	
	public static function suite() {
		global $argv, $filter_testcases, $modified_file_list;
		global $storage_server_pool;
		
		$modified_file_list = array();
			
		if(count($argv) < 4){
			log_function::exit_log_message("Need atleast 1 machines to execute the test \n".
			"Usage: php phpunit zbase.php <testsuite name> <test_machine1:test_machine2:...> <testcase name> optional");	
		}
		
		if(count($argv) > 4){
			$filter_testcases = explode(":", $argv[4]);
		}
		
		foreach(explode(":", $argv[3]) as $key => $testmachine){
			$key = $key + 1;
			define('TEST_HOST_'.$key, $testmachine);
		}
		if(!defined(TEST_PASSWORD)){
			echo "\nPassword for".TEST_USERNAME.": ";
			system('stty -echo');
			define('TEST_PASSWORD', trim(fgets(STDIN)));
			system('stty -echo');
			echo TEST_PASSWORD;
		}
		
		if(is_array($storage_server_pool) && count($storage_server_pool) > 0){
			foreach($storage_server_pool as $key => $testmachine){
				$key = $key + 1;
				define('STORAGE_SERVER_'.$key, $testmachine);
			}
		}
	
		$suite_name = $argv[2];
		if(stristr($suite_name, "logger")){
			$pecl_logging_filename = str_replace(".php", ".log", basename($suite_name));
			define('PECL_LOGGING_FILE_PATH', "/tmp/pecl_memcache_".$pecl_logging_filename);
		}

		// define constants from temp_config
		$file_contents = log_function::read_from_temp_config();
		foreach($file_contents as $value){
			$value = explode("=", $value);
			define(trim($value[0]), trim($value[1]));

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
		
		// Add suite names which would need zbase to be restarted in the end
		// Cannot be used for suites which work with more than one machine
		$suite_list_restart_zbase_servers = array();
		foreach($suite_list_restart_zbase_servers as $suite){	
			if(stristr($argv[2], $suite)){
				zbase_setup::reset_zbase_servers(array(TEST_HOST_1));
				break;
			}
		}
			
		$this->sharedFixture = NULL;
	}
}
