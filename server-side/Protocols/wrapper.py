#!/usr/bin/python

import socket
import definitions as CONST
from definitions import CONST 

def socket_send_wrapper(sock, data, flag):
    shouldSent = len(data)
    while shouldSent > 0:
        sent = sock.send(data, flag)
        if sent == 0:
            return False
        shouldSent = shouldSent - sent
def socket_recv_wrapper(sock, buffer_, shouldRecv, flags):
    while shouldRecv > 0:
        chunk = sock.recv(CONST.BRIDGE_MAX_SIZE_OF_BUFFER, flags)
        if chunk == b'':
            return False
        buffer_[0] = buffer_[0] + chunk
        shouldRecv = shouldRecv - len(chunk)

# Type filed fetch
def BridgeFieldFetch(frame, field):
    header = unpack(CONST.BRIDGE_FRAME_FORMAT_UNPACK, frame[:CONST.BRIDGE_FRAME_HEADER_LEN])
    return header[field]
def BridgeTypeField(frame):
    return BridgeFieldFetch(frame, CONST.BRIDGE_FRAME_TYPE_OFFSET)
def BridgeOpField(frame):
    return BridgeFieldFetch(frame, CONST.BRIDGE_FRAME_OP_OFFSET)
def BridgePropField(frame):
    return BridgeFieldFetch(frame, CONST.BRIDGE_FRAME_PROP_OFFSET)
def BridgeTaskIDField(frame):
    return BridgeFieldFetch(frame, CONST.BRIDGE_FRAME_TASKID_OFFSET)
def BridgeFlagField(frame):
    return BridgeFieldFetch(frame, CONST.BRIDGE_FRAME_FLAG_OFFSET)
def BridgeContentField(frame):
    return frame[CONST.BRIDGE_FRAME_HEADER_LEN:]

# Type field check
def BridgeTypeFieldCheck(frame, expect):
    type_ = BridgeTypeField(frame)
    return type_ == expect
def BridgeIsRequest(frame):
    return BridgeTypeFieldCheck(frame, CONST.BRIDGE_TYPE_REQUEST)
def BridgeIsReply(frame):
    return BridgeTypeFieldCheck(frame, CONST.BRIDGE_TYPE_REPLY)
def BridgeIsInfo(frame):
    return BridgeTypeFieldCheck(frame, CONST.BRIDGE_TYPE_INFO)
def BridgeIsManagement(frame):
    return BridgeTypeFieldCheck(frame, CONST.BRIDGE_TYPE_MANAGEMENT)
def BridgeIsTransfer(frame):
    return BridgeTypeFieldCheck(frame, CONST.BRIDGE_TYPE_TRANSFER)

# Op field check
def BridgeOpFieldCheck(frame, expect):
    op_ = BridgeOpField(frame)
    return op_ == expect
def BridgeIsOpEnable(frame):
    return BridgeOpFieldcheck(frame, CONST.BRIDGE_OP_ENABLE)
def BridgeIsOpDisable(frame):
    return BridgeOpFieldCheck(frame, CONST.BRIDGE_OP_DISABLE)
def BridgeIsOpSet(frame):
    return BridgeOpFieldCheck(frame, CONST.BRIDGE_OP_SET)

# Flag field
def BridgeFlagFieldCheck(frame, bit):
    flag = BridgeFlagField(frame)
    return flag & bit
def BridgeIsNOtifySet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_NOTIFY)
def BridgeIsTransferSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_TRANSFER)
def BridgeIsTransDoneSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_TRANSFER_DONE)
def BridgeIsAcceptSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_ACCEPT)
def BridgeIsDeclineSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_DECLINE)
def BridgeisReadyToSendSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_READY_TO_SEND)
def BridgeIsIsJobDoneSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_IS_JOB_DONE)
def BridgeIsJobDoneSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_JOB_DONE)
def BridgeIsRecoverSet(frame):
    return BridgeFlagFieldCheck(frame, CONST.BRIDGE_FLAG_RECOVER)

