<?php

abstract class TestForLimits_TestCase extends ZStore_TestCase {		

	public function test_keySize_limit(){	
		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;
		
		// positive case 
		for($ikeysize = 248 ; $ikeysize < 251 ; $ikeysize++){		
			$testkey = Data_generation::generate_key($ikeysize - 2);	
			$testkey_1 = $testkey."_1";			
			$testkey_2 = $testkey."_2";				
			$testvalue1 = Data_generation::generate_data(1024);
			$testvalue2 = Data_generation::generate_data(1024);
	
				// without proxy
			$instance->delete($testkey_1);
			$instance->delete($testkey_2);
			$instance_without_proxy->set($testkey_1, $testvalue1);
			$instance_without_proxy->set($testkey_2, $testvalue2);

			$returnValue1 = $instance_without_proxy->get($testkey_1);
			$this->assertEquals($testvalue1, $returnValue1, "Memcache::get (value)");
			$this->assertNotEquals($testvalue2, $returnValue1, "Memcache::get (value)");
		}
	}	

	public function test_keySize_limit_cksum(){	
		
		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;
		
		// positive case 
		for($ikeysize = 248 ; $ikeysize < 251 ; $ikeysize++){	
			$testkey = Data_generation::generate_key($ikeysize - 2);	
			$testkey_1 = $testkey."_1";			
			$testkey_2 = $testkey."_2";				
			$testvalue1 = Data_generation::generate_data(1024);
			$testvalue2 = Data_generation::generate_data(1024);		
				// with chksum without proxy
			$instance->delete($testkey_1);
			$instance->delete($testkey_2);
			$instance_without_proxy_cksum->set($testkey_1, $testvalue1);
			$instance_without_proxy_cksum->set($testkey_2, $testvalue2);

			$returnValue1 = $instance_without_proxy_cksum->get($testkey_1);
			$this->assertEquals($testvalue1, $returnValue1, "Memcache::get (value)");
			$this->assertNotEquals($testvalue2, $returnValue1, "Memcache::get (value)");	
		}
	}		

	public function test_keySize_limit_negative(){	
		
		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;
		
		// negative case where key gets truncated
		for($ikeysize = 251 ; $ikeysize < 256 ; $ikeysize++){		
			$testkey = Data_generation::generate_key($ikeysize - 2);	
			$testkey_1 = $testkey."_1";			
			$testkey_2 = $testkey."_2";				
			$testvalue1 = Data_generation::generate_data(1024);
			$testvalue2 = Data_generation::generate_data(1024);

				// without proxy
			$instance->delete($testkey_1);
			$instance->delete($testkey_2);
			$instance_without_proxy->set($testkey_1, $testvalue1);
			$instance_without_proxy->set($testkey_2, $testvalue2);

			$returnValue1 = $instance_without_proxy->get($testkey_1);
			$this->assertEquals($testvalue2, $returnValue1, "Memcache::get (value)");
		}
	}

	/**
     * @dataProvider keyProvider
     */	
	public function test_valSize_limit($testkey){

		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;
		
				// positive case 
			// without checksum and proxy
		$testvalue_19MB = Data_generation::HugeValueProvider(19);	
		$instance_without_proxy->set($testkey, $testvalue_19MB);
		$returnValue = $instance_without_proxy->get($testkey);
		$this->assertEquals(strlen($testvalue_19MB), strlen($returnValue), "Memcache::get (value)");
	}

	/**
     * @dataProvider keyProvider
     */	
	public function test_valSize_limit_cksum($testkey){

		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;	
		
			// with chsum and without proxy
		$testvalue_19MB = Data_generation::HugeValueProvider(19);			
		$instance_without_proxy_cksum->set($testkey, $testvalue_19MB);
		$returnValue = $instance_without_proxy_cksum->get($testkey);
		$this->assertEquals(strlen($testvalue_19MB), strlen($returnValue), "Memcache::get (value)");	
	}
	
	/**
     * @dataProvider keyProvider
     */	
	public function test_valSize_limit_get_cksum($testkey){

		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;
		
		$testvalue_19MB = Data_generation::HugeValueProvider(19);		
		$instance_without_proxy->set($testkey, $testvalue_19MB);
		$returnValue = $instance_without_proxy_cksum->get($testkey);
		$this->assertEquals(strlen($testvalue_19MB), strlen($returnValue), "Memcache::get (value)");		
	}
	
	/**
     * @dataProvider keyProvider
     */	
	public function test_valSize_limit_cksum_get($testkey){

		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;
		
		$testvalue_19MB = Data_generation::HugeValueProvider(19);			
		$instance_without_proxy_cksum->set($testkey, $testvalue_19MB);
		$returnValue = $instance_without_proxy->get($testkey);
		$this->assertEquals(strlen($testvalue_19MB), strlen($returnValue), "Memcache::get (value)");		
	}
	
	/**
     * @dataProvider keyProvider
     */		
	public function test_valSize_limit_negative($testkey){

		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;

		$testvalue_21MB = Data_generation::HugeValueProvider(21);	
		
				// Negative case	
		$instance_without_proxy->set($testkey, $testvalue_21MB);
		$returnValue = $instance_without_proxy->get($testkey);
		$this->assertFalse($returnValue, "Memcache::get (value)");		
	}

	/**
     * @dataProvider keyProvider
     */		
	public function test_valSize_limit_negative_cksum($testkey){

		$instance_with_proxy = Connection::getConnectionWithProxy();
		$instance_without_proxy = Connection::getConnectionWithoutProxy();
		$instance_with_proxy_cksum = Connection::getConnectionWithProxy(True);
		$instance_without_proxy_cksum = Connection::getConnectionWithoutProxy(True);	
		$instance = $instance_without_proxy;

		$testvalue_21MB = Data_generation::HugeValueProvider(21);			
		$instance_without_proxy_cksum->set($testkey, $testvalue_21MB);
		$returnValue = $instance_without_proxy_cksum->get($testkey);
		$this->assertFalse($returnValue, "Memcache::get (value)");		
	}
		
}
class TestForLimits_TestCase_Quick extends TestForLimits_TestCase{

	public function keyProvider() {
		$testkey = Data_generation::generate_key(240);
		return array(array($testkey));
	}
	
}	
?>
