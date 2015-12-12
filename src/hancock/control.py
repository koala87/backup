# -*- coding: utf-8 -*-

"""
Description
"""

__author__ = 'TT'

func_num_dict = dict(
    switch_room=10018,
    transfer_room=10028,

)


class Control(object):
    """
    开包：
        type: 2, id: boxid
    """

    def __init__(self):
        pass

    @staticmethod
    def switch_room(act=1, keep=0):
        """"""
        return '<root><room_status>{}</room_status><keep>{}</keep></root>'.format(act, keep)
