#!/usr/bin/env python
import os
import sys
import time
import pexpect
import commands
import getopt
import subprocess
import logging
import signal

TIMEOUT = 60
EOF_TIMEOUT = 5

logger = logging.getLogger('reshard')
hdlr = logging.FileHandler('/var/log/reshard.log')
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)

def parse_args(args):
    try:
	opts, args = getopt.getopt(args, 'hs:d:u:p:q', ['help'])
    except getopt.GetoptError, e:
	usage("ERROR: " + e.msg)

    source_file = None
    dest_file = None
    user = None
    passwd = None

    for (o, a) in opts:
	if o == '--help' or o == '-h':
	    usage()
	elif o == '-s':
	    source_file = a
        elif o == '-d':
	    dest_file = a
	elif o == '-u':
	    user = a
	elif o == '-p':
	    passwd = a
	
    if not source_file or not dest_file:
	usage("ERROR: missing source or destination file")

    return source_file, dest_file, user, passwd, args

def signal_handler(signal, frame):
    kill_all()
    stop_script()
    os._exit(1)

def ssh_command(user, host, password, command):
    """Connect to the remote machine, and return the pexpect.spawn object
    """
    ssh_newkey = 'Are you sure you want to continue connecting'
    child = pexpect.spawn('ssh -l %s %s %s' %(user, host, command), timeout=TIMEOUT)
    try:
        i = child.expect([pexpect.TIMEOUT, ssh_newkey, 'password: '])
        if i == 0: # Timeout
	    if (child.isalive()):
                return child
	    print 'ERROR!'
	    print 'SSH could not login to %s' %(host)
	    print child.before, child.after
	    return None

        if i == 1: #SSH does not have a public key. Just accept it.
  	    child.sendline ('yes')
	    child.expect ('password: ')
	    i = child.expect([pexpect.TIMEOUT, 'password: '])
	    if i == 0: # Timeout
	        print 'ERROR!'
	        print 'SSH could not login to %s' %(host)
    	        print child.before, child.after
	        return None
        child.sendline(password)
        return child
    except Exception, e:
        if (child.isalive()):
            return child
        else:
            print "Exception is %s" %(e)
	    return None

def scp_command(user, host, password, file):
    """Copy file to the remote machine, and return the pexpect.spawn object
    """
    ssh_newkey = 'Are you sure you want to continue connecting'
    child = pexpect.spawn('scp %s %s@%s:/tmp' %(file, user, host), timeout=TIMEOUT)
    try:
        i = child.expect([pexpect.TIMEOUT, ssh_newkey, 'password: '])
        if i == 0: # Timeout
	    print 'ERROR!'
	    print 'SCP could not connect to %s' %(host)
	    print child.before, child.after
	    return None

        if i == 1: #SSH does not have a public key. Just accept it.
  	    child.sendline ('yes')
	    child.expect ('password: ')
	    i = child.expect([pexpect.TIMEOUT, 'password: '])
	    if i == 0: # Timeout
	        print 'ERROR!'
	        print 'SCP could not connect to %s' %(host)
    	        print child.before, child.after
	        return None
        child.sendline(password)
        return child
    except:
        i = child.expect(pexpect.EOF)
	return child

def start_reshard_script(host, port, user, passwd, dest_file, source_num, source_index):
    "Start the resharding script"
    child = scp_command(user, host, passwd, dest_file)
    dest_file = os.path.basename(dest_file)  
 
    if child:
	child.expect(pexpect.EOF)
        command = "python /opt/zbase/bin/ep_engine/management/tap-redistribute-keys.py -s 5 -q 50 -d /tmp/" + dest_file  + " -n " + str(source_num) + " -i " + str(source_index) + " localhost:11211"
        child = ssh_command(user, host, passwd, command) 
        return child
    else:
	return None

def get_stats():
    global source_list
    global dest_list
    global source_stats
    global dest_stats
    source_keys = 0
    dest_keys = 0
    source_map = list()
    dest_map = list()
    for source in source_list:
	host, port = source
	cmd = "echo stats | nc " + (host) + " " + str(port) + " | grep \"curr_items \" "
	cmd2 = "echo stats tap | nc " + (host) + " " + str(11211) + "| grep reshard |grep \"rec_fetched \""
        status, output = commands.getstatusoutput(cmd)
	if status != 0:
	    print "Could not get stats for %s" %(host)
	    return
	s, key, value = output.split(' ')
	source_keys = source_keys + int(value)
	source_map.append((host, "cur_items", int(value)))
	
    for dest in dest_list:
	host, port = dest
	cmd = "echo stats | nc " + (host) + " " + str(port) + " | grep \"curr_items \" "
	status, output = commands.getstatusoutput(cmd)
	if status != 0:
	    print "Could not get stats for %s" %(host)
	    return    
	s, key, value = output.split(' ')
	dest_keys = dest_keys + int(value)
	dest_map.append((host, "cur_items", int(value)))

    print "Source ip                                current_item"
    for source in source_map:
	host, key, value = source
	print '%-40s %-20d' %(host, value)
    print 'Total keys on source machines: ', source_keys
	    

    print "Dest ips                                 current_item" 	
    for dest in dest_map:
	host, key, value = dest
	print'%-40s %-20d' %(host, value)	
    print 'Total keys on destination machines: ', dest_keys	
	

def check_stats():
    global source_list
    global dest_list
    global source_stats
    global dest_stats
    global migration_finished
    source_keys = 0
    dest_keys = 0
    source_map = list()
    dest_map = list()
    for source in source_list:
	host, port = source
	cmd = "echo stats | nc " + (host) + " " + str(port) + " | grep \"curr_items \" "
	status, output = commands.getstatusoutput(cmd)
	if status != 0:
	    return
	s, key, value = output.split(' ')
	source_keys = source_keys + int(value)
	source_map.append((host, "cur_items", int(value)))
	
    for dest in dest_list:
	host, port = dest
	cmd = "echo stats | nc " + (host) + " " + str(port) + " | grep \"curr_items \" "
	status, output = commands.getstatusoutput(cmd)
	if status != 0:
		return    
	s, key, value = output.split(' ')
	dest_keys = dest_keys + int(value)
	dest_map.append((host, "cur_items", int(value)))
 
    if (source_keys == dest_keys):
	print "Migrated all the keys from source to destination"
	migration_finished = 1
	logger.info('Key distribution before the migration')	
	logger.info('ip                                       current_item' )	
	for source in source_map:
	    host, key, value = source
	    logger.info('%-40s %-20d', host, value)	
        logger.info('Total keys on source machines: %d', source_keys)
	
	logger.info('\nKey distribution after the migration')	
	logger.info('ip                                       current_item' )	
        for dest in dest_map:
	    host, key, value = dest
	    logger.info('%-40s %-20d', host, value)	
        logger.info('Total keys on destination machines: %d', dest_keys)
	hdlr.close()
         	    
def stop_script():
    global user
    global passwd
    global source_list
    for source in source_list:
	host, port = source
        command = "\"ps -aef | grep tap-re | awk \'{print $2}\' | xargs kill -9\""    
        child = ssh_command(user, host, passwd, command)
        if child != None :
            child.expect(pexpect.EOF)

def kill_all(): 
    global source_processes
    for source_process in source_processes:
	host, process = source_process
    	process.kill(9)

def main(dest_file):
    global source_list
    global migration_finished
    global source_processes
    global user 
    global passwd
    migration_finished = 0
    source_processes = list()
    count = 0
    source_num = len(source_list)
    signal.signal(signal.SIGINT, signal_handler)
    for source in source_list:
	host, port = source
	child = start_reshard_script(host, port, user, passwd, dest_file, source_num, count)
	count = count + 1
        if child != None :
	    source_processes.append((host,child))
	else:
	    print "Error connecting to host %s" %(host)
            kill_all()
	    os._exit(1)
    
    while 1:
	for source_process in source_processes:
	    try:
		host, process = source_process
		process.expect(pexpect.EOF, EOF_TIMEOUT)
		print "Script terminated for host %s" %(host)
		print process.before
		kill_all()
		os._exit(1)
	    except :
    	        continue
	
	if not migration_finished:
	    check_stats()


if __name__ == '__main__':
    global source_list
    global dest_list
    global user
    global passwd
    source_list = list()
    dest_list = list()
    source_file, dest_file, user, passwd, args = parse_args(sys.argv[1:])
	
    """ Open the source file """
    for source in open(source_file, "r"):
	host, port = source.split(':', 1)
	port = int(port)		    
	source_list.append((host, port))

    for dest in open(dest_file, "r"):
	host, port = dest.split(':', 1)
	port = int(port)		    
	dest_list.append((host, port))
   
    if ( args and args[0] == "status"):
	get_stats()
    elif ( args and args[0] == "stop"):
	stop_script(user, passwd)
    else:
        main(dest_file)

