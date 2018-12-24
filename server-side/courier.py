#!/usr/bin/python

import dataHouse as DB_OP
from config import CONST
from threading import Lock
from mysql import connector as MConnector

class BridgeQueue:
    
    def __init__(self):
        self.__dbConn = MConnector.connect(
                user = CONST.DB_USERNAME,
                password = CONST.DB_PASSWORD,
                host = CONST.DB_HOST,
                database = CONST.DB_DATABASE)
        self.__count = 0
        self.__queue = []
        self.__lock = Lock()

    def __del__(self):
        self.__dbConn.close()

    def enQueue(self, elem):
        self.__lock.acquire(timeout = -1)

        self.__queue.insert(0, elem)
        self.__count = self.__count + 1
       
        self.__lock.release()
        
        fd = self.__dbConn.cursor()
        DB_OP.row_update(fd, 
                "overhead", 
                "wID = {}".format(CONST.WORKER_ID), 
                "pending = pending + 1")
        fd.execute(sqlStmt)
        self.__dbConn.commit()
        fd.close() 

        return True

    def deQueue(self):
        self.__lock.acquire(timeout = -1)
        try:
            elem = self.__queue.pop()
            self.__count = self.__count - 1
        except IndexError:
            elem = False
            self.__lock.release()
        self.__lock.release()
        
        fd = self.__dbConn.cursor()
        DB_OP.row_update(fd,
                "overHead",
                "wID = {}".format(CONST.WORKER_ID),
                "pending = pending - 1")
        DB_OP.row_update(fd,
                "overHead",
                "wID = {}".format(CONST.WORKER_ID),
                "inProc = inProc + 1")
        
        self.__dbConn.commit()
        fd.close()
        return elem
    
    def count(self):
        return self.__count

if __name__ == '__main__':
    queue = BridgeQueue()
    
    values = ["1", "2", "3", "4", "5"]
    for value in values:
        queue.enQueue(value)
    while (queue.count() > 0):
        print(queue.deQueue(), queue.count())


