#coding=utf-8

"""Connection"""

__author__ = "Yingqi Jin <jinyingqi@luoha.com>"

import json
import time
import socket
import logging
import threading
from struct import pack, unpack

from config import get_server
from bconnection import BusinessConnection

HEADER_LENGTH = 24

def get_addr_str(addr):
    return '%s:%d' % (addr[0], addr[1])


class Connection(object):
    clients = set() # store all the connection instance
    conns = {} # item: type : map
    conns_lock = threading.Lock()
    header_length = HEADER_LENGTH 

    @classmethod
    def clean_connection(cls):
        for cli in cls.clients:
            cli._stream.close()

    def __init__(self, stream, address):
        Connection.clients.add(self)
        self._stream = stream
        self._address = address
        self._addr_str = get_addr_str(self._address) 
        self._type = 1 # app:1 box:2 erp:3 init:4
        self._registed = False

        self._header = ''
        self._body = ''
        self._author = ''
        self._version = ''
        self._request = 0
        self._length = 0
        self._verify = 0
        self._device = 0

        self._type_name_map = {
            1 : 'app',
            2 : 'box',
            3 : 'erp',
            4 : 'init',
        }

        self._stream.set_close_callback(self.on_close)

        self._stream.read_bytes(Connection.header_length, self.read_header)


    def set_type(self, device_type):
        self._type = device_type


    def read_header(self, header):
        self._header = header
        parts = unpack("6I", self._header)
        from socket import ntohl
        parts = [ntohl(x) for x in parts]

        (self._author, self._version, self._request,
            self._verify, self._length, self._device) = parts
        logging.debug('read header(%d, %d, %d, %d, %d, %d) from %s' % (
            self._author, self._version, self._request,
            self._verify, self._length, self._device,
            self._addr_str))

        # store relation
        Connection.conns_lock.acquire()
        self._registed = True

        if self._type not in Connection.conns:
            Connection.conns[self._type] = {}

        Connection.conns[self._type][self._device] = self
        Connection.conns_lock.release()

        self._stream.read_bytes(self._length, self.read_body)


    def read_body(self, body):
        logging.debug('read body(%s) from %s' % (body, self._addr_str))
        self._body = body

        business = get_server(self._request)        

        BusinessConnection.clients_lock.acquire()
        if business in BusinessConnection.clients:
            logging.debug('forward request to %s' % business)
            conn = BusinessConnection.clients[business].pop()
            BusinessConnection.clients[business].add(conn)
            logging.debug('send data(header:%d, %d, %d, %d, %d, %d body:%s len:%d) to %s' %
                (self._author, self._version, self._request,
                 self._verify, self._length, self._device,
                 self._body, len(self._body), self._addr_str))
            conn.send(self._header + self._body, self._type, self._device, self._address[0])
        else:
            logging.debug('no %s business server is avaliable' % business)
        BusinessConnection.clients_lock.release()

        self._stream.read_bytes(Connection.header_length, self.read_header)


    def send(self, msg):
        if len(msg) < 24:
            logging.error('unexpected packet')
            return

        from socket import ntohl, htonl
        parts = unpack("6I", msg[:24])
        parts = [ntohl(x) for x in parts]
        author, version, request, verify, length, device = parts

        # TODO: fix the bug
        #if request == 10001:
        #    body = """<root><status>0</status><result>0</result><boxid>6</boxid><infoip>192.168.1.199</infoip><infoport>58849</infoport><serverid>1</serverid><video>http://192.168.1.233</video><version>245</version><apkurl>install/KTVBox.apk</apkurl><tvPlayUrl>rtsp://192.168.1.254/h264</tvPlayUrl><wineServerUrl>http://192.168.1.233:22</wineServerUrl><romVersion>2.100.100</romVersion><romUrl>install/update.zip</romUrl><servers><com.luoha.config.bean.KtvResourceUrl><item>http://192.168.1.199</item></com.luoha.config.bean.KtvResourceUrl></servers><validate>1</validate><sid>90001</sid><controlenabled>1</controlenabled><portapkurl>install/KTVBoxPort.apk</portapkurl><portversion>4</portversion><image__cache>1</image__cache><cash__apk__ip>192.168.70.195</cash__apk__ip><cash__apk__port>25567</cash__apk__port><cash__apk__version>2</cash__apk__version><carapkurl>install/DriveFind.apk</carapkurl><carapkversion>410</carapkversion></root>"""
        #    header = pack("6I", htonl(author), htonl(version), htonl(request), htonl(verify), htonl(len(body)), htonl(device))
        #    msg = header + body
        logging.debug('send header:(%d, %d, %d, %d, %d, %d) to client %s'
            % (author, version, request, verify,
               length, device, self._addr_str))
        logging.debug('send body:(%d:%s) to client %s'
            % (len(msg[24:]), msg[24:], self._addr_str))
        self._stream.write(msg)


    def on_close(self):
        Connection.conns_lock.acquire()
        self._stream.close()
        Connection.clients.remove(self)

        assert self._type in Connection.conns
        assert self._device in  Connection.conns[self._type]
        del Connection.conns[self._type][self._device]

        if self._registed:
            logging.info('%s disconnected from %s' % (self._type_name_map[self._type], self._addr_str))

        Connection.conns_lock.release()


class BoxConnection(Connection):
    box_clients = set()
    def __init__(self, stream, address):
        Connection.__init__(self, stream, address)
        self.set_type(2)
        BoxConnection.box_clients.add(self)
        logging.debug('new box connection # %d from %s' % (len(BoxConnection.box_clients), get_addr_str(address)))
    
    def on_close(self):
        Connection.on_close(self)
        BoxConnection.box_clients.remove(self)
        logging.debug('box connection %s disconnected' % get_addr_str(self._address))


class AppConnection(Connection):
    app_clients = set()
    def __init__(self, stream, address):
        Connection.__init__(self, stream, address)
        self.set_type(1)
        AppConnection.app_clients.add(self)
        logging.debug('new app connection # %d from %s' % (len(AppConnection.app_clients), get_addr_str(address)))

    def on_close(self):
        Connection.on_close(self)
        AppConnection.app_clients.remove(self)
        logging.debug('app connection %s disconnected' % get_addr_str(self._address))


class ERPConnection(Connection):
    erp_clients = set()
    def __init__(self, stream, address):
        Connection.__init__(self, stream, address)
        self.set_type(3)
        ERPConnection.erp_clients.add(self)
        logging.debug('new erp connection # %d from %s' % (len(ERPConnection.erp_clients), get_addr_str(address)))

    def on_close(self):
        Connection.on_close(self)
        ERPConnection.erp_clients.remove(self)
        logging.debug('erp connection %s disconnected' % get_addr_str(self._address))


class InitConnection(Connection):
    init_clients = set()
    def __init__(self, stream, address):
        Connection.__init__(self, stream, address)
        self.set_type(4)
        InitConnection.init_clients.add(self)
        logging.debug('new init connection # %d from %s' % (len(InitConnection.init_clients), get_addr_str(address)))

    def on_close(self):
        Connection.on_close(self)
        InitConnection.init_clients.remove(self)
        logging.debug('init connection %s disconnected' % get_addr_str(self._address))
