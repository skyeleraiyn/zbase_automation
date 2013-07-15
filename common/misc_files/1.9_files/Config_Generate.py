#!/usr/bin/env python
import sys
import commands
import re
import simplejson as json

primary = []
secondary = []
for ip in sys.argv[1].split(","):
    cmd = "pdsh -w " + ip + " \"/sbin/ifconfig -a\""
    status, output = commands.getstatusoutput(cmd)
    v=re.findall(r'inet addr:(\S+)',output)
    if len(v) >= 1:
        if v[0] != "127.0.0.1":
            primary.append(v[0] + ":11211") 
    if len(v) >=2:        
        if v[1] != "127.0.0.1":
            secondary.append(v[1] + ":11211")

print json.dumps({"cluster1":{"Port":11114,
    "Vbuckets" : int(sys.argv[2]),                        
    "Replica" : int(sys.argv[3]),                           
    "Servers":primary,                      
    "SecondaryIps":secondary,               
    "Capacity" : int(sys.argv[4])                       
}})
