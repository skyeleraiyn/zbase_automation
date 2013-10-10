#!/usr/bin/env python26
import sys
sys.path.insert(0,'/opt/zbase/zbase-backup/')
from backuplib import BackupFactory

#from subprocess import call
#call(["export", "LD_LIBRARY_PATH=/opt/sqlite3/lib/:$LD_LIBRARY_PATH"])
import subprocess
subprocess.Popen("export LD_LIBRARY_PATH=/opt/sqlite3/lib/:$LD_LIBRARY_PATH", shell=True)

if __name__ == '__main__':

    class L:
        def log(self,msg):
            print msg

    logger = L()
    base_filepath = "output_mbb/test-%.mbb"
    backup_type = "full" # full or incr
    tapname = "backup"
    txn_size = 100
    bo = BackupFactory(base_filepath, backup_type, tapname,logger, '0', 11211, txn_size)
    while not bo.is_complete():
        print bo.create_next_split('/tmp/')
        #create file at /dev/shm/test/test-%.mbb

    print bo.list_splits()
