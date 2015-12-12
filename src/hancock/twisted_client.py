# -*- coding: utf-8 -*-

"""
Description
"""

__author__ = 'TT'


from twisted.internet import reactor
from twisted.internet.protocol import Protocol
from twisted.internet.endpoints import TCP4ClientEndpoint, connectProtocol
import json
from hashlib import md5
import time
from struct import pack, unpack
import socket
import requests


class Hancock(Protocol):
    func = 'control'

    def sendMessage(self, body):
        """"""
        msg = json.dumps(body)
        header = pack("I32s", socket.htonl(len(msg)), md5(msg).hexdigest())
        m = header + msg
        print(m)
        self.transport.write(m)

    def dataReceived(self, data):
        """"""
        print(data)
        header = data[:4]
        length = socket.ntohl(unpack('I', header)[0])
        body = json.loads(data[4:4 + length])
        if length == 27:
            # register callback
            if body.get('status', None) == 0:
                print('register success!')
            else:
                print('register failed!')
        else:
            # 数据传输
            pass


def register(p):
    """"""
    body = dict(function='control', timestamp=time.time())
    p.sendMessage(body)


if __name__ == '__main__':
    """"""
    point = TCP4ClientEndpoint(reactor, "127.0.0.1", 6666)
    d = connectProtocol(point, Hancock())
    d.addCallback(register)
    reactor.run()
