# -*- coding: utf-8 -*-

"""
这里是控制的核心代码。在这里解析并发送数据。 @TT
"""

__author__ = 'TT'

from xml.etree import ElementTree
from socket import htonl
from struct import pack
from hashlib import md5
import json
import requests
import time
import logging

# 定义给API route的device_type  @TT
device_type_dict = dict(
    # 1为app, 2为机顶盒，3为erp，4为init
    app=1, box=2, erp=3, init=4,
)
# 定义ERP每个功能的 device_id  @TT
erp_device_id_dict = dict(
    receptionist=1001,  # 前台
    manager=1002,  # 管理程序
    supermarket=1003,  # 超市
    kitchen=1004,  # 厨房
)
# 每一个功能好对应一个函数名，好通过函数名获取功能号  @TT
func_num = dict(
    box_switch_room=10018,
    erp_switch_room=20001,
    erp_init_room=30001,
    notice_box=10029,
)
# 封装xml的消息体， 每一个功能号的消息体都不一样  @TT
# 用的时候直接 format 就好
MSG = {
    10018: '<root><room_status>{}</room_status><keep>{}</keep></root>',
    20001: '<root><status>{}</status><confirm>{}</confirm></root>',
    30001: '<root><confirm>{}</confirm></root>',
}


class Boa(object):
    """
    控制的类，上层在调用的时候，只要把Hancock类本身传进来就ok了，
    其实Hancock是继承了Business的
    这里的process方法会直接在Boa类init的时候就调用
    调用process的时候，会根据上面获取的功能号，找到对应的函数去处理  @TT
    """

    def __init__(self, stream):
        """
        self.author = self.author  # 验证部分
        self.version = self.version  # 版本号
        self.request = self.request  # 请求功能
        self.verify = self.verify  # 识别码
        self.length = self._length  # 数据长度
        self.device = self.device  # 设备ID
        """
        self._stream = stream
        # self._author = self._stream.author  # 验证部分
        # self._version = self._stream.version
        # self._request = self._stream.request  # 请求功能
        # self._verify = self._stream.verify
        # self._length = self._stream.length
        # self._device = self._stream.device  # 设备ID
        # self.body = self._stream.body
        # self._device_id = self._stream.device_id
        # self._device_type = self._stream.device_type
        self._md5 = None
        # 这里定义一个dict，key是功能号，value是处理该功能号的函数  @TT
        self.boa = {
            10018: self.switch_room,
            20001: self.switch_room,
            30001: self.erp_init_room,
        }
        self.process()

    def get_text_from_xml(self, xml, *args, **kwargs):
        """
        返回一个generator object
        传args的时候，如果xml里面有对应的值就返回，没有的话返回None
        传kwargs的时候，如果xml里面有对应的key的值就返回，没有的话返回对应的key的value  @TT
        """
        for arg in args:
            try:
                yield xml.find(arg).text
            except AttributeError:
                yield None
        for k, v in kwargs.items():
            try:
                yield xml.find(k).text
            except AttributeError:
                yield v

    def get_vercode(self):
        """"""
        a = int(time.time())
        b = str(a)
        logging.info(b)
        return b[2:]
        return str(int(time.time()))[2:]

    def process(self):
        """
        这里根据功能号，调用对应的函数做处理  @TT
        """
        try:
            self.boa.get(int(self._stream.request))()
        except Exception:
            logging.error('boa process error', exc_info=True)

    def send(self, msg):
        """
        把需要send出去的body叫上header再send出去  @TT
        """
        self._md5 = md5(msg).hexdigest()
        # logging.info(self.author, self.version, self.request, self.verify, self.device)
        header = pack("6I", htonl(self._stream.author), htonl(self._stream.version), htonl(self._stream.request),
                      htonl(self._stream.verify), htonl(len(msg)), htonl(self._stream.device))
        body = header + msg
        print(len(msg))
        print('2222')
        logging.info(body)
        self._stream.send(body)
        print(self._stream.author, self._stream.version, self._stream.request, self._stream.verify, len(msg), self._stream.device)

    def switch_room(self):
        """
        开关包，ERP开关包
        功能号：10018（box）/20001（erp）
        """
        root = ElementTree.fromstring(self._stream.body)
        kwargs = dict(boxid=0, room=0, keep=0)
        res = self.get_text_from_xml(root, **kwargs)
        box_id = res.next()
        room = res.next()
        keep = res.next()
        confirm = 1
        # 下面这行代码看得懂么，不懂就要问，问也不告诉你。坑啊！
        # 虽然下面这行代码很吊，但是为了需求，也只能注释掉了，可惜啊！！
        # msg = MSG.get(self.request).format(*map(str, res))
        # 通知ERP确认
        self._stream.device_id = erp_device_id_dict.get('receptionist')
        logging.info('device_id: {}'.format(self._stream.device_id))
        logging.info('device_type: {}'.format(self._stream.device_type))

        msg = MSG.get(func_num.get('erp_switch_room')).format(room, confirm)
        self.send(msg)

        print(6666)
        # 通知机顶盒开关包
        print(4444)
        self._stream.device_id = self._stream.device = int(box_id)
        logging.info('device_id: {}'.format(self._stream.device_id))
        self._stream.device_type = device_type_dict.get('box')
        self._stream.request = func_num.get('box_switch_room')
        # print(func_num.get('box_switch_room'))
        # print(self._stream.request)
        # print(3333)
        logging.info('device_type: {}'.format(self._stream.device_type))
        msg = MSG.get(func_num.get('box_switch_room')).format(room, keep)
        self.send(msg)
        # TODO 先查是否开/关包，状态一致，不修改
        vercode = ''
        if room in [1, '1', u'1']:
            vercode = self.get_vercode()
        logging.info(vercode)
        url = 'http://192.168.1.199:4201/box/info/vercode/update'
        res = requests.post(url, dict(boxid=box_id, vercode=vercode))
        url = 'http://192.168.1.199:4201/box/status/upsert'
        res = requests.post(url, dict(boxid=box_id, status=room))
        print(5555)

    def erp_init_room(self):
        """
        功能号：30001
        ERP 告诉我们所有机顶盒的初始化的值
        包括 boxid，roomno，roomname，ip，type
        每个都是啥意思，我是猜的，你要不要也猜猜。  @TT
        eg：
收到的body
<root>
    <shopname>北京洛哈技术有限公司</shopname>
    <ktvinfos>
        <item>
            <boxid>1</boxid>
            <roomno>16</roomno>
            <roomname>16</roomname>
            <ip>192.168.70.16</ip>
            <type>1</type>
        </item>
        ...
    </ktvinfos>
</root>
返回的body
<root><confirm>1</confirm></root>
        """
        confirm = 1
        msg = MSG.get(func_num.get('erp_init_room')).format(confirm)
        self.send(msg)
        url = 'http://192.168.1.199:4201/box/info/by/status'
        res = requests.get(url).json()
        addr_id_list = res.get('result')
        root = ElementTree.fromstring(self._stream.body)
        args = ['shopname']
        res = self.get_text_from_xml(root, *args)
        shop_name = res.next()
        logging.info(shop_name)
        box_info_key = ['boxid', 'roomno', 'roomname', 'ip', 'type']
        data = {}
        for i in root.find('ktvinfos'):
            # logging.info(i.find('ip').text)
            box_info = [i.find(k).text for k in box_info_key]
            # box_info_list.append(dict(zip(box_info_key, box_info)))
            data.update({box_info[0]: box_info})
        info_data = json.dumps(data)
        logging.info(info_data)
        for k, v in data.items():
            msg = dict(zip(box_info_key, v))
            oldid = addr_id_list.get(msg.get('ip', ''), None)
            if oldid is None:
                continue
            msg.update(dict(valid=1))
            msg.update(dict(shopname=shop_name))
            msg.pop('ip')
            self.notice_box(oldid, msg)
        post_data = dict(box_info=info_data, shop_name=shop_name)
        url = 'http://192.168.1.199:4201/box/info/add'
        post_res = requests.post(url, post_data)
        url = 'http://192.168.1.199:4201/config/resource/upsert/'
        post_res = requests.post(url, dict(name='shopname', value=shop_name, detail='shop name'))

    def notice_box(self, boxid, data):
        """"""
        #
        msg = json.dumps(data)
        self._stream.device_id = self._stream.device = int(boxid)
        self._stream.device_type = device_type_dict.get('box')
        self._stream.request = func_num.get('notice_box')
        self.send(msg)
        logging.info(msg)
