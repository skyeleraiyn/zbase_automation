<?php

class Connection{
	private static $_testHost;
	private static $instance_with_proxy_server;
	
	public static function getHost() {
		if (!self::$_testHost) {
			self::$_testHost = TEST_HOST_1;
		}
		return self::$_testHost;
	}
		
	public static function getDummyConnection() {
		$instance_dummy_connection = new Memcache;
		$instance_dummy_connection->addServer(SERVER_NO_RESP_DUMMY_HOSTNAME, MEMBASE_PORT_NO);	
		$instance_dummy_connection->setLogName("Logger_non_existant_server");
		return $instance_dummy_connection;
	}

	public static function getOutofMemoryServerConnection(){
		// start membase server with just 96MB memory to simulate server error out of memory
		
		$instance = new Memcache;
		$instance->addServer(TEST_HOST_1, MEMBASE_PORT_NO);	
		$instance->setLogName("Logger_out_of_memory_server");
		return $instance;
	}

	public static function getSigStopServerConnection(){
		// start membase server and issue sig stop to the process
		
		$instance = new Memcache;
		$instance->addServer(TEST_HOST_1, MEMBASE_PORT_NO);	
		$instance->setLogName("Logger_sig_stop_server");
		return $instance;
	}
	
	private static function check_proxy_server_protocol($host_name) {
		// Currently this supports only ASCII protocol. Binary protocol will be supported later
		
		$instance_with_proxy_server = new Memcache;
		if(is_array($host_name)){
			for($i=0;$i<count($host_name);$i++) {
				for($iconnattempt=0 ; $iconnattempt < 10 ; $iconnattempt++){
					if($instance_with_proxy_server->addServer($host_name[$i], MEMBASE_PORT_NO)){
						break;
					}
					sleep(1);
				}
			}
		} else {
			for($iconnattempt=0 ; $iconnattempt < 10 ; $iconnattempt++){
				if($instance_with_proxy_server->addServer($host_name, MEMBASE_PORT_NO)){
					break;
				}
				sleep(1);
			}	
		}	
		
			// To support DI. Checksum will be enabled only if all components support it
		if(!defined('ENABLE_CHECKSUM')){
			if(	SUPPORT_CHECKSUM && 
				installation::verify_php_pecl_DI_capable() && 
				installation::verify_mcmux_DI_capable() &&
				installation::verify_membase_DI_capable(TEST_HOST_1)){
				define('ENABLE_CHECKSUM', True);
			} else {
				define('ENABLE_CHECKSUM', False);
			}
		}
		
		$instance_with_proxy_server->setproperty("EnableChecksum", ENABLE_CHECKSUM);	
		return $instance_with_proxy_server;
		
				/* Removing this as 1.6.0 mcmux is not supported version
		if (MCMUX_VERSION == "1.6.0") {
			// mcmux 1.6.0			
			if(is_array($host_name)){
				for($i=0;$i<count($host_name);$i++) {
					$instance_with_mcmux->addServer($host_name[$i], MEMBASE_PORT_NO, true, 1, 10, 1, 0, NULL, 100, TRUE); // TRUE for Binary protocol, FALSE for Ascii	
				}
			} else {
				$instance_with_mcmux->addServer($host_name, MEMBASE_PORT_NO , true, 1, 10, 1, 0, NULL, 100, TRUE); // TRUE for Binary protocol, FALSE for Ascii	
			}	
		} 
		*/
	}

	public function getConnection($remote_machine){
		return self::check_proxy_server_protocol($remote_machine);
	}
	
	public static function getMaster() {
		return self::check_proxy_server_protocol(TEST_HOST_1);
	}
	
	public static function getSlave() {
		return self::check_proxy_server_protocol(TEST_HOST_2);
	} 

	public static function getSlave2() {
		return self::check_proxy_server_protocol(TEST_HOST_3);
	} 

	public static function getServerPool() {
		return self::check_proxy_server_protocol(array(TEST_HOST_1, TEST_HOST_2, TEST_HOST_3));
	}

	public static function getSocketConn($a = TEST_HOST_1, $b = MEMBASE_PORT_NO){
		return  new PeclSocket($a, $b);
	}

		// To support TestKeyValueLimit test suite
	public static function getConnectionWithoutProxy($checksum = False){
		ini_set('memcache.proxy_enabled', 0);
		$instance = new Memcache;
		$instance->addServer(TEST_HOST_1, MEMBASE_PORT_NO);	
		$instance->setproperty("EnableChecksum", $checksum);
		return $instance;
	}
		// To support TestKeyValueLimit test suite
	public static function getConnectionWithProxy($checksum = False){
		ini_set('memcache.proxy_enabled', 1);
		$instance = new Memcache;
		$instance->addServer(TEST_HOST_1, MEMBASE_PORT_NO);	
		$instance->setproperty("EnableChecksum", $checksum);
		return $instance;
	}

}
?>