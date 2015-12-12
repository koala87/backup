#!/usr/bin/env python2.7
# -*-coding:utf-8 -*-
__author__ = 'yin'

"""
"""
from _socket import ntohl, htonl
import os
import sys
import socket
import logging
import json
from struct import pack, unpack

import tornado.iostream
import tornado.ioloop
import requests
ROOT = os.path.dirname(os.path.abspath(__file__))
sys.path.append(os.path.join(ROOT, '../generic'))
from business import Business
from utility import BUSINESS_HEADER_LENGTH, BUSINESS_FEEDBACK_HEADER_LENGTH, CLIENT_HEADER_LENGTH

# packet header length between route and business  BUSINESS_HEADER_LENGTH = 56
# packet header length between app/box/erp/init and business  CLIENT_HEADER_LENGTH = 24

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
                      default=6666, help="specify port, default is 6666")
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


class ForWard(Business):

    def __init__(self, ip, port):
        self._type = -1
        self._status = -1
        self._error = ''

        Business.__init__(self, 'forward', ip, port)

    type_map = {
        'app': 1,
        'box': 2,
        'erp': 3,
        'init': 4,
    }

    """process route data
    """
    def process(self):
        if 11 == self._request:
            self._device_id = self._device
            self.send_packet_back(body=self._body)
        elif 10007 == self._request:
            """ Scan a QR Code info
            """
            pass
            try:
                body1 = self._body  #[self._client_header_length:]
                body_json = json.loads(body1)
                print body_json["serialid"]
                re7 = requests.get("http://192.168.1.199:4201/box/info/by/ip/", params={'ip': self._ip})
                js7 = re7.json()
                ver_code = js7["result"]["vercode"]
                ret_val = {}
                ret_val["appServerUrl"] = ""
                ret_val["result"] = {"code": ver_code, "port": 3050}
                ret_val["status"] = 0
                body1 = json.dumps(ret_val)
                self._length = len(body1)
                print " ret json: %s   ret val %s: ", js7, ret_val

                self.send_packet_back(body=body1)
                logging.debug("************3")
            except Exception, e:
                logging.debug(e)

        elif 40001 == self._request:
            pass
            #logging.debug('read body: header(%d, %d, %d, %d, %d, %d) body:%s'
            #              % (self._author, self._version, self._request,
            #                 self._verify, self._length, self._device, self._body))

        elif 90001 == self._request:
            """ app send server number 90001
                server send box number 900012
            """
            to_uid = ''
            try:
                body1 = self._body #[self._client_header_length:]
                body_json = json.loads(body1)
                to_uid = body_json["touid"]
                self._type = int(body_json.get("type"))
                print to_uid
                self._device_type = self.type_map["box"]
                re1 = requests.get("http://192.168.1.199:4201/box/info/by/vercode/", params={'vercode': to_uid})
                js1 = re1.json()
                self._device_id = js1["result"]["boxid"]
                #self._device = self._device_id
                self._request = 90012

                self.send_packet_back(body=body1)
                self._status = 0
            except Exception, e:
                error = e
                logging.debug(e)
                self._status = 1

            """" return app  """
            self._device_type = self.type_map["app"]
            self._request = 90001
            self._device_id = self._device # int(to_uid)

            ret_app = {"status": self._status, "type": self._type, "uid": to_uid}
            if self._status != 0:
                ret_app["error"] = error

            self.send_packet_back(body=json.dumps(ret_app))

        elif 90005 == self._request:
            """ app -> box """
            try:
                body1 = self._body # [self._client_header_length:]
                body_json = json.loads(body1)
                to_uid = body_json["touid"]

                self._device_type = self.type_map["box"]
                re5 = requests.get("http://192.168.1.199:4201/box/info/by/vercode/", params={'vercode': to_uid})
                js = re5.json()

                self._device_id = js["result"]["boxid"]
                self._device = self._device_id
                self._request = 90012

                self.send_packet_back(body=body1)
            except Exception, e:
                logging.debug(e)
        elif 90002 == self._request:
            """box -> app. send msg of switch video"""
            self._device_type = self.type_map["app"]
        elif 90003 == self._request:
            """ app -> app  || box -> apps """
            self.fun90003()
        elif 90013 == self._request:
            self.fun90013()
        else:
            self.send_packet_back(body=self._body)

    """send data to route
    """

    def send_packet_back(self, body="error"):
        self.send(body)
        #from socket import htonl

        #header1 = pack("6I", htonl(self._author),
        #    htonl(self._version), htonl(self._request),
        #    htonl(self._verify), htonl(self._length),
        #    htonl(self._device))
        #if self._request != 11:
        #    logging.debug('send packet back: body(%d, %d, %d, %d, %d, %d) %d:%s'
        #                  % (self._author, self._version, self._request, self._verify,
        #                     self._length, self._device, len(body), body))

        #body += header1

        #ip = htonl(unpack('I', socket.inet_aton(self._ip))[0])
        #header = pack("2I32sdII", htonl(self._device_type),
        #    htonl(self._device_id), self._md5, self._timestamp,
        #    htonl(len(body)), ip)
        #msg = header + body
        #try:
        #    self._stream.write(msg)
        #except Exception, e:
        #    raise e
        #if self._request != 11:
        #    logging.debug('send route back: header(%d, %d, %s, %.4f, %d, %s)'
        #                  % (self._device_type, self._device_id, self._md5,
        #                     self._timestamp, len(body), self._ip))

        #old code
        # ip = htonl(unpack('I', socket.inet_aton(self._ip))[0])
        # header = pack("2I32sdII", htonl(self._device_type),
        #               htonl(self._device_id), self._md5, self._timestamp,
        #               htonl(len(self._body)), ip)
        #
        # parts = unpack('6I', self._body[:self._client_header_length])
        # body1 = self._body[self._client_header_length:]
        # parts = [ntohl(x) for x in parts]
        # parts[5] = self._device
        # parts[4] = len(body1)
        # print "request :  ::: ", self._request
        # logging.debug(self._request)
        # send_header = pack("6I", htonl(self._author), htonl(self._version), htonl(self._request), htonl(self._verify), htonl(len(body1)),
        #                    htonl(self._device))
        #
        # msg = header + send_header + body1
        # self._stream.write(msg)

        # if parts[2] != 11:
        #     logging.debug('send packet back: header(%d, %d, %s, %.4f, %d, %s)'
        #                   % (self._device_type, self._device_id, self._md5,
        #                      self._timestamp, len(self._body), self._ip))
        #     logging.debug('send body: header(%d, %d, %d, %d, %d, %d) body:%s'
        #                   % (self._author, self._version, self._request, self._verify, len(body1), self._device, body1))

    """
    """

    def fun90003(self):

        if self._device_type == self.type_map["app"]:
            """ app request res url. app to app"""
            self._device_type = self.type_map["app"]

            ret_dic = {}
            try:
                re = requests.get("http://192.168.1.199:4201/config/resource/", params={'names': "node_list,ip"})
                js = re.json()
                items = js["result"]["items"]

                for item in items:
                    if item.get("name") == "node_list":
                        node_lists = item.get("value")
                        nodes = json.loads(node_lists)
                        res = nodes["node_list"]
                        ret_dic["id"] = res[0].get("id")
                        ret_dic["infoserver"] = res[0].get("url")

                    if item.get("name") == "ip":
                        ret_dic["server"] = "http://" +  item.get("value")
                ret_dic["status"] = 0

                logging.debug("ret_dic %s" % ret_dic)
                js_dic93 = json.dumps(ret_dic)
                self._length = len(js_dic93)
                self.send_packet_back(body=js_dic93)
            except Exception, e:
                ret_dic["status"] = 1
                ret_dic["error"] = e
                logging.debug("exception: %s", e)

        elif self._device_type == self.type_map["box"]:
            """ send apps. box to app  """
            self._device_type = self.type_map["app"]
            self._request = 90011

            body1 = self._body # [self._client_header_length:]
            js = json.loads(body1)

            t_uid_s = [x for x in js["touids"]]
            for to_uid in t_uid_s:
                body_app = {"touid": to_uid, "message": js["message"], "type": js["type"]}
                body_js = json.dumps(body_app)
                self._length = len(body_js)
                self.send_packet_back(body_js)

    def fun90011(self):
        pass

    def fun90013(selfs):
        pass

    def error_requery(self):
        pass

    def on_close(self):
        Business.on_close(self)

if __name__ == '__main__':

    opts = register_options()
    init_log(opts.log, opts.debug)
    logging.info('start %d threads to server %s:%d ...' % (opts.num, opts.host, opts.port))

    for i in xrange(opts.num):
        fw = ForWard(opts.host, opts.port)

    tornado.ioloop.IOLoop.current().start()

    logging.info('stop ...')
