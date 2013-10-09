#!/usr/bin/env python
"""
Example script for tap.py.

Copyright (c) 2010  Dustin Sallings <dustin@spy.net>
"""

import os
import sys
import string
import asyncore
import signal
import getopt
import struct
import zlib
import collections
import threading
import Queue
import time
import socket
import logging
import re
#import simplejson
#import pycurl
import StringIO
from threading import Condition

import mc_bin_server, mc_bin_client
import mc_bin_client_async

from memcacheConstants import REQ_MAGIC_BYTE, RES_MAGIC_BYTE
from memcacheConstants import REQ_PKT_FMT, RES_PKT_FMT, MIN_RECV_PACKET
from memcacheConstants import SET_PKT_FMT, DEL_PKT_FMT, INCRDECR_RES_FMT

import memcacheConstants

import tap

logger = logging.getLogger('tap-zbase')
hdlr = logging.FileHandler('/tmp/rejected-keys', 'w') 
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)

def usage(err=0):
    print >> sys.stderr, """
Usage: %s [-u bucket_user [-p bucket_password]] [-d dest_server_file] [-q ack_queue_size] [-s sleep_time] [-n no_source_servers] [-k (key_only)] [-z (vbucket mode)] [-i source_index] host:port [... hostN:portN]

Example:
  %s -u user_profiles -p secret9876 -d destination_server_file -q 1000 -s 1 -n 3 -i 1 zbase-01:11210 zbase-02:11210
""" % (os.path.basename(sys.argv[0]),
       os.path.basename(sys.argv[0]))
    sys.exit(err)

def parse_args(args):
    user = None
    pswd = None
    new_server_file = None
    queue_size = 0
    tap_name = None
    sleep_time = 1
    no_source_servers = None
    index_source = None
    vbucket_mode = False
    keyonly = False
    global key_regex

    try:
        opts, args = getopt.getopt(args, 'hzd:k:q:s:n:i:u:p', ['help'])
    except getopt.GetoptError, e:
        usage("ERROR: " + e.msg)

    for (o, a) in opts:
        if o == '--help' or o == '-h':
            usage()
        elif o == '-u':
            user = a
        elif o == '-p':
            pswd = a
        elif o == '-d':
            new_server_file = a
        elif o == '-q':
            queue_size = int(a)
        elif o == '-s':
            sleep_time = int(a)
        elif o == '-n':
            no_source_servers = int(a)
        elif o == '-i':
            index_source = int(a)
        elif o == '-z':
            vbucket_mode = True
        elif o == '-k':
            keyonly = True
            if a == 'None':
                key_regex = None
            else:
                key_regex = re.compile(a)
        else:
            usage("ERROR: unknown option - " + o)

    if not args or len(args) < 1 or not new_server_file:
        usage("ERROR: missing at least one host:port or newServer:port to TAP")
    
    if no_source_servers and index_source == -1:
        usage("ERROR: need to provide both number of source servers and source index")

    if queue_size == 0:
        queue_size = 1
    return user, pswd, new_server_file, queue_size, sleep_time, no_source_servers, index_source, vbucket_mode, keyonly, args

    
def signal_handler(signal, frame):
    global client_thread
    print >> sys.__stderr__,'Tap stream terminated by user'
    print >> sys.__stderr__,'Total number of mutations %d' %(num_mutations)
    print >> sys.__stderr__,'Total number of deletes %d' %(num_delete)
    hdlr.close()
    os._exit(1)

def buildGoodSet(goodChars=string.printable, badChar='?'):
    """Build a translation table that turns all characters not in goodChars
    to badChar"""
    allChars=string.maketrans("", "")
    badchars=string.translate(allChars, allChars, goodChars)
    rv=string.maketrans(badchars, badChar * len(badchars))
    return rv

# Build a translation table that includes only characters
transt=buildGoodSet()

def abbrev(v, maxlen=30):
    if len(v) > maxlen:
        return v[:maxlen] + "..."
    else:
        return v

def keyprint(v):
    return string.translate(abbrev(v), transt)

def computeHash(key):
    hash = (zlib.crc32(key) >> 16) & 0x7fff
    if (hash):
        return hash
    else:
        return 1

class clientThread(threading.Thread):
    """Thread for polling client side connection"""
    def __init__(self):
        threading.Thread.__init__(self)
        keys = asyncore.socket_map.keys()
        self.new_map = {}
        for key in keys:
            self.new_map[key] = asyncore.socket_map[key]
        
        asyncore.socket_map.clear()

    def run(self):
        try:
            asyncore.loop(0.0001, False, self.new_map, None)
        except Exception, e:
            print >>sys.__stderr__,"Got exception %s" %(e)
            os._exit(1)

def mainLoop(serverList, cb, user=None, pswd=None, s=None, v=None, k=None):
    """Run the given callback for each tap message from any of the
    upstream servers.

    loops until all connections drop
    """
    signal.signal(signal.SIGINT, signal_handler)
    connections = []
    retries = 0
    options = {memcacheConstants.TAP_FLAG_CKSUM : ''}

    if k:
        options[memcacheConstants.TAP_FLAG_REQUEST_KEYS_ONLY] = ''
         
    for a in serverList:
        if s and v:
            options[memcacheConstants.TAP_FLAG_LIST_VBUCKETS] = v[s.index(a)] 
        else:
            options[memcacheConstants.TAP_FLAG_LIST_VBUCKETS] = [0]
        connections.append(tap.TapDescriptor(a, options))

    try:
        while retries < memcacheConstants.MAX_SOURCE_RETRY:
            tap.TapClient(connections, cb, user=user, pswd=pswd)
            asyncore.loop(5)
            retries += 1
        os._exit(1);
    except Exception, e:
        print >>sys.__stderr__,"Got exception %s" %(e)
        os._exit(1)

if __name__ == '__main__':
    global num_old_servers
    global index_source
    global num_mutations
    global num_new_servers
    global new_clients
    global num_delete
    global key_regex 
    user, pswd, new_server_file, queue_size, sleep_time, num_old_servers, index_source, vbucket_mode, keyonly, args = parse_args(sys.argv[1:])
    num_mutations = 0
    num_new_servers = 0
    num_delete = 0
    
    def cbRehashRecheck(tapConnection, cmd, extra, key, vb, val, cas):
        global num_delete 
        global num_mutations
        global num_old_servers
        global index_source
        global num_new_servers 
        crcHashSource = computeHash(key)
        if index_source != crcHashSource % num_old_servers :
            if (cmd == memcacheConstants.CMD_TAP_MUTATION):
                logger.info('%s %d', key, (crcHashSource % num_old_servers))
            return  
        if (cmd == memcacheConstants.CMD_TAP_MUTATION): 
            # Add the key to the new servers
            num_mutations += 1

            # Consider only cksum_len, flags and expiry from extra data
            cksum_len, flags, expiry = struct.unpack(">bxxII", extra[5:16])
            crcHash = computeHash(key)
            if cksum_len > 0:
                cksum_offset = len(val) - cksum_len
                cksum = val[cksum_offset:-1]
                val = val[:cksum_offset]
            else:
                cksum = ''
            crcHash = computeHash(key)
            new_clients[crcHash % num_new_servers].set(key, (expiry), socket.ntohl(flags), val, cksum)
        elif (cmd == memcacheConstants.CMD_TAP_DELETE):
            num_delete += 1
            crcHash = computeHash(key)
            new_clients[crcHash % num_new_servers].delete(key)  

    def cbRehash(tapConnection, cmd, extra, key, vb, val, cas):
        global num_delete 
        global num_mutations
        global num_old_servers
        global index_source
        global num_new_servers
        if (cmd == memcacheConstants.CMD_TAP_MUTATION): 
        # Add the key to the new servers
            num_mutations += 1
            # Consider only cksum_len, flags and expiry from extra data
            cksum_len, flags, expiry = struct.unpack(">bxxII", extra[5:16])
            crcHash = computeHash(key)
            if cksum_len > 0:
                cksum_offset = len(val) - cksum_len
                cksum = val[cksum_offset:-1]
                val = val[:cksum_offset]
            else:
                cksum = ''
            new_clients[crcHash % num_new_servers].set(key, (expiry), socket.ntohl(flags), val, cksum)
        elif (cmd == memcacheConstants.CMD_TAP_DELETE):
            num_delete += 1
            crcHash = computeHash(key)
            new_clients[crcHash % num_new_servers].delete(key) 

    def cbKeyOnly(tapConnection, cmd, extra, key, vb, val, cas):
        global num_mutations
        global key_regex
        if (cmd == memcacheConstants.CMD_TAP_MUTATION): 
            num_mutations += 1
            if (not key_regex) or key_regex.match(key):
                logger.info('%s', key)
         
    def getVbuckets(serverList):
        for server in serverList:
            url = "http://" + server.split(":")[0] + ":8091/pools/default/buckets" 
            result = StringIO.StringIO()
            curl = pycurl.Curl()
            curl.setopt(pycurl.URL, url)
            curl.setopt(pycurl.WRITEFUNCTION, result.write)
            curl.perform()

            if (curl.getinfo(pycurl.HTTP_CODE) != 200):
                    self.Log.error("Not able to fetch the bucket information for server %s .Got error %s" %(server, result.getvalue()))
                    continue 

            decodedata = simplejson.loads(result.getvalue())
            out = list(decodedata)[0]
            vbucket_map = dict(out)['vBucketServerMap']['vBucketMap']
            server_map = dict(out)['vBucketServerMap']['serverList']
            bucketmap = list()
            vbucket = 0
            for l in vbucket_map:
                try:
                    ll = bucketmap.pop(l[0])
                except:
                    ll = list()
                ll.append(vbucket)
                bucketmap.insert(l[0],ll)
                vbucket+=1
            return server_map, bucketmap
        return None, None

    # This is an example opts parameter to do future-only tap:
    opts = {memcacheConstants.TAP_FLAG_BACKFILL: 0xffffffff}
    # If you omit it, or supply a past time_t value for backfill, it
    # will get all data.
    opts = {memcacheConstants.TAP_FLAG_LIST_VBUCKETS: [0]}
    new_clients = list()

    server_map = None
    vbucket_map = None

    if vbucket_mode:
        server_map, vbucket_map = getVbuckets(args) 
        if server_map is None or vbucket_map is None:
            self.Log.error("Not able to fetch the bucket information...so exiting")
            sys.exit(0)


    if not keyonly:
        for server in open(new_server_file,"r"):
            host, port = server.split(':', 1)
            port = int(port)
            support_cksum = mc_bin_client.MemcachedClient(host, port).options_supported()
            new_clients.append(mc_bin_client_async.MemcachedClient(host, port, queue_size,
                sleep_time, support_cksum))
            num_new_servers += 1

    client_thread = clientThread()
    client_thread.start()
    if keyonly:
        mainLoop(args, cbKeyOnly, user, pswd, server_map, vbucket_map, k=True)
    elif num_old_servers:
        mainLoop(args, cbRehashRecheck, user, pswd, server_map, vbucket_map)
    else:
        mainLoop(args, cbRehash, user, pswd, server_map, vbucket_map)
    

