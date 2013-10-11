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
abstract class ByKeyCAS2_TestCase extends ZStore_TestCase{

			/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/	 
        public function test_Cas2_MultiByKey($atestKey, $atestValue, $testFlags) {

                $instance = Connection::getServerPool();
                $testValue1 = "testvalue1";
                $testValue2 = "testvalue2";
                $testValue3 = "testvalue3";
                $keys = array(
                        $atestKey[0] => array(
                                "value" => $atestValue[0],
                                "shardKey" => SHARDKEY1,
                                "flag" => $testFlags,
                                "cas" => 0,
                                "expire" => 1209600
                        ),
                        $atestKey[1] => array(
                                "value" => $atestValue[1],
                                "shardKey" => SHARDKEY2,
                                "flag" => $testFlags,
                                "cas" => 0,
                                "expire" => 1209600
                        ),
                        $atestKey[2] => array(
                                "value" => $atestValue[2],
                                "shardKey" => SHARDKEY3,
                                "flag" => $testFlags,
                                "cas" => 0,
                                "expire" => 1209600
                        )
                );

                $getkeys = array(
                $atestKey[0] => SHARDKEY1,
                $atestKey[1] => SHARDKEY2,
                $atestKey[2] => SHARDKEY3
                );
                $instance->setMultiByKey($keys);
                $return_array = $instance->getMultiBykey($getkeys);
                $keys[$atestKey[0]]["cas"] = $return_array[$atestKey[0]]["cas"];
                $keys[$atestKey[1]]["cas"] = $return_array[$atestKey[1]]["cas"];
                $keys[$atestKey[2]]["cas"] = $return_array[$atestKey[2]]["cas"];

                $keys[$atestKey[0]]["value"] = $atestValue[1];
                $keys[$atestKey[1]]["value"] = $atestValue[2];
                $keys[$atestKey[2]]["value"] = $atestValue[0];

                $instance->casMultiByKey($keys);
                $return_array = $instance->getMultiBykey($getkeys);
                $success = True;
                $printmessage =  "Memcache::casmulti (positive)";
                 if (($return_array[$atestKey[0]]["cas"] == $keys[$atestKey[0]]["cas"] ))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[0]]["cas"]." matches wit ". $keys[$atestKey[0]]["cas"];
                }
                if (($return_array[$atestKey[1]]["cas"] == $keys[$atestKey[1]]["cas"] ))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[1]]["cas"]." matches wit ". $keys[$atestKey[0]]["cas"];
                }
                if (($return_array[$atestKey[2]]["cas"] == $keys[$atestKey[2]]["cas"] ))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[0]]["cas"]." matches wit ". $keys[$atestKey[0]]["cas"];
                }

                $keys[$atestKey[0]]["cas"] = $return_array[$atestKey[0]]["cas"];
                $keys[$atestKey[1]]["cas"] = $return_array[$atestKey[1]]["cas"];
                $keys[$atestKey[2]]["cas"] = $return_array[$atestKey[2]]["cas"];

                $keys[$atestKey[0]]["value"] = $atestValue[2];
                $keys[$atestKey[1]]["value"] = $atestValue[0];
                $keys[$atestKey[2]]["value"] = $atestValue[1];
                $instance->casMultiByKey($keys);
                $return_array = $instance->getMultiBykey($getkeys);

               if (($return_array[$atestKey[0]]["cas"] == $keys[$atestKey[0]]["cas"] ))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[0]]["cas"]." matches wit ". $keys[$atestKey[0]]["cas"];
                }
                if (($return_array[$atestKey[1]]["cas"] == $keys[$atestKey[1]]["cas"] ))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[1]]["cas"]." matches wit ". $keys[$atestKey[0]]["cas"];
                }
                if (($return_array[$atestKey[2]]["cas"] == $keys[$atestKey[2]]["cas"] ))
                {
                        $success = False;
                        $printmessage = $printmessage.$return_array[$atestKey[0]]["cas"]." matches wit ". $keys[$atestKey[0]]["cas"];
                }

                $this->assertTrue($success, $printmessage);
        }


	/**
	* @dataProvider ArrayKeyArrayValueFlags
	*/
	public function test_Cas2ByKey($atestKey, $atestValue, $testFlags) {

		$testValue1 = "testvalue1";
		$testValue2 = "testvalue2";
		$testValue3 = "testvalue3";
	
		$instance = Connection::getServerPool();

		$instance->setByKey($atestKey[0], $atestValue[0], $testFlags, TIMEOUT, CASVALUE, SHARDKEY1);
		$instance->setByKey($atestKey[1], $atestValue[1], $testFlags, TIMEOUT, CASVALUE, SHARDKEY2);
		$instance->setByKey($atestKey[2], $atestValue[2], $testFlags, TIMEOUT, CASVALUE, SHARDKEY3);
		
		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1, $returnCas1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[1],  SHARDKEY2, $returnValue2, $returnFlags2, $returnCas2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue2, "Memcache::getbykey (value2)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[2],  SHARDKEY3, $returnValue3, $returnFlags3, $returnCas3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue3, "Memcache::getbykey (value3)");		
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
	
                $getsuccess1 = $instance->casByKey($atestKey[0], $atestValue[1], $testFlags, TIMEOUT, $returnCas1 , SHARDKEY1);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");
                $getsuccess2 = $instance->casByKey($atestKey[1], $atestValue[2], $testFlags, TIMEOUT, $returnCas2 , SHARDKEY2);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");
                $getsuccess3 = $instance->casByKey($atestKey[2], $atestValue[0], $testFlags, TIMEOUT, $returnCas3 , SHARDKEY3);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");

		//cas again with return Cas value
                $getsuccess1 = $instance->casByKey($atestKey[0], $atestValue[2], $testFlags, TIMEOUT, $returnCas1 , SHARDKEY1);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");
                $getsuccess2 = $instance->casByKey($atestKey[1], $atestValue[0], $testFlags, TIMEOUT, $returnCas2 , SHARDKEY2);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");
                $getsuccess3 = $instance->casByKey($atestKey[2], $atestValue[1], $testFlags, TIMEOUT, $returnCas3 , SHARDKEY3);
                $this->assertTrue($getsuccess1, "Memcache::casByKey (positive)");


		$getsuccess1 = $instance->getByKey($atestKey[0],  SHARDKEY1, $returnValue1, $returnFlags1, $returnCas1);
		$this->assertTrue($getsuccess1, "Memcache::get (positive)");
		$this->assertEquals($atestValue[2], $returnValue1, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags1, "Memcache::get (flag1)");
		$getsuccess2 = $instance->getByKey($atestKey[1],  SHARDKEY2, $returnValue2, $returnFlags2, $returnCas2);
		$this->assertTrue($getsuccess2, "Memcache::get (positive)");
		$this->assertEquals($atestValue[0], $returnValue2, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags2, "Memcache::get (flag1)");
		$getsuccess3 = $instance->getByKey($atestKey[2],  SHARDKEY3, $returnValue3, $returnFlags3, $returnCas3);
		$this->assertTrue($getsuccess3, "Memcache::get (positive)");
		$this->assertEquals($atestValue[1], $returnValue3, "Memcache::getbykey (value1)");
		$this->assertEquals($testFlags, $returnFlags3, "Memcache::get (flag1)");
	}
	
}

class CAS2_TestCase_Full extends ByKeyCAS2_TestCase
{
	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}
	
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	
	public function ArrayKeyArrayValueFlags() {
		return Data_generation::provideArrayKeyArrayValueFlags();
	}
}
