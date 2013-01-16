import pickle
import json

ifile=open("/var/tmp/disk_mapper/host.mapping")
unpickled_data= pickle.load(ifile)
print json.dumps(unpickled_data)