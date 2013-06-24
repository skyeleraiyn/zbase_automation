
Download and Install ngrep:
http://pkgs.repoforge.org/ngrep/ngrep-1.45-1.el5.rf.x86_64.rpm
http://pkgs.repoforge.org/ngrep/ngrep-1.45-2.el6.rf.x86_64.rpm

copy timeout script and run the following command:
	for i in {1..300} ; do ./timeout -i 60 ngrep 'get|getl' port 11211 -S 100 | grep -e "get F" -e "getl F" >>hostname ; sleep 240 ; done
This will copy 1 minute get and getl commands coming over the traffic onto the hostname.log file once in every 5mins.

To replay the copied events, copy the files to /dev/shm folder and use get_keys.php

