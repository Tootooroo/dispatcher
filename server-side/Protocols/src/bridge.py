#!/usr/bin/python

import socket
import struct
import time

import src.wrapper as wrapper
from src.definitions import CONST
from mysql import connector as MConnector

class TaskArea:
    def __init__(self, size):
        self.__area = {}
    def enter(self, task):
        if task.id() in self.__area:
            return False
        self.__area.append[task.id()] = task
    def remove(self, taskID):
        if task.id() not in self.__area:
            return False
        self.__area.pop(taskID)
    def search(self, taskID):
        if task.id() not in self.__area:
            return False
        return self.__area[taskID]

class TaskInfo:
    def __init__(self, taskID, entry):
        # Task states 
        self.__sock = ''
        self.__taskID = taskID
        self.__lastSeekDistance = CONST.BRIDGE_TASK_NET_STATUS_CONNECTED
        self.__netStat = 0
        self.__jobStatus = CONST.BRIDGE_TASK_STATUS_PENDING   
        self.__entry = entry
        self.__procInfoRetriveArgs = 0     

        # For result store in files
        self.__path = ''
        self.__descriptor = 0
        self.__files = ''

        # For result store in memory buffer
        self.__buffer = 0

        # Task callback
        self.__rollback = 0
        self.__retrive = 0
        self.__reset = 0
        self.__infoRetrive = 0
     
    def sockSet(self, sock):
        self.__sock = sock
        return True

    def id(self):
        return self.__taskID
   
    def procInfoArgsGet(self):
        return self.__procInfoRetriveArgs
    
    def procInfoArgsSet(self, args):
        self.__procInfoRetriveArgs = args
        return True

    # Files
    def descriptor(self):
        return self.__descriptor
    
    def path(self):
        return self.__path

    def setPath(self, path):
        self.__path = path
        return True
    
    def setFiles(self, files):
        self.__files = files
        return True

    def openFileResult(self):
        self.__descriptor = open(self.__path, "rb")
        return True

    # Memory
    def bufferFd(self):
        return self.__buffer

    def rollbackRtnSet(self, rtn):
        self.__rollback = rtn

    def retriveRtnSet(self, rtn):
        self.__retrive = rtn

    def procInfoRetriRtnSet(self, rtn):
        self.__infoRetrive = rtn

    def resetRtnSet(self, rtn):
        self.__reset = rtn
        
    def netStatus(self):
        return self.__netStat

    def setNetStatus(self, stat):
        if stat != CONST.BRIDGE_TASK_NET_STATUS_CONNECTED or stat != CONST.BRIDGE_TASK_NET_STATUS_DISCONNECTED:
            return False
        self.__netStat = stat
        return True

    def taskStatus(self):
        return self.__jobStatus
     
    def getTaskStatus(self):
        return self.__jobStatus;

    def setTaskStatus(self, status):
        if status < CONST.BRIDGE_TASK_STATUS_FAILED or status > CONST.BRIDGE_TASK_STATUS_PENDING:
            return False
        self.__jobStatus = status
        return True
    
    def getTaskID(self):
        return self.__taskID

    def getEntry(self):
        return self.__entry

    # Rollback to last success state 
    def rollback(self):
        return self.__rollback(self)

    def retrive(self, size):
        return self.__retrive(self, size)

    def reset(self):
        return self.__reset(self)

    def infoRetrive(self):
        return self.__infoRetrive(self)

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
        if not wrapper.BridgeTypeFieldVerify(type_):
            return False
        else:
            self.__type = type_
            return True
    
    def op(self):
        return self.__op
    def setOp(self, op_):
        if not wrapper.BridgeOpFieldVerify(op_):
            return False
        else:
            self.__op = op_
            return True

    def content(self):
        return self.__content
    def setContent(self, content_):
        self.__length = len(content_) + CONST.BRIDGE_FRAME_HEADER_LEN
        self.__content = content_

    def message(self):
        return struct.pack(CONST.BRIDGE_FRAME_FORMAT_PACK % len(self.__content),
                self.__type, self.__op, self.__prop, self.__taskID, 
                self.__flags, self.__length, self.__content)       

    def length(self):
        return self.__length

class BridgeEntry:  

    # Task info
    # Format of Tasktbl
    # | TID | JobTransRtn | ContinueRtn | Info
    __taskTbl = {}

    def __init__(self, sock):
        self.__currentTaskID = -1
        self.__requestRoutine = {
            CONST.BRIDGE_FLAG_NOTIFY : self.__newTaskHandle,         
            CONST.BRIDGE_FLAG_RECOVER : self.__taskRecover,
            CONST.BRIDGE_FLAG_IS_JOB_DONE : self.__isTaskReady,
            CONST.BRIDGE_FLAG_RETRIVE : self.__taskRetrive
        }
        # Socket initialization
        self.__socket = sock 

    def __newTask(self, taskID):
        BridgeEntry.__taskTbl[taskID] = TaskInfo(taskID, self)

    def __rmTask(self, taskID):
        del BridgeEntry.__taskTbl[taskID]
   
    def taskInfoGet(self, taskID):
        try:
            tInfo = BridgeEntry.__taskTbl[taskID]
        except IndexError:
            print("taskInfoGet Index Error")
            return False
        except KeyError:
            print("KeyError")
            return False
        return tInfo

    def taskTaskInfoGet(self, taskID):
        return BridgeEntry.__taskTbl[taskID] 
    
    def __newTaskHandle(self, taskID, flags, content):
        msg = BridgeMsg(CONST.BRIDGE_TYPE_REPLY, 0, 0, taskID, CONST.BRIDGE_FLAG_ACCEPT)
        
        if taskID in BridgeEntry.__taskTbl:
            if BridgeEntry.__taskTbl[taskID].netStatus() == CONST.BRIDGE_TASK_NET_STATUS_DISCONNECTED:
                msg.setFlags(CONST.BRIDGE_FLAG_EMPTY)
                self.Bridge_send(msg.message(), 0)
                BridgeEntry.__taskTbl[taskID].setNetStatus(CONST.BRIDGE_TASK_NET_STATUS_CONNECTED)
                return True
            else:
                msg.setFlags(CONST.BRIDGE_FLAG_ERROR)
                self.Bridge_send(msg.message(), 0)
                return True

        self.Bridge_send(msg.message(), 0)
        
        self.__newTask(taskID)

        return (taskID, content)
        
    def __taskRecover(self, taskID, flags, content):
        # After recover Worker should be recover to the 
        # status before the internet broken.
        pass    

    def __isTaskReady(self, taskID, flags, content):
        # Job processing query
        if taskID in BridgeEntry.__taskTbl:
            if BridgeEntry.__taskTbl[taskID].getTaskStatus() == CONST.BRIDGE_TASK_STATUS_SUCCESS:
                msg.setFlags(CONST.BRIDGE_FLAG_JOB_DONE)
                self.Bridge_send(msg.message(), 0)  
            else:
                msg.setFlags(CONST.BRIDGE_FLAG_EMPTY) 
                self.Bridge_send(msg.message(), 0)
        else:
            msg = BridgeMsg(CONST.BRIDGE_TYPE_REPLY, 0, 0, taskID, CONST.BRIDGE_FLAG_ERROR)
            self.Bridge_send(msg.message(), 0)
            return False
        return True

    def __taskRetrive(self, taskID, flags, content):
        msg = BridgeMsg(CONST.BRIDGE_TYPE_REPLY, 0, 0, taskID, CONST.BRIDGE_FLAG_ERROR)

        # Task result retrive
        if taskID in BridgeEntry.__taskTbl:
            if (BridgeEntry.__taskTbl[taskID].getTaskStatus() == CONST.BRIDGE_TASK_STATUS_SUCCESS):
                msg.setFlags(CONST.BRIDGE_FLAG_READY_TO_SEND)
                self.Bridge_send(msg.message(), 0)
            else:
                msg.setFlags(CONST.BRIDGE_FLAG_EMPTY)
                self.Bridge_send(msg.message(), 0)
                return True

            # While a little while
            # if this delay case performance problem
            # or stable problem we may need to use 
            # three way hand shake.
            time.sleep(0.1) 
            
            msg.setType(CONST.BRIDGE_TYPE_TRANSFER)
            msg.setFlags(CONST.BRIDGE_FLAG_TRANSFER)
            
            taskMng = BridgeEntry.__taskTbl[taskID]
            contentSize = CONST.BRIDGE_MAX_SIZE_OF_BUFFER - CONST.BRIDGE_FRAME_HEADER_LEN

            while True:
                chunk = taskMng.retrive(contentSize)

                # Content print
                wrapper.BRIDGE_DEBUG_MSG("==========================")
                wrapper.BRIDGE_DEBUG_MSG("Length of chunk is " + str(len(chunk)))
                wrapper.BRIDGE_DEBUG_MSG(chunk)        

                if not chunk:
                    wrapper.BRIDGE_DEBUG_MSG("Transfer done.")
                    msg.setFlags(CONST.BRIDGE_FLAG_TRANSFER_DONE)
                    msg.setContent(b"")
                    self.Bridge_send(msg.message(), 0)

                    # Task is already done 
                    # so delete task info 
                    # from table.
                    del self.__taskTbl [taskID]

                    return True

                msg.setContent(chunk)

                nBytes = self.Bridge_send(msg.message(), 0)

                if nBytes == 0:
                    taskMng.rollBack()
                    return False
        else:
            wrapper.BRIDGE_DEBUG_MSG("Task Not Found")
            self.Bridge_send(msg.message(), 0)
            return False

    def __requestProcessing(self, frame):
        taskID = wrapper.BridgeTaskIDField(frame) 
        flags = wrapper.BridgeFlagField(frame)  
        content = wrapper.BridgeContentField(frame)
        
        self.__currentTaskID = taskID

        wrapper.BRIDGE_DEBUG_MSG("In __requestProcessing")
        wrapper.BRIDGE_DEBUG_MSG("TaskID is " + str(taskID))

        msg = BridgeMsg(CONST.BRIDGE_TYPE_REPLY, 0, 0, taskID, CONST.BRIDGE_FLAG_ERROR)
        
        try:
            ret = self.__requestRoutine[flags](taskID, flags, content)
        except IndexError: 
            self.Bridge_send(msg.message(), 0)

        return ret
    
    def procInfoRetrive(self, taskID, flags, content):
        msg = BridgeMsg(CONST.BRIDGE_TYPE_INFO, 0, 0, taskID, CONST.BRIDGE_FLAG_NOTIFY)
        tInfo = BridgeEntry.__taskTbl[taskID]  

        # Info is still not generate in this phase.
        if tInfo.getTaskStatus() == CONST.BRIDGE_TASK_STATUS_PENDING:
            msg.setFlags(CONST.BRIDGE_FLAG_DECLINE)
            self.Bridge_send(msg.message(), 0)
            return True
        
        # Info transfer
        infoContent = tInfo.infoRetrive()
        if not infoContent:
            # Info transfering is done.
            if tInfo.getTaskStatus() == CONST.BRIDGE_TASK_STATUS_SUCCESS:
                msg.setFlags(CONST.BRIDGE_FLAG_JOB_DONE) 
        msg.setContent(infoContent.encode())
        self.Bridge_send(msg.message(), 0)

        return True

        
    def __infoProcessing(self, frame):
        taskID = wrapper.BridgeTaskIDField(frame)
        flags = wrapper.BridgeFlagField(frame)
        content = wrapper.BridgeContentField(frame)

        self.__currentTaskID = taskID
        msg = BridgeMsg(CONST.BRIDGE_TYPE_INFO, 0, 0, taskID, CONST.BRIDGE_FLAG_ERROR)

        try:
            ret = self.procInfoRetrive(taskID, flags, content)
        except IndexError:
            self.Bridge_send(msg.message(), 0)
        return ret

    def __management(self, frame):
        pass

    def accept(self):
        frame = [b'']

        ret = self.Bridge_recv(frame, 0)
        if ret == False:
            return False

        if wrapper.BridgeIsRequest(frame[0]):
            return self.__requestProcessing(frame[0])
        elif wrapper.BridgeIsInfo(frame[0]):
            return self.__infoProcessing(frame[0])
        elif wrapper.BridgeIsManagement(frame[0]):
            return self.__management(frame[0])
        else:
            return False 
         
    def done(self):
        pass
    
    # Protocol data unit
    def Bridge_send(self, frame, flags):
        if wrapper.socket_send_wrapper(self.__socket, frame, flags) == False:
            tInfo = self.taskInfoGet(self.__currentTaskID)
            tInfo.setNetStatus(CONST.BRIDGE_TASK_NET_STATUS_DISCONNECTED)
            return False

    def Bridge_recv(self, frame, flags): 
        header = [b'']

        ret = self.Bridge_recv_header(header, 0)
        if ret == False:
            return False

        contentLen = wrapper.BridgeLengthField(header[0])
        ret = wrapper.socket_recv_wrapper(self.__socket, frame, 
                contentLen - CONST.BRIDGE_FRAME_HEADER_LEN, flags)
        if ret == False:
            tInfo = self.taskInfoGet(self.__currentTaskID)
            tInfo.setNetStatus(CONST.BRIDGE_TASK_NET_STATUS_DISCONNECTED)
            return False

        frame[0] = header[0] + frame[0]
        return True

    def Bridge_recv_header(self, frameHeader, flags):
        return wrapper.socket_recv_wrapper(self.__socket, frameHeader, 
                CONST.BRIDGE_FRAME_HEADER_LEN, flags)
    def Bridge_transfer(data): 
        pass  

