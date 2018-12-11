#!/usr/bin/python

import sys
sys.path.append("..")

import socket
from src.bridge import *

lSock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
lSock.bind(("127.0.0.1", 8813))

lSock.listen(10)
while True:
    pair = lSock.accept()
    print("Accept")

    entry = BridgeEntry(pair[0]) 
    content = entry.accept()

