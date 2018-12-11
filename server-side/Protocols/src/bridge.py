#!/usr/bin/python

import socket
import struct
import wrapper
import time
from definitions import CONST

class TaskManage:
    def __init__(self, taskID):
        # Task states 
        self.taskID = taskID
        self.lastSeekDistance = 0  
        self.jobStatus = CONST.BRIDGE_TASK_STATUS_PENDING   
        self.descriptor = 0

        # Task callback
        self.__rollback = 0
        self.__retrive = 0
        self.__reset = 0

    def rollbackRtnSet(self, rtn):
        self.__rollback = rtn

    def retriveRtnSet(self, rtn):
        self.__retrive = rtn

    def resetRtnSet(self, rtn):
        self.__reset = rtn

    # Rollback to last success state 
    def rollback(self):
        return self.__rollback(self)

    def retrive(self, size):
        return self.__retrive(self, size)

    def reset(self):
        return self.__reset(self)

class BridgeMsg: 
    # Notes: if content is a string must be convert to ascii before
    #        calling of __init__().
    def __init__(self, type_, op_, prop_, taskID_, flags_, content_ = b""):
        self.__type = type_
        self.__op = op_
        self.__prop = prop_
        self.__taskID = taskID_
        self.__flags = flags_
        self.__content = content_
        self.__length = CONST.BRIDGE_FRAME_HEADER_LEN + len(content_) 
    
    def flags(self):
        return self.__flags 
    def setFlags(self, flags_):
        self.__flags = flags_
        return True

    def taskID(self):
        return self.__taskID
    def setTaskID(self, taskID_):
        self.__taskID = taskID_
        return True

    def type(self):
        return self.__type
    def setType(self, type_):
        check = type_ != CONST.BRIDGE_TYPE_REQUEST and \
                type_ != CONST.BRIDGE_TYPE_INFO and \
                type_ != CONST.BRIDGE_TYPE_MANAGEMENT
        if check:
            return False
        else:
            self.__type = type_
            return True
    
    def op(self):
        return self.__op
    def setOp(self, op_):
        check = op_ != CONST.BRIDGE_OP_ENABLE and \
                op_ != CONST.BRIDGE_OP_DISABLE and \
                op_ != CONST.BRIDGE_OP_SET
        if check:
            return False
        else:
            self.__op = op_
            return True

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

    # Task info
    # Format of Tasktbl
    # | TID | JobTransRtn | ContinueRtn | Info
    __taskTbl = {}

    def __init__(self, sock):
        # Socket initialization
        self.__socket = sock 

    def __newTask(self, taskID):
        BridgeEntry.__taskTbl[str(taskID)] = TaskManage(taskID)

    def __rmTask(self, taskID):
        del BridgeEntry.__taskTbl[str(taskID)]

    def taskTaskManageGet(self, taskID):
        return BridgeEntry.__taskTbl[str(taskID)] 

    def __requestProcessing(self, frame):
        taskID = wrapper.BridgeTaskIDField(frame) 
        flags = wrapper.BridgeFlagField(frame)  
        content = wrapper.BridgeContentField(frame)
        
        print("In __requestProcessing")

        taskID_str = str(taskID)
        msg = BridgeMsg(CONST.BRIDGE_TYPE_REPLY, 0, 0, taskID_str, CONST.BRIDGE_FLAG_ERROR)

        if flags & CONST.BRIDGE_FLAG_NOTIFY:
            # A new task is arrived.
            msg.setFlags(CONST.BRIDGE_FLAG_ACCEPT)

            # For the consistency of both side we give reply 
            # first then do job.
            if self.Bridge_send("12345") == False:
                return False
            
            self.__newTask(taskID) 
            return (taskID_str, content)

        elif flags & CONST.BRIDGE_FLAG_RECOVER:
            # Recover request
 
            # First to check is the TaskID exist.
            if taskID_str in BridgeEntry.__taskTbl:
                msg.setFlags(CONST.BRIDGE_FLAG_RECOVER)
                self.Bridge_send(msg.message())
            else:
                msg.setFlags(CONST.BRIDGE_FLAG_ERROR)
                self.Bridge_send(msg.message())
                return False
            taskMng = BridgeEntry.__taskTbl[taskID_str]
            
            msg.setType(CONST.BRIDGE_TYPE_TRANSFER)
            msg.setFlags(CONST.BRIDGE_FLAG_TRANSFER)
            contentSize = CONST.BRIDGE_MAX_SIZE_OF_BUFFER - \
                CONST.BRIDGE_FRAME_HEADER_LEN
            
            while True:
                chunk = taskMng.retrive(contentSize)
                if chunk == b'':
                    break
                msg.setContent(chunk) 
                nBytes = self.Bridge_send(msg.message())
                if nBytes == 0:
                    taskMng.rollBack()
                    return False

        elif flags & CONST.BRIDGE_FLAG_IS_JOB_DONE:
            # Job processing query
            if taskID_str in BridgeEntry.__taskTbl:
                if BridgeEntry.__taskTbl[taskID_str].isTaskFinished():
                    msg.setFlags(CONST.BRIDGE_FLAG_JOB_DONE)
                    self.Bridge_send(msg.message())  
                else:
                    msg.setFlags(CONST.BRIDGE_FLAG_EMPTY) 
                    self.Bridge_send(msg.message())
            else:
                msg = BridgeMsg(CONST.BRIDGE_TYPE_REPLY, 0, 0, taskID, CONST.BRIDGE_FLAG_ERROR)
                self.Bridge_send(msg.message())
                return False

        elif flags & CONST.BRIDGE_FLAG_RETRIVE:
            # Task result retrive
            if taskID_str in BridgeEntry.__taskTbl:
                msg.setFlags(CONST.BRIDGE_FLAG_READY_TO_SEND)
                self.Bridge_send(msg.message())

                # While a little while
                # if this delay case performance problem
                # or stable problem we may need to use 
                # three way hand shake.
                time.sleep(0.1) 
                
                msg.setType(CONST.BRIDGE_TYPE_TRANSFER)
                msg.setFlag(CONST.BRIDGE_FLAG_TRANSFER)
                
                contentSize = CONST.BRIDGE_MAX_SIZE_OF_BUFFER - CONST.BRIDGE_FRAME_HEADER_LEN
                while True:
                    chunk = taskMng.retrive(contentSize)
                    if chunk == b'':
                        break
                    msg.setContent(chunk)
                    nBytes = self.Bridge_send(msg.message())
                    if nBytes == 0:
                        taskMng.rollBack()
                        return False

        else:
            msg.setFlags(CONST.BRIDGE_FLAG_ERROR)
            self.Bridge_send(msg.message())
            
    def __infoProcessing(self, frame):
        pass

    def __management(self, frame):
        pass

    def accept(self):
        frame = [b'']

        self.Bridge_recv(frame, 0)
        
        if wrapper.BridgeIsRequest(frame[0]):
            return self.__requestProcessing(frame[0])
        elif wrapper.BridgeIsInfo(frame[0]):
            return self.__infoProcessing
        elif wrapper.BridgeIsManagement(frame[0]):
            return self.__management(frame[0])
        else:
            return False 
         
    def done(self):
        pass

    # Protocol data unit
    def Bridge_send(self, frame, flags):
        return wrapper.socket_send_wrapper(self.__socket, frame, flags)
    def Bridge_recv(self, frame, flags): 
        header = [b'']
        self.Bridge_recv_header(header, 0)
        
        contentLen = wrapper.BridgeLengthField(header[0])
        ret = wrapper.socket_recv_wrapper(self.__socket, frame, 
                contentLen - CONST.BRIDGE_FRAME_HEADER_LEN, flags)
        if ret == False:
            return False
        frame[0] = header[0] + frame[0]
        print(wrapper.BridgeContentField(frame[0]))
        return True

    def Bridge_recv_header(self, frameHeader, flags):
        return wrapper.socket_recv_wrapper(self.__socket, frameHeader, 
                CONST.BRIDGE_FRAME_HEADER_LEN, flags)
    def Bridge_transfer(data): 
        pass  
