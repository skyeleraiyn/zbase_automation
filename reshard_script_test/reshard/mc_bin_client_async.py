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
"""
A memcached test client.
"""

import asyncore
import random
import string
import socket
import struct
import time
import hmac
import heapq
import threading
import exceptions
import random
import os
import sys
import collections
import syslog
import time
import logging 

import memcacheConstants

from threading import Condition, Lock
from collections import deque
from memcacheConstants import MIN_RECV_PACKET, REQ_PKT_FMT, RES_PKT_FMT
from memcacheConstants import INCRDECR_RES_FMT, PROTOCOL_BINARY_RESPONSE_CKSUM_FAILED
from memcacheConstants import REQ_MAGIC_BYTE, RES_MAGIC_BYTE, EXTRA_HDR_FMTS
from memcacheConstants import REQ_MAGIC_BYTE, RES_MAGIC_BYTE
from memcacheConstants import REQ_PKT_FMT, RES_PKT_FMT, MIN_RECV_PACKET
from memcacheConstants import SET_PKT_FMT, DEL_PKT_FMT, INCRDECR_RES_FMT


logger = logging.getLogger('tap-zbase-cksum')
cksumhdlr = logging.FileHandler('/tmp/cksum-failed-keys') 
logger.addHandler(cksumhdlr)
logger.setLevel(logging.INFO)

VERSION="1.0"

class MemcachedError(exceptions.Exception):
    """Error raised when a command fails."""

    def __init__(self, status, msg):
        supermsg='Memcached error #' + `status`
        if msg: supermsg += ":  " + msg
        exceptions.Exception.__init__(self, supermsg)

        self.status=status
        self.msg=msg

    def __repr__(self):
        return "<MemcachedError #%d ``%s''>" % (self.status, self.msg)

   

class MemcachedBinaryClient(asyncore.dispatcher):
    """A memcached client."""
    
    BUFFER_SIZE = 4096    

    def __init__(self, callback, host='127.0.0.1', port=11211):
        asyncore.dispatcher.__init__(self)
        self.host = host
        self.port = port
        self.log_info("Established new bin connection")
        self.wbuf=""
        self.rbuf=""
        self.vbucketId = 0
        self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
        self.connect((self.host, self.port)) 
        self.callback = callback
        self.wbuf_lock = Lock()
        
    def sendCommand(self, cmd, key, val, opaque, extraHeader='', cas=0, dtype=0):
        msg=struct.pack(REQ_PKT_FMT, REQ_MAGIC_BYTE,
            cmd, len(key), len(extraHeader), dtype, self.vbucketId,
                len(key) + len(extraHeader) + len(val), opaque, cas)
        self.wbuf_lock.acquire()
        self.wbuf += (msg + extraHeader + key + val)
        self.wbuf_lock.release()

    def __hasEnoughBytes(self):
        rv=False
        if len(self.rbuf) >= MIN_RECV_PACKET:
            magic, cmd, keylen, extralen, datatype, vb, remaining, opaque, cas=\
                struct.unpack(REQ_PKT_FMT, self.rbuf[:MIN_RECV_PACKET])
            rv = len(self.rbuf) - MIN_RECV_PACKET >= remaining
        return rv


    def handle_read(self):
        try:    
            self.rbuf += self.recv(self.BUFFER_SIZE)
            while self.__hasEnoughBytes():
                magic, cmd, keylen, extralen, dtype, errcode, remaining, opaque, cas=\
                    struct.unpack(RES_PKT_FMT, self.rbuf[:MIN_RECV_PACKET])
                assert (magic in (RES_MAGIC_BYTE, REQ_MAGIC_BYTE)), "Got magic: %d" %magic
                self.rbuf=self.rbuf[MIN_RECV_PACKET + remaining:]
                key = self.callback(opaque, errcode) 
        except Exception, e:
            print >>sys.__stderr__, "Error reading from host: %s, port %s. Error is %s" %(self.host, self.port, e) 
            os._exit(1)  
        
    def writable(self):
        return self.wbuf

    def handle_write(self):
        try:
            self.wbuf_lock.acquire()
            sent = self.send(self.wbuf)
            self.wbuf = self.wbuf[sent:]
            self.wbuf_lock.release()
        except Exception, e:
            print >>sys.__stderr__,"Error writing to destination - host: %s, port %s. Error is %s" %(self.host, self.port, e)   
            self.wbuf_lock.release()
            self.connect((self.host, self.port))
            
    def handle_connect(self):
        pass

    def handle_close(self):
        print >>sys.__stderr__, "Got close for client %s %d" %(self.host, self.port)
        os._exit(1)

class MemcachedClient(object):
    """Simple memcached client."""
    
    EVENT_WAIT_TIMEOUT = 600
    def __init__(self, host='127.0.0.1', port=11211, maxQueueSize = 10, sleepTime = 1, supportCksum = 0):
        self.async_client = MemcachedBinaryClient(self._processAck, host, port)
        self.r = random.Random()
        self.event_pending_ack_queue_empty = threading.Event()
        self.pending_msg_queue = deque()
        self.max_queue_size = maxQueueSize
        self.send_a_message = 0
        self.send_message_lock = threading.Lock()
        self.sleep_time = sleepTime
        self.set_send = 0
        self.ack = 0
        self.supportCksum = supportCksum 
            
    def close(self):
        self.aync_client.close()

    def _wait(self):
        while 1:
            self.event_pending_ack_queue_empty.wait(self.EVENT_WAIT_TIMEOUT)
            if(self.event_pending_ack_queue_empty.isSet()):
                break
            else:
                msg = self.pending_msg_queue[0] 
                self.async_client.sendCommand(msg[0], msg[1], msg[2], msg[3], msg[4], msg[5], self.supportCksum)
                    
    
    def _doCmd(self, cmd, key, val, extraHeader='', cas=0, cksum = ''):
        """Send a command and await its response."""
        opaque=self.r.randint(0, 2**32)
        self.event_pending_ack_queue_empty.clear()
        if (len(self.pending_msg_queue) >= self.max_queue_size):
            self._wait()
        msg = cmd, key, val, opaque, extraHeader, cas, cksum
        self.pending_msg_queue.append(msg)
        self.send_message_lock.acquire()
        if (not self.send_a_message):
            try:
                msg = self.pending_msg_queue[0]
                self.async_client.sendCommand(msg[0], msg[1], msg[2], msg[3], msg[4],
                    msg[5], self.supportCksum)
                self.send_a_message = 1
            except exceptions.IndexError: 
                self.send_message_lock.release()
                return
        self.send_message_lock.release()
 
    def _mutate(self, cmd, key, exp, flags, cas, val, cksum = None):
        if self.supportCksum and cksum:
            extra = struct.pack(memcacheConstants.SET_PKT_FMT_WITH_CKSUM, flags, exp, len(cksum));
            val = cksum + val
            return self._doCmd(cmd, key, val, extra, cas, cksum)
        else:
            self.supportCksum = 0
            extra = struct.pack(SET_PKT_FMT,flags, exp);
            return self._doCmd(cmd, key, val, extra, cas)

    def _processAck(self, opaque, errcode):
        try:
            #135 means checksum failed
            if errcode != 0 and errcode != 1 and errcode != PROTOCOL_BINARY_RESPONSE_CKSUM_FAILED:
                self.send_message_lock.acquire()
                msg = self.pending_msg_queue[0]
                self.send_message_lock.release()
                time.sleep(self.sleep_time)
                self.async_client.sendCommand(msg[0], msg[1], msg[2], msg[3], msg[4], msg[5], self.supportCksum)
            else:
                self.send_message_lock.acquire()
                msg = self.pending_msg_queue.popleft()
                # if checksum has failed, add the key to the cksum failed keys.
                if errcode == PROTOCOL_BINARY_RESPONSE_CKSUM_FAILED:
                    logger.info("%s", msg[1]) 
                if (msg[3] == opaque):
                    self.event_pending_ack_queue_empty.set()
                    if (self.send_a_message):
                        msg = self.pending_msg_queue[0]
                        self.async_client.sendCommand(msg[0], msg[1], msg[2], msg[3], msg[4], msg[5], self.supportCksum)
                    self.send_message_lock.release()
                else:
                    msg = self.pending_msg_queue[0]
                    self.async_client.sendCommand(msg[0], msg[1], msg[2], msg[3], msg[4], msg[5], self.supportCksum)
                    self.send_message_lock.release()
        except exceptions.IndexError: 
            self.send_a_message = 0
            self.send_message_lock.release()
    
    def set(self, key, exp, flags, val, cksum = None):
        """Set a value in the memcached server."""
        return self._mutate(memcacheConstants.CMD_SET, key, exp, flags, 0, val, cksum)

    def add(self, key, exp, flags, val, cksum = None):
        """Add a value in the memcached server iff it doesn't already exist."""
        return self._mutate(memcacheConstants.CMD_ADD, key, exp, flags, 0, val, cksum)

    def replace(self, key, exp, flags, val, cksum = None):
        """Replace a value in the memcached server iff it already exists."""
        return self._mutate(memcacheConstants.CMD_REPLACE, key, exp, flags, 0,
            val, cksum)
   
    def delete(self, key, cas=0):
        """Delete the value for a given key within the memcached server."""
        return self._doCmd(memcacheConstants.CMD_DELETE, key, '', '', cas)
 
