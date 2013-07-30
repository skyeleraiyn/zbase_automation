<?php
global $vbs_machine;
define('VBS_IP',$vbs_machine[0]);
define('GET_VBS_MAPPING', "http://".VBS_IP.":6060/vbucketServer/vbucketMap");
define('SERVER_ALIVE_API', "http://".VBS_IP.":6060/cluster1/serverAlive");
define('SERVER_DOWN_API', "http://".VBS_IP.":6060/cluster1/reshardDown");
define('VB_DOWN', "http://".VBS_IP.":6060/vbucketServer/deadvBuckets");

?>
