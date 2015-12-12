# -*- coding: utf-8 -*-

"""
Description
"""

__author__ = 'TT'


from tornado.tcpclient import TCPClient
from tornado import gen
from tornado.ioloop import IOLoop
import json
import time
from hashlib import md5
import socket
from struct import pack, unpack


class Hancock(object):
    """"""

    boa = None
    host = '127.0.0.1'
    port = '6666'
    func = 'control'
    stream = None
    is_connection = False
    _md5 = None
    md5 = None
    device_type = None
    device_id = None
    length = None
    timestamp = None
    ip = None

    def __init__(self, host=None, port=None, func=None):
        """"""
        if host is not None:
            self.host = host
        if port is not None:
            self.port = port
        if func is not None:
            self.func = func
        self.__boa()

    @gen.coroutine
    def __boa(self):
        """"""
        self.boa = yield TCPClient().connect(self.host, self.port)

        self.__register()
        self.hold()

    def write(self, body, device, ip):
        """"""
        msg = json.dumps(body)
        # header = pack("I32s", socket.htonl(len(msg)), md5(msg).hexdigest())
        _ip = socket.htonl(unpack('I', socket.inet_aton(ip))[0])
        header = pack("2I32sdII", socket.htonl(device),
                      socket.htonl(device), self._md5, time.time(),
                      socket.htonl(len(body)), _ip)
        m = header + msg
        self.boa.write(m)
        self.hold()

    def __register(self):
        """"""

        body = dict(function=self.func, timestamp=time.time())
        msg = json.dumps(body)
        header = pack("I32s", socket.htonl(len(msg)), md5(msg).hexdigest())
        m = header + msg
        print(m)
        self.boa.write(m)
        self.boa.read_bytes(4, callback=self.__register_callback)

    def __register_callback(self, data):
        """"""
        print(3333)
        # self.boa.read_bytes(4, callable=self.__register_callback)
        # print(res)
        length = socket.ntohl(unpack('I', data)[0])
        self.boa.read_bytes(length, callback=self.__register_callback_body)

    def __register_callback_body(self, data):
        """"""
        print(4444)
        msg = json.loads(data)
        print(msg)
        if msg.get('status', None) == 0:
            print('register success')
            self.is_connection = True
        else:
            print('register failed')
            print(msg.get('reason', 'no reason'))
            self.is_connection = False

    def hold(self):
        """"""
        self.boa.read_bytes(4, self.read_header)

    def read_header(self, data):
        """"""
        # 2I32sdII
        # 2I: 8, 32s: 32, d: 8, II: 8
        self.device_type, self.device_id, self.md5, self.timestamp, self.length, self.ip = map(socket.ntohl, unpack('2I32sdII', data))

        self.boa.read_bytes(self.length, self.read_body)

    def read_body(self, data):
        """"""
        if self.md5 == md5(data).hexdigest():
            msg = json.loads(data)
            print(msg)
        else:
            print('check md5 failed')
        self.hold()


if __name__ == '__main__':
    # ttt()
    h = Hancock()
    print(11111111)
    # h.write('hello')
    IOLoop.current().start()
