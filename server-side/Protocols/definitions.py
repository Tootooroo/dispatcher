#!/usr/bin/python

class CONST:
    # Maximum size of buffer
    BRIDGE_MAX_SIZE_OF_BUFFER = 1024
    # Bridge frame header length : Type(2) | OP(2) | PROP(2) | TASKID(2) | FLAG(2) | LEN(4) 
    BRIDGE_FRAME_HEADER_LEN = 14
    # Entry state
    ENTRY_UP = 0x01
    ENTRY_DOWN = 0x00

    # Binary buffer format
    BRIDGE_FRAME_FORMAT_PACK = "HHHHHI%ds"
    BRIDGE_FRAME_FORMAT_UNPACK = "HHHHHI"
    
    # Frame field offset
    BRIDGE_FRAME_TYPE_OFFSET = 0
    BRIDGE_FRAME_OP_OFFSET = 1
    BRIDGE_FRAME_PROP_OFFSET = 2
    BRIDGE_FRAME_TASKID_OFFSET = 3
    BRIDGE_FRAME_FLAG_OFFSET = 4
    BRIDGE_FRAME_LEN_OFFSET = 5 

    # Bridge type field constant
    BRIDGE_TYPE_REQUEST = 0x01
    BRIDGE_TYPE_REPLY = 0x02
    BRIDGE_TYPE_INFO = 0x03
    BRIDGE_TYPE_MANAGEMENT = 0x04
    BRIDGE_TYPE_TRANSFER = 0x05

    # Bridge op field constant
    BRIDGE_OP_ENABLE = 0x01
    BRIDGE_OP_DISABLE = 0x02
    BRIDGE_OP_SET = 0x03

    # Bridge flag field constant
    BRIDGE_FLAG_NOTIFY = 0x0001
    BRIDGE_FLAG_TRANSFER = 0x0002
    BRIDGE_FLAG_TRANSFER_DONE = 0x0004
    BRIDGE_FLAG_ACCEPT = 0x0008
    BRIDGE_FLAG_DECLINE = 0x0010
    BRIDGE_FLAG_RETRIVE = 0x0020
    BRIDGE_FLAG_READY_TO_SEND = 0x0040
    BRIDGE_FLAG_ERROR = 0x0080
    BRIDGE_FLAG_IS_JOB_DONE = 0x0100
    BRIDGE_FLAG_RECOVER = 0x0200
    BRIDGE_FLAG_JOB_DONE = 0x0400

    # Bridge property field constant specific property is pending


