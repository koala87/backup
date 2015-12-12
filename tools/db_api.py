#!/usr/bin/env python2.7
# -*-coding:utf-8 -*-
# 
# Copyright (C) 2012-2015 LuoHa TECH Co., Ltd. All rights reserved.
# Created on 2015-12-09, by rory
# 
# 

__author__ = 'rory'


import requests

import argparse

parse = argparse.ArgumentParser(description='Test client for the db api server')
parse.add_argument('-host', default='http://127.0.0.1', type=str, help='The hose for the db api server')
parse.add_argument('-port', default=4201, type=int, help='The port for the db api server')
parse.add_argument('-uri', default='/test/', help='The uri for the db api server, default:/test/')
parse.add_argument('-method', default='get', help='The request method for the db api server, default:get')

args = parse.parse_args()

try:
    res = getattr(requests, args.method)('{}:{}{}'.format(args.host, args.port, args.uri)).json()
except Exception, e:
    print u'失败了, --> {}'.format(e)
else:
    import json
    print u'成功了，结果为:\n'
    print json.dumps(res, ensure_ascii=False, indent=4)
