#!/usr/bin/python
#coding=utf8

import httplib

http = httplib.HTTPConnection('127.0.0.1')
http.request('GET','/index.php')
res = http.getresponse()
if res.status == 200:
	print(res.read())
else:
	print('PHP server exceotion!')
