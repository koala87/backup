#!/usr/bin/env python2.7
# -*-coding:utf-8 -*-
#
# Copyright (C) 2012-2015 LuoHa TECH Co., Ltd. All rights reserved.
# Created on 2015-11-19, by rory
#
#

__author__ = 'rory'

import socket
import hashlib
import struct
import random
import os

# 可选项TCP服务器 HOST:PORT
import argparse

parse = argparse.ArgumentParser(description='Test client for local tcp log server')
parse.add_argument('-host', default='127.0.0.1', type=str, help='The hose for the local tcp log server')
parse.add_argument('-port', default=45623, type=int, help='The port for the local tcp log server')
parse.add_argument('-file', default=os.path.join(os.path.dirname(__file__), '192.168.1.24.zip'),
                   help='The file for yun upload local tcp log server')

args = parse.parse_args()


def test_client():
    # 新建socket
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.connect((args.host, args.port))

    md5 = hashlib.md5()
    body = ''
    with open(args.file, 'r+') as log_file:
        for f in log_file:
            body += f
            md5.update(f)

    log_length = len(body)

    # 构造发送包
    package = struct.pack('I32s16sI{}s'.format(log_length), socket.htonl(random.choice([101, 104])),
                          '192.168.1.24.zip',
                          md5.hexdigest()[:16],
                          socket.htonl(log_length), body)

    s.sendall(package)
    s.close()


if __name__ == '__main__':
    if not os.path.isfile(args.file):
        print u'无效的文件'
    else:
        import threading
        import logging

        logging.basicConfig(level=logging.DEBUG,
                            format='(%(threadName)-10s) %(message)s')

        # 多线程测试发包请求
        for i in range(5):
            t = threading.Thread(target=test_client)
            logging.debug('starting %s', t.getName())
            t.start()
