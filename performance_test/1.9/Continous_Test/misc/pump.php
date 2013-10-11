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
#error_reporting (E_ERROR | E_WARNING | E_PARSE);
function usage()
{
echo "\nUsage : php pump.php -i ip -n number of keys -p port for moxi -s size of keys\n\n";
exit();
}
global $num;
function main()
{
$options = getopt("i:n:p:s");
if(isset($options['i']))
        {
        $moxi_ip=$options['i'];
        }
        else
        {
        echo "\nip for pumping keys  \n ";
        usage();
        }

if (isset($options['n']))
        {
        $n=$options['n'];
        }
        else
        {
        echo "\n number of keys to be pumped \n";
        usage();
        }
if (isset($options['p']))
        {
        $port=$options['p'];
        }
        else
        {
        echo "\n port \n";
        usage();
        }

if (isset($options['s']))
        {
        $size=(int)$options['s'];
        }
        else
        {
        $size=1024;
        }


$mc = new Memcache;
$ip_array=split(":",$moxi_ip);
foreach($ip_array as $i=>$ip)
	if($ip!=NULL)
        $mc->addserver($ip,$port);
$value="#";
for($i=0;$i<$size;$i++)
$value.="#";
global $tot;
$tot=$n;
$expiry=0;
register_shutdown_function('shutdown');
for( $i=0;$i<$n; $i++ )
	{
#	echo $i."\n";
	try{
	$x=$mc->set('testkey'.$i,$value,$expiry);
	if($x != 1)
		exit();
	global $num;
	$num=$i+1;
	}

	catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
	continue;
	}

	}


}

function shutdown()
	{
	global $num,$tot;
	echo "\nTotal no of keys pumped:".$num;
	if($tot == $num) {
		echo "\nSuccess!";
	}
	}


main();
?>
