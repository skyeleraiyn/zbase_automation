#!/usr/bin/env python

# 	Copyright 2013 Zynga Inc
#
#   Licensed under the Apache License, Version 2.0 (the "License");
#   you may not use this file except in compliance with the License.
#   You may obtain a copy of the License at
#
#       http://www.apache.org/licenses/LICENSE-2.0
#
#   Unless required by applicable law or agreed to in writing, software
#   distributed under the License is distributed on an "AS IS" BASIS,
#   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#   See the License for the specific language governing permissions and
#   limitations under the License.
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
