#!/usr/bin/python

import socket
from .. import bridge

lSock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
lSock.bind("127.0.0.1", 8813)

lSock.listen(1)
while True:
    cSock = lSock.accept()
    entry = BridgeEntry(cSock) 
     
    content = entry.accept()
    print(content)

