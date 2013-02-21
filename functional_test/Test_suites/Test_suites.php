<?php

include_once "../common/common.php";
common::include_all_php_files("Constants/");
common::include_all_php_files("Include/");

class Test_suites{		
	function declare_test_suite($testname){
		switch ($testname){
			case "php_pecl_smoke_test":
				return array(
					"Pecl/Basic/Basic.php",
					"Pecl/Logger/Logger_basic.php",						
					"Pecl/Basic/Append_Prepend.php",
					"Pecl/Basic/CAS.php",
					"Pecl/Basic/CAS2.php",
					"Pecl/Checksum.php",
					"Pecl/Serialize/IGBinary_Serialize_Bzip.php",						 
					"Pecl/Getl/Getl_basic.php",
					"Pecl/Getl/Getl_Metadata.php",					
					"Mcmux/Mcmux.php",
					"Pecl/ByKey/ByKey__independent.php",
					"Membase/1.7/Data_Integrity/DI_basic.php");
			case "php_pecl_regression_test":
				return array_merge(self::declare_test_suite("php_pecl_smoke_test"), 
					array(
					"Pecl/Basic/Re_entrant.php",	
					"Pecl/Logger/Logger_invalid_rule.php",
					"Pecl/Leak.php",						 
					"Pecl/Serialize/Negative_IGBSerialize_BzipUncompress.php",	
					"Pecl/Logger/Logger_non_existant_keys.php",
					"Pecl/Serialize/Negative_Serialize_Uncompress.php",						
					"Pecl/Getl/Getl_append_prepend.php",
					"Pecl/Getl/Getl_evict.php",
					"Pecl/Getl/Getl_timeout.php",
					"Pecl/Getl/Getl_update.php",
					"Pecl/Getl/Getl_expiry.php",
					"Pecl/Getl/Getl_unlock.php",
					"Pecl/Getl/Getl_MultiByKey__independent.php",
					"Pecl/Logger/Logger_out_of_memory_server.php",
					"Pecl/ByKey/ByKeyEviction__independent.php",
					"Pecl/ByKey/ByKeyCAS2__independent.php",
					"Pecl/ByKey/ByKey_logger__independent.php",
				//	"Pecl/Basic/Negative_CAS2.php", // To be run manually and not part of CI
					"Pecl/ApacheLog.php",
					"Pecl/HugeMultiGet__independent.php",
					"Membase/TestKeyValueLimit.php",
					"Mcmux/TestKeyValueLimit_mcmux.php"));
			case "membase_smoke_test":
				return array(
					"Pecl/Basic/Basic.php",
					"Pecl/Basic/Append_Prepend.php",
					"Pecl/Basic/CAS.php",
					"Pecl/Basic/CAS2.php",
					"Membase/Eviction.php",
					"Pecl/Checksum.php",
					"Pecl/Serialize/IGBinary_Serialize_Bzip.php",						 					
					"Pecl/Getl/Getl_basic.php",
					"Pecl/Getl/Getl_Metadata.php",				
					"Membase/Replication__independent.php",
					"Membase/1.7/Min_Data_Age__independent.php",
					"Membase/1.7/Backup_Tests__independent.php",
					"Membase/1.7/Data_Integrity/DI_basic.php",
					"Membase/1.7/Data_Integrity/DI_IBR__independent.php",
					"Membase/1.7/LRU/LRU_basic.php",
					"Membase/1.7/Multi_KV_Store/Multi_KVStore_Functional.php"
					);
			case "membase_regression_test":
				return array_merge(self::declare_test_suite("membase_smoke_test"), 
					array(
					"Membase/Python_Scripts.php",	
					"Pecl/Serialize/Negative_IGBSerialize_BzipUncompress.php",	
					"Pecl/Serialize/Negative_Serialize_Uncompress.php",						
					"Pecl/Getl/Getl_append_prepend.php",					
					"Pecl/Getl/Getl_evict.php",
					"Pecl/Getl/Getl_timeout.php",
					"Pecl/Getl/Getl_update.php",
					"Pecl/Getl/Getl_expiry.php",
					"Pecl/Getl/Getl_unlock.php",
					"Membase/Persistance/Persistance_basic.php",
					"Membase/Stats/Stats_getl.php",
					"Membase/1.7/Core_Parameters_Test__independent.php",			
					"Membase/1.7/1.7_Replication__independent.php",
					"Membase/1.7/Tap__independent.php",
					"Membase/1.7/Data_Integrity/DI_IBR_negative__independent.php",
					"Membase/1.7/Multi_KV_Store/Multi_KVStore_Config_Parameter_Test.php",
					"Membase/1.7/Multi_KV_Store/Multi_KVStore_Functional_Master_Slave__independent.php"
					));	
			case "storage_server_test":
				return array(
					"Storage_Server/Backup_Daemon__independent.php",
					"Storage_Server/Restore__independent.php",
					"Storage_Server/Core_Merge__independent.php",					
					"Storage_Server/Daily_Merge__independent.php",
					"Storage_Server/Master_Merge__independent.php"
				);
			case "disk_mapper_test":
				return array(
					"Storage_Server/Disk_mapper/disk_mapper__independent.php",
					"Storage_Server/Disk_mapper/Storage_Server_Component__independent.php",
					"Storage_Server/Disk_mapper/Torrent__independent.php"
				);
			case "disk_mapper_smoke_test":
				return array(
					"Storage_Server/Disk_mapper/Disk_mapper_api__independent.php"
				);
			default:
				echo "Error: undeclared testname \n";
				exit;
		}			
	}
					
		
						
// Following test suites have issue or need to be reviewed
//		"Logger/Logger_sig_stop_server.php"
//		"Logger/Logger_non_existant_server.php"
// 		"ByKey/Large_MultiKey__independent.php",
//		"HugeMultiGet.php"						



}
?>