<?php
require_once 'PHPUnit/Framework.php';
require_once 'Include/Utility.php';
//error_reporting ( E_WARNING );
define('MICRO_TO_SEC',          1000000);
define('SHARD', 1);

abstract class ALogger_TestCase extends ZStore_TestCase {
        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_setByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;

                $instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("setByKey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider keyValueFlagsProvider
     */

        public function test_setByKey_getByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;

                $instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("setByKey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");

                $instance->getByKey($testKey,SHARD, $testValue, $cas);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("getBykey", trim($output["command"]), "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider keyValueFlagsProvider
     */

        public function test_casByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;

                $instance->setByKey($testKey, $testValue, $testFlag, 0, 0, SHARD);
                $instance->getByKey($testKey,SHARD, $testValue, $cas);
                $instance->casByKey($testKey, $testValue, $testFlag, 0, $cas, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("casByKey", trim($output["command"]), "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_EXISTS, $output["res_code"], "respcode");

        }


        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_addByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;

                $instance->addByKey($testKey, $testValue, $testFlag, 0, 0, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("addByKey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_incrementByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;
		$testValue1 = strlen(serialize($testValue));

                $instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARD);
		$instance->incrementByKey($testKey, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("incrementbykey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_decrementByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;
                $testValue1 = strlen(serialize($testValue));

                $instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARD);
                $instance->decrementByKey($testKey, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("decrementbykey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_SUCCESS, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_appendByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;
                $testValue1 = "testValue1";
		$testValue2 = "testValue2";

                $instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARD);
                $instance->appendByKey($testKey, $testValue2, 0, 0, 0, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("appendByKey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");

        }

        /**
     * @dataProvider keyValueFlagsProvider
     */
        public function test_prependByKey($testKey, $testValue, $testFlag) {

                $instance = $this->sharedFixture;
                $testValue1 = "testValue1";
                $testValue2 = "testValue2";

                $instance->addByKey($testKey, $testValue1, $testFlag, 0, 0, SHARD);
                $instance->prependByKey($testKey, $testValue2, 0, 0, 0, SHARD);
                $output = Utility::parseLoggerFile_temppath();
                $this->assertEquals($GLOBALS['testHost'], $output["host"], "Server name");
                $this->assertEquals("prependByKey", $output["command"], "Command");
                $this->assertEquals($testKey, $output["key"], "keyname");
                $this->assertEquals(MC_STORED, $output["res_code"], "respcode");

        }

}



class Logger_TestCase_Quick extends ALogger_TestCase{
        public function simpleKeyValueProvider() {
                return array(array("test_key", "test_value"));
        }
        public function simpleNumericKeyValueProvider() {
                return array(array("test_key", 5));
        }
        public function keyProvider() {
                return Utility::provideKeys();
        }

        public function keyValueProvider() {
                return Utility::provideKeyValues();
        }

        public function keyValueFlagsProvider() {
                return Utility::provideKeyValueFlags();
        }

        public function flagsProvider() {
                return Utility::provideFlags();
        }
}
?>

