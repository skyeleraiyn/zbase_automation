import json
import sys
if len(sys.argv) < 2:
        sys.exit("usage: python %s file_name(required)" %sys.argv[0])

ifile=open(sys.argv[1])
d = ifile.read()
print json.dumps(eval(d))

