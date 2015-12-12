#!/usr/bin/env python2.7
# !coding=utf-8

"""control module"""

__author__ = 'Yingqi Jin <jinyingqi@luoha.com>'

import os
import sys
import signal
import logging

from tornado.ioloop import IOLoop


# add generic dir into sys path
# ROOT = os.path.dirname(os.path.abspath(__file__))
# sys.path.append(os.path.join(ROOT, '../sunny'))
from utility import init_log, get_default_log, get_ip
from business import Business
from boa import Boa


def sig_handler(sig, frame):
    IOLoop.current().stop()
    logging.info('stop ...')


class Hancock(Business):
    """
    这里是继承的金瑛棋的Business类，主要是通过process函数处理信息。 @TT
    之所以初始化好多Business类的一些属性，是为了传给下面Boa调用
    只需要把类本身，也就是self传给Boa类，其他的操作都在Boa类里面完成
    比如：消息体的解析，封装，发送等等，
    发消息是Business的send发出去的，但是要在Boa里面调用，所以要把self传给Boa
    我在这里就做了两件事：
    1： 定义一些初始化的属性
    2： 重写process函数，把self传给Boa，好让Boa可以调用上一件事定义的属性
    """
    control_clients = set()

    def __init__(self, ip, port):
        Hancock.control_clients.add(self)
        Business.__init__(self, 'control', ip, port)
        # 初始化一些基础信息，供下面调用
        self.request = None
        self.device_type = None
        self.device_id = None
        self.device = None
        self.version = None
        self.verify = None
        self.author = None
        self.body = None
        self.length = None

    # overwrite process method
    def process(self):
        """
        这里直接调用Boa类，用法在Boa类里面做详细介绍。  @TT
        """
        self.author = self.author  # 验证部分
        self.version = self.version  # 版本号
        self.request = self.request  # 请求功能
        self.verify = self.verify  # 识别码
        self.length = self._length  # 数据长度
        self.device = self.device  # 设备ID
        self.device_id = self.device_id
        self.device_type = self.device_type
        self.body = self.body
        # logging.info(self.author)
        # logging.info(self.version)
        # logging.info(self.verify)
        # logging.info(self.request)
        # logging.info(self.device)
        # logging.info(self.device_type)
        # logging.info(self.device_id)
        Boa(self)
        logging.debug('#in control process ...#')

    def on_close(self):
        Hancock.control_clients.remove(self)
        Business.on_close(self)


def register_options():
    from optparse import OptionParser
    parser = OptionParser()
    parser.add_option("-i", "--host", dest="host",
                      default='192.168.1.199', help="specify host, default is local ip")
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


if __name__ == '__main__':

    opts = register_options()

    init_log(opts.log, opts.debug)

    signal.signal(signal.SIGTERM, sig_handler)
    signal.signal(signal.SIGINT, sig_handler)

    logging.info('start %d connections to server %s:%d ...' % (opts.num, opts.host, opts.port))

    for i in xrange(opts.num):
        Hancock(opts.host, opts.port)

    IOLoop.current().start()

    logging.info('stop ...')
