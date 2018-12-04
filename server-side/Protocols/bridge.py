#!/usr/bin/python

import socket
import struct
import wrapper
import mysql.connector
from definitions import CONST
import ../config.CONST as DB_CONST

class Task:
    pass

class Item: 
    # Notes: if content is a string must be convert to ascii before
    #        calling of __init__().
    def __init__(self, type_, op_. prop_, taskID_, flags_, content_):
        self.__type = type_
        self.__op = op_
        self.__prop = prop_
        self.__taskID = taskID_
        self.__flags = flags_
        self.__content = content_
        self.__length = 0
    
    def flags(self):
        return self.__flags 
    def setFlags(self, flags_):
        self.__flags = flags_
        return false

    def taskID(self):
        return self.__taskID
    def setTaskID(self, taskID_):
        self.__taskID = taskID_
        return true

    def type(self):
        return self.__type
    def setType(self, type_):
        check = type_ != CONST.BRIDGE_TYPE_REQUEST &&
                type_ != CONST.BRIDGE_TYPE_INFO &&
                type_ != CONST.BRIDGE_TYPE_MANAGEMENT
        if check:
            return false
        else:
            self.__type = type_
            return true
    
    def op(self):
        return self.__op
    def setOp(self, op_):
        check = op_ != CONST.BRIDGE_OP_ENABLE &&
                op_ != CONST.BRIDGE_OP_DISABLE &&
                op_ != CONST.BRIDGE_OP_SET
        if check:
            return false
        else:
            self.__op = op_
            return true

    def content(self):
        return self.__content
    def setContent(self, content_):
        self.content = content_

    def message(self):
        return struct.pack(CONST.BRIDGE_FRAME_FORMAT_PACK % len(self.__content),
                self.__type, self.__op, self.__prop, self.__taskID, 
                self.__flags, self.__length, self.__content)       

    def length():
        return self.__length

class BridgeEntry:  
    def __init__(self, address_, port_):
        # Socket initialization
        self.__address = address_    
        self.__port = port_
        self.__socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM) 
        self.__socket.bind(address_, port_)
        
        # Task info
        # Format of Tasktbl
        # | TID | 
        self.__taskTbl = []

        # Database connection initialization
        try:
            self.__dbEntry = mysql.connector.connect(user = DB_CONST.DB_USERNAME, 
                    password = DB_CONST.DB_PASSWORD, host = DB_CONST.DB_ADDR, 
                    database = DB_CONST.DB_DATABASE) 
        except:
            print("BridgeEntry Init: mysql.connector.connect failed\n")
            self.__dbEntry.close()
    
    def accept():
         
    
    # Protocol data unit
    def Bridge_send(self, frame, flags):
        return wrapper.socket_send_wrapper(self.sock, frame, flags)
    def Bridge_recv(self, buffer_, flags):
        return wrapper.socket_recv_wrapper(self.sock, frame, 
                CONST.BRIDGE_MAX_SIZE_OF_BUFFER, flags)
    def Bridge_recv_header(self, frameHeader, flags):
        return wrapper.socket_recv_wrapper(self.sock, frameHeader, 
                CONST.BRIDGE_FRAME_HEADER_LEN, flags)
    def Bridge_transfer(data): 
        pass 
    


