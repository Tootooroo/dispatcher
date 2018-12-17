#!/usr/bin/python
import os
import socket
import subprocess
from Protocols.src import *

MAX_NUM_OF_USERS = 10
MAX_NUM_OF_MISSIONS = 10

lSock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
lSock.bind(("127.0.0.1", 8813))
lSock.listen(MAX_NUM_OF_USERS)

while True:
    queue = BridgeQueue()
    disT = DispatchThread(queue)
    disT.run()

    pair = lSock.accept()
    print("A new connection received")

    entry = BridgeEntry(pair[0])
    t = BriThread(entry, queue)
    t.run()


def imgRollback(info):
    pass 
def imgRetrive(info, size):
    fd = info.descriptor()
    if (fd == 0):
        info.openFileResult()
        fd = info.descriptor()
    rBuffer = fd.read(size)
    return rBuffer

def imgReset(info):
    pass

class BriThread(Thread):
    def __init__(entry, queue):
        self.__entry = entry
        self.__queue = queue

    def run(self):
        while (True):
            mission = self.__entry.accept()
            queue.enQueue((self.__entry, mission))

class ProcessingThread(Thread):
    def __init__(entry, missionInfo):
        self.__entry = entry
        self.__taskID = missionInfo[0]
        self.__command = missionInfo[1]
        self.__taskInfo = entry.taskInfoGet()

        # Setting Task callback
        self.__taskInfo.rollbackRtnSet(imgRollback)
        self.__taskInfo.retriveRtnSet(imgRetrive)
        self.__taskInfo.resetRtnSet(imgReset)
        self.__taskInfo.setPath(CONST.RESULT_PATH)

    def run():
        dirpath = tempfile.mkdtemp(prefix=WORKING_DIR)
        os.chdir(dirpath)
        # Git clone from git server
        subprocess.run(missionInfo[1])
        # And then compile the source file
        os.chdir(dirpath + PROJECT_NAME + COMPILE_ROOT)    
        for command in CONST.COMPILE_COMMAND:
            ret = subprocess.run(command)
            if (ret != 0):
                self.__taskInfo.setTaskStatus = BCONST.BRIDGE_TASK_STATUS_FAILED
                break
        self.__taskInfo.setTaskStatus = BCONST.BRIDGE_TASK_STATUS_SUCCESS
        # Compress files         
        os.chdir(RESULT_PATH)
        zipfd = zipfile.ZipFile("result.zip")
        for file_ in RESULT_FILES:
            zipfd.write(file_)
        self.setFiles = RESULT_PATH + "result.zip"

class DispatchThread(Thread):
    def __init__(queue):
        self.__queue = queue
        self.__num_of_missions = 0
    def run(self):
        while (True):
            if (self.__queue.count() > 0):
                if (self.__num_of_missions > MAX_NUM_OF_MISSIONS):
                    sleep(5)
                    continue
                missionInfo = self.__queue.deQueue()
                pThread = ProcessingThread(missionInfo[0], mission[1])
                pThread.run()
                self.__num_of_missions = self.__num_of_missions + 1
            else:
                sleep(5)


