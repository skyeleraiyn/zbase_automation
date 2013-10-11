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

//Format --- set($key, $value, $flag=0, $expire =0, $algo , $crc=0, $crc2=0)

abstract class MBSocket_TestCase extends ZStore_TestCase {

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_ZeroOuter($testKey, $testValue, $testFlags) {

		$instance = Connection::getSocketConn();
		$testValue1= serialize($testValue);

		$returnValue = $instance->set($testKey, $testValue1, $testFlags, 0, 2, 0, crc32($testFlags.$testValue1));
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Checksum Zero");

		$returnValue = $instance->get($testKey);
		$this->assertFalse($returnValue, "get::checksum zero");
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_ZeroInner($testKey, $testValue, $testFlags) {

		$instance = Connection::getSocketConn();
		$testValue1= serialize($testValue);

		$returnValue = $instance->set($testKey, $testValue1, $testFlags, 0, 2, crc32($testFlags.$testValue1), 0);
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Checksum Zero");

		$returnValue = $instance->get($testKey);
		$this->assertFalse($returnValue, "get::checksum zero");

	}



	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_ZeroBoth($testKey, $testValue, $testFlags) {

		$instance = Connection::getSocketConn();
		$testValue1= serialize($testValue);

		$returnValue = $instance->set($testKey, $testValue1, $testFlags, 0, 2, 0, 0);
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Checksum Zero");

		$returnValue = $instance->get($testKey);
		$this->assertFalse($returnValue, "get::checksum zero");

	}


	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_AlgoOne($key, $value, $flags) {

		$instance = Connection::getSocketConn();

		$returnValue = $instance->set($key, serialize($value), $flags, 0, 1, 0, 0);
		$this->assertTrue($returnValue, "Checksum Zero");

		$returnValue = $instance->get($key);
		$this->assertNotEquals($returnValue, false, "get::checksum zero");
	}


	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Checksum_Correct_Outer($key, $value, $flags) {

		$instance = Connection::getSocketConn();

		$returnValue = $instance->set($key, serialize($value),$flags, 0, 2, CRC_generator::getCksum($flags, serialize($value)));
		$this->assertTrue($returnValue);

		$returnValue = $instance->get($key);
		$this->assertNotEquals($returnValue, false, "get::checksum zero");
	}

	/**
	* @dataProvider keyValueProvider
	*/
	public function test_Checksum_OuterBad($key, $value) {

		$instance = Connection::getSocketConn();
		$value1 = serialize($value);

		$returnValue = $instance->set($key, $value1, 0, 0, 2, crc32("something else"));
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Checksum Zero");

		$returnValue = $instance->get($key);
		$this->assertFalse($returnValue, "get::checksum zero");
	}

	/**
	* @dataProvider keyValueProvider
	*/
	public function test_Checksum_InnerBad($key, $value) {

		$instance = Connection::getSocketConn();
		$value1 = serialize($value);
		$bz_val = bzcompress($value1);

		$returnValue = $instance->set($key, $bz_val, 0, 0, 2, CRC_generator::getCksum(MEMCACHE_COMPRESSED_BZIP2, $bz_val), crc32('bad'));
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Checksum Zero");

		$returnValue = $instance->get($key);
		$this->assertFalse($returnValue, "get::checksum zero");
	}



	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Checksum_Append($key, $value, $flags) {

		$instance = Connection::getSocketConn();

		$value1 = serialize($value);
		$returnValue = $instance->set($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, $value1));
		$this->assertTrue($returnValue);

		$returnValue = $instance->append($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, $value1));
		$this->assertTrue($returnValue);

		$returnValue = $instance->get($key);
		$this->assertEquals($returnValue, $value1.$value1, "Append");
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Bad_Checksum_Append($key, $value, $flags) {

		$instance = Connection::getSocketConn();

		$value1 = serialize($value);
		$returnValue = $instance->set($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, $value1));
		$this->assertTrue($returnValue);

		$returnValue = $instance->append($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, "SOme Value"));
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Append");

		$returnValue = $instance->get($key);
		$this->assertEquals($returnValue, $value1, "Append");
	}


	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Checksum_Prepend($key, $value, $flags) {

		$instance = Connection::getSocketConn();

		$value1 = serialize($value);
		$returnValue = $instance->set($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, $value1));
		$this->assertTrue($returnValue);

		$returnValue = $instance->prepend($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, $value1));
		$this->assertTrue($returnValue);

		$returnValue = $instance->get($key);
		$this->assertEquals($returnValue, $value1.$value1, "Append");
	}

	/**
	* @dataProvider keyValueFlagsProvider
	*/
	public function test_Bad_Checksum_Prepend($key, $value, $flags) {

		$instance = Connection::getSocketConn();

		$value1 = serialize($value);
		$returnValue = $instance->set($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, $value1));
		$this->assertTrue($returnValue);

		$returnValue = $instance->prepend($key, $value1,$flags, 0, 2, CRC_generator::getCksum($flags, "SOme Value"));
		$this->assertEquals($returnValue,'CHECKSUM_FAILED', "Append");

		$returnValue = $instance->get($key);
		$this->assertEquals($returnValue, $value1, "Append");
	}
}


class MBSocket_TestCase_Quick extends MBSocket_TestCase{
	public function keyProvider() {
		return Data_generation::provideKeys();
	}
	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}
	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
	public function flagsProvider() {
		return Data_generation::provideFlags();
	}
}

?>
