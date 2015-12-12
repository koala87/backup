#!/usr/bin/env python2.7
# -*-coding:utf-8 -*-
__author__ = 'yin'

"""
"""
from _socket import ntohl, htonl
import sys
import socket
import logging
import json
from struct import pack, unpack

import tornado.iostream
import tornado.ioloop
import requests
from control import BaseBusiness


# packet header length between route and business
BUSINESS_HEADER_LENGTH = 56
# packet header length between app/box/erp/init and business
CLIENT_HEADER_LENGTH = 24


def init_log(fname, debug):
    logging.basicConfig(
        level=logging.DEBUG,
        format='[%(asctime)s - %(process)-6d - %(threadName)-10s - %(levelname)-8s] %(message)s',
        datefmt='%a, %d %b %Y %H:%M:%S',
        filename=fname,
        filemode='w')

    sh = logging.StreamHandler()
    if debug:
        sh.setLevel(logging.DEBUG)
    else:
        sh.setLevel(logging.INFO)
    formatter = logging.Formatter('%(levelname)-8s %(message)s')
    sh.setFormatter(formatter)
    logging.getLogger('').addHandler(sh)


def get_default_log():
    """return default log name"""
    import os

    name = os.path.basename(sys.argv[0])
    pos = name.rfind('.')
    if pos != -1:
        name = name[:pos]

    name = name.split("/")[-1]

    root_path = os.path.dirname(os.path.abspath(__file__))
    root_path = os.path.abspath(os.path.join(root_path, '../../Log/%s.log' % name))

    return root_path


def register_options():
    from optparse import OptionParser

    parser = OptionParser()
    parser.add_option("-i", "--host", dest="host",
                      default="192.168.1.199", help="specify host, default is 192.168.1.199")
    parser.add_option("-p", "--port", dest="port",
                      type="int",
                      default=6666, help="specify port, default is 3050")
    parser.add_option("-n", "--num", dest="num",
                      type="int",
                      default=1, help="specify threads num, default is 10")
    parser.add_option("-l", "--log", dest="log",
                      default=get_default_log(), help="specify log name")
    parser.add_option("-d", "--debug", dest="debug",
                      action='store_true',
                      default=False, help="enable debug")

    (options, args) = parser.parse_args()
    return options


class ForWard(BaseBusiness):
    type_map = {
        'app': 1,
        'box': 2,
        'erp': 3,
        'init': 4,
    }

    """
    """
    def do_something(self):
        if 11 == self._request:
            self._device_id = self._device
        elif 10007 == self._request:
            pass
            try:
                body1 = self._body[self._client_header_length:]
                body_json = json.loads(body1)
                box_id = body_json["serialid"]
                re7 = requests.get("http://192.168.1.199:4201/box/info/by/ip/", params={'ip': self._ip})
                js7 = re7.json()
                ver_code = js7["result"]["vercode"]
                ret_val = {}
                ret_val["appServerUrl"] = ""
                ret_val["result"] = {"code": ver_code, "port": 3050}
                ret_val["status"] = 0
                body1 = json.dumps(ret_val)

                logging.debug(" ret json: %s   ret val %s: ", js7, ret_val)

                parts = unpack('6I', self._body[:self._client_header_length])
                parts = [ntohl(x) for x in parts]
                parts[4] = len(body1)
                info_header = pack("6I", htonl(parts[0]), htonl(parts[1]), htonl(parts[2]), htonl(parts[3]), htonl(parts[4]), htonl(parts[5]))

                packet_body = info_header + body1
                self._body = packet_body
                self._length = len(packet_body)
            except Exception, e:
                logging.debug(e)

        elif 90001 == self._request:
            """ app -> box """
            try:
                body1 = self._body[self._client_header_length:]
                body_json = json.loads(body1)
                touid = body_json["touid"]

                self._device_type = self.type_map["box"]
                re5 = requests.get("http://192.168.1.199:4201/box/info/by/vercode/", params={'vercode': touid})
                js = re5.json()
                self._device_id = js["result"]["boxid"]
                self._device = self._device_id
            except Exception, e:
                logging.debug(e)
        elif 90005 == self._request:
            """ app -> box """
            try:
                body1 = self._body[self._client_header_length:]
                body_json = json.loads(body1)
                touid = body_json["touid"]

                self._device_type = self.type_map["box"]
                re5 = requests.get("http://192.168.1.199:4201/box/info/by/vercode/", params={'vercode': touid})
                js = re5.json()
                self._device_id = js["result"]["boxid"]
                self._device = self._device_id
            except Exception, e:
                logging.debug(e)
        elif 90002 == self._request:
            """box -> app. send msg of switch video"""
            self._device_type = self.type_map["app"]
        elif 90003 == self._request:
            """ app -> app  || box -> apps """
            self.fun90003()
        elif 90011 == self._request:
            # self.fun90011()
            """ box -> app """
            self._device_type = self.type_map["app"]
        elif 90012 == self._request:
            # self.fun90012()
            """ app -> box"""
            self._device_type = self.type_map["box"]
        elif 90013 == self._request:
            self.fun90013()
        else:
            self.error_requery()

    """return data
    """
    def send_packet_back(self):
        from socket import htonl

        ip = htonl(unpack('I', socket.inet_aton(self._ip))[0])
        header = pack("2I32sdII", htonl(self._device_type),
                      htonl(self._device_id), self._md5, self._timestamp,
                      htonl(len(self._body)), ip)

        parts = unpack('6I', self._body[:self._client_header_length])
        body1 = self._body[self._client_header_length:]
        parts = [ntohl(x) for x in parts]
        parts[5] = self._device
        parts[4] = len(body1)
        send_header = pack("6I", htonl(parts[0]), htonl(parts[1]), htonl(parts[2]), htonl(parts[3]), htonl(parts[4]), htonl(parts[5]))
                 
        msg = header + send_header + body1
        self._stream.write(msg)
        
        if parts[2] != 11: 
            logging.debug('send packet back: header(%d, %d, %s, %.4f, %d, %s)'
                          % (self._device_type, self._device_id, self._md5,
                             self._timestamp, len(self._body), self._ip))
            logging.debug('send body: header(%d, %d, %d, %d, %d, %d) body:%s'
                % (parts[0], parts[1], parts[2], parts[3], parts[4], parts[5],body1))
        
        self._stream.read_bytes(BUSINESS_HEADER_LENGTH, self.read_packet_header)
        pass

    """
    """
    def fun90001(self):
            pass

    def fun90003(self):

        if self._device_type == self.type_map["app"]:
            """ app request res url. app to app"""
            self._device_type = self.type_map["app"]

            ret_dic = {}
            # try:
            #     re = requests.get("url", params="??")
            #     js = re.json()
            #     ret_dic["status"] = 0
            #     ret_dic["server"] = js["server"]
            #     ret_dic["infoserver"] = js["infoserver"]
            #     ret_dic["id"] = js["id"]
            # except Exception, e:
            #     ret_dic["status"] = 1
            #     ret_dic["error"] = e
            #
            ret_dic['status'] = 0
            ret_dic['server'] = "http://192.168.1.199"
            ret_dic['id'] = 1
            ret_dic['infoserver'] = "http://192.168.1.199"
            js_dic = json.dumps(ret_dic)
            self._length = len(js_dic)
           
            try: 
                body1 = self._body[self._client_header_length:]
                logging.debug("body1  %s" % body1)
                body_json = json.loads(body1)
                touid = body_json["touid"]
                logging.debug("touid %s " % touid)
                re3 = requests.get("http://192.168.1.199:4201/box/info/by/vercode/", params={'vercode': touid})
                js3 = re3.json()
                self._device = js3["result"]["boxid"]
            except Exception, e:
                logging.debug(e)

            print 'header ', self._author, self._version, self._request, self._verify, self._length, self._device
            header = pack("6I", htonl(self._author), htonl(self._version), htonl(self._request),
                          htonl(self._verify), htonl(self._length), htonl(self._device))
            self._body = header + json.dumps(js_dic)

        elif self._device_type == self.type_map["box"]:
            """ send apps. box to app  """
            self._device_type = self.type_map["app"]
            self._request = 90002

            body1 = self._body[self._client_header_length:]
            js = json.loads(body1)

            t_uid_s = [x for x in js["touids"]]
            for to_uid in t_uid_s:
                body_app = {"touid": to_uid, "message": js["message"], "type": js["type"]}
                body_js = json.dumps(body_app)
                self._length = len(body_js)

                header = pack("6I", htonl(self._author), htonl(self._version), htonl(self._request),
                              htonl(self._verify), htonl(self._length), htonl(self._device))
                self._body = header + body_js

                logging.debug('read body: header(%d, %d, %d, %d, %d, %d) body:%s'
                              % (self._author, self._version, self._request,
                                 self._verify, self._length, self._device, body_js))

    def fun90011(self):
        pass

    def fun90012(self):
        pass

    def fun90013(selfs):
        pass

    def error_requery(self):
        pass


class ProcessData():

    def __init__(self, forward):
        self.fd = forward

if __name__ == '__main__':

    opts = register_options()
    init_log(opts.log, opts.debug)
    logging.info('start %d threads to server %s:%d ...' % (opts.num, opts.host, opts.port))

    for i in xrange(opts.num):
        fw = ForWard(opts.host, opts.port)

    tornado.ioloop.IOLoop.current().start()

    logging.info('stop ...')
