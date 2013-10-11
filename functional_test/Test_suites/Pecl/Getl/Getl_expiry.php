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

abstract class Getl_TestCase extends ZStore_TestCase {
 
			//***** getl and expiry *****//
        /**
     * @dataProvider simpleKeyValueFlagProvider
     */

        public function test_Getl_Expire_Set($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();

                $testTTL = 3;

                //  set expiry and getl
                $instance->set($testKey, $testValue, $testFlags, $testTTL);
                $returnValue = $instance2->getl($testKey, $testTTL + 1, $returnFlags);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
                $this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");

                sleep($testTTL + 1);
                // validate set value
                $returnFlags = null;
                $returnValue = $instance->set($testKey, $returnFlags);
                $this->assertTrue($returnValue, "Memcache::set (negative)");
	}

        /**
     * @dataProvider simpleKeyValueFlagProvider
     */

        public function test_Getl_Expire_Getl($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();

                $testTTL = 3;
                //  set expiry and getl
                $instance->set($testKey, $testValue, $testFlags, $testTTL);
                $returnValue = $instance2->getl($testKey, $testTTL, $returnFlags);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue, $returnValue, "Memcache::get (value)");
                $this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");


                sleep($testTTL + 1);
                // validate set value
                $returnFlags = null;
                $returnValue = $instance->getl($testKey, $testTTL, $returnFlags);		
                $this->assertNull($returnValue, "Memcache::getl (negative)");
        }

        /**
     * @dataProvider simpleKeyValueFlagProvider
     */


        public function test_Getl_Expire_Append($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();
                $testValue1 = $testValue;
                $testValue2 = strrev($testValue1);


                $testTTL = 3;

                //  set expiry and getl
                $instance->set($testKey, $testValue1, $testFlags, $testTTL);
                $returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
                $this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");


                sleep($testTTL + 1);
                // validate set value
                $returnFlags = null;
                $returnValue = $instance->append($testKey, $testValue2);
                $this->assertFalse($returnValue, "Memcache::append (negative)");
        }

        /**
     * @dataProvider simpleKeyValueFlagProvider
     */

        public function test_Getl_Expire_Prepend($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();
                $testValue1 = $testValue;
                $testValue2 = strrev($testValue1);


                $testTTL = 3;

                //  set expiry and getl
                $instance->set($testKey, $testValue1, $testFlags, $testTTL);
                $returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
                $this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");


                sleep($testTTL + 1);
                // validate set value
                $returnFlags = null;
                $returnValue = $instance->prepend($testKey, $testValue2);
                $this->assertFalse($returnValue, "Memcache::prepend (negative)");
        }

        /**
     * @dataProvider simpleKeyValueFlagProvider
     */

        public function test_Getl_Expire_increment($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();
                $testValue1 = strlen($testValue);


                $testTTL = 3;

                //  set expiry and getl
                $instance->set($testKey, $testValue1, $testFlags, $testTTL);
                $returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
                $this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");


                sleep($testTTL + 1);
                // validate set value
                $returnFlags = null;
                $returnValue = $instance->increment($testKey, $testValue1);
                $this->assertFalse($returnValue, "Memcache::increment (negative)");
        }

        /**
     * @dataProvider simpleKeyValueFlagProvider
     */

        public function test_Getl_Expire_decrement($testKey, $testValue, $testFlags) {

                $instance = $this->sharedFixture;
                $instance2 = Connection::getMaster();
                $testValue1 = strlen($testValue);


                $testTTL = 3;

                //  set expiry and getl
                $instance->set($testKey, $testValue1, $testFlags, $testTTL);
                $returnValue = $instance2->getl($testKey, GETL_TIMEOUT, $returnFlags);
                $this->assertNotEquals($returnValue, false, "Memcache::get (positive)");
                $this->assertEquals($testValue1, $returnValue, "Memcache::get (value)");
                $this->assertEquals($testFlags, $returnFlags, "Memcache::get (flag)");


                sleep($testTTL + 1);
                // validate set value
                $returnFlags = null;
                $returnValue = $instance->decrement($testKey, $testValue1);
                $this->assertFalse($returnValue, "Memcache::decrement (negative)");
        }
	
}


class Getl_TestCase_Full extends Getl_TestCase
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
	public function simpleKeyValueFlagProvider() {
		return array(array(uniqid('key_'), uniqid('value_'), 0));
	}	
	public function flagsProvider() {
		return Data_generation::provideFlags();	
	}
}

?>
