#!/usr/bin/env python
"""
TAP protocol client library.
Copyright 2013 Zynga Inc
Copyright (c) 2010  Dustin Sallings <dustin@spy.net>
"""

import socket
import string
import random
import struct
import asyncore
import time
import os

import mc_bin_server
import mc_bin_client

from memcacheConstants import REQ_MAGIC_BYTE, RES_MAGIC_BYTE
from memcacheConstants import REQ_PKT_FMT, RES_PKT_FMT, MIN_RECV_PACKET
from memcacheConstants import SET_PKT_FMT, DEL_PKT_FMT, INCRDECR_RES_FMT

import memcacheConstants

class TapConnection(mc_bin_server.MemcachedBinaryChannel):

    def __init__(self, server, port, callback, clientId=None, opts={}, user=None, pswd=None):
        mc_bin_server.MemcachedBinaryChannel.__init__(self, None, None,
                                                      self._createTapCall(clientId,
                                                                          opts))
        self.server = server
        self.port = port
        self.callback = callback
        self.identifier = (server, port)
        self.user = user
        self.pswd = pswd
        self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
        self.tapMutations = 0
        self.tapDelete = 0
        self.connect((server, port))

    def create_socket(self, family, type):
        if not self.user:
            mc_bin_server.MemcachedBinaryChannel.create_socket(self, family, type)
            self.client = mc_bin_client.MemcachedClient(self.server, self.port)
            return

        self.family_and_type = family, type

        self.mc = mc_bin_client.MemcachedClient(self.server, self.port)
        self.mc.sasl_auth_plain(self.user, self.pswd or "")
        sock = self.mc.s
        sock.setblocking(0)
        self.set_socket(sock)
        self.client = mc_bin_client.MemcachedClient(self.server, self.port)
        self.client.sasl_auth_plain(self.user, self.pswd or "")

    def _createTapCall(self, key=None, opts={}):
        # Client identifier
        if not key:
            key = "".join(random.sample(string.letters, 16))
        dtype=0
        opaque=0
        cas=0

        extraHeader, val = self._encodeOpts(opts)

        msg=struct.pack(REQ_PKT_FMT, REQ_MAGIC_BYTE,
                        memcacheConstants.CMD_TAP_CONNECT,
                        len(key), len(extraHeader), dtype, 0,
                        len(key) + len(extraHeader) + len(val),
                        opaque, cas)
        return msg + extraHeader + key + val

    def _encodeOpts(self, opts):
        header = 0
        val = []
        for op in sorted(opts.keys()):
            header |= op
            if op in memcacheConstants.TAP_FLAG_TYPES:
                val.append(struct.pack(memcacheConstants.TAP_FLAG_TYPES[op],
                                       opts[op]))
            elif op == memcacheConstants.TAP_FLAG_LIST_VBUCKETS:
                val.append(self._encodeVBucketList(opts[op]))
            else:
                val.append(opts[op])
	return struct.pack(">I", header), ''.join(val)

    def _encodeVBucketList(self, vbl):
        l = list(vbl)
        vals = [struct.pack("!H", len(l))]
        for v in vbl:
            vals.append(struct.pack("!H", v))
        return ''.join(vals)

    def processCommand(self, cmd, klen, vb, extralen, cas, data):
            extra = data[0:extralen]
            key =  data[extralen:(extralen+klen)]
            val = data[(extralen+klen):]
            self.extralen = extralen
            return self.callback(self, cmd, extra, key, vb, val, cas)

    def handle_connect(self):
        pass

    def handle_close(self):
            print "Connection closed by source"
            self.close()

class TapClient(object):

    def __init__(self, servers, callback, opts={}, user=None, pswd=None):
        for t in servers:
            tc = TapConnection(t.host, t.port, callback, t.id, t.opts, user, pswd)

class TapDescriptor(object):
    port = 11211
    id = None

    def __init__(self, s, opts):
        self.host = s
    	self.opts = opts
        if ':' in s:
            self.host, self.port = s.split(':', 2)
            self.port = int(self.port)

        if '@' in self.host:
            self.id, self.host = self.host.split('@', 1)

        if self.id is None:
            self.id = "".join(random.sample(string.letters, 16))

    def __repr__(self):
        return "<TapDescriptor %s@%s:%d>" % (self.id or "(anon)", self.host, self.port)
