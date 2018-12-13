#!/usr/bin/python

class BridgeQueue:
    
    def __init__(self):
        self.__count = 0
        self.__queue = []

    def enQueue(self, elem):
        self.__queue.insert(0, elem)
        self.__count = self.__count + 1

    def deQueue(self):
        try:
            elem = self.__queue.pop()
            self.__count = self.__count - 1
        except IndexError:
            elem = False
    
    def count(self):
        return self.__count

