#!/usr/bin/python

from threading import Lock

class BridgeQueue:
    
    def __init__(self):
        self.__count = 0
        self.__queue = []
        self.__lock = Lock()

    def enQueue(self, elem):
        self.__lock.acquire(timeout = -1)

        self.__queue.insert(0, elem)
        self.__count = self.__count + 1
       
        self.__lock.release()

    def deQueue(self):
        self.__lock.acquire(timeout = -1)
        try:
            elem = self.__queue.pop()
            self.__count = self.__count - 1
        except IndexError:
            elem = False
            self.__lock.release()
        self.__lock.release()

        return elem
    
    def count(self):
        return self.__count

if __name__ == '__main__':
    pass

