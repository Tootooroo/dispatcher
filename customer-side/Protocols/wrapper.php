#!/bin/php -a

<?php

/* Send & Recv function */
function socket_send_wrapper($socket, $message, $lenShouldSent, $flags) {
    while ($lenShouldSent > 0) {
        $nBytes = socket_send($socket, $message, $lenShouldSent, $flags);
        if ($nBytes == FALSE) 
            return FALSE;
        if ($nBytes < $lenShouldSent) {
            $lenShouldSent = $lenShouldSent - $nBytes;
            $message = substr($message, -($lenShouldSent)); 
        }
    }
    return $nBytes;
}

function socket_recv_wrapper($socket, &$buffer, &$lenShouldRecv, $flags) {
    while ($lenShouldRecv > 0) {
        $nBytes = socket_recv($socket, $recvBuffer, $lenShouldRecv, $flags);
        if ($nBytes == FALSE) {
            return FALSE; 
        }
        if ($nBytes < $lenShouldRecv) {
            $lenShouldRecv = $lenShouldRecv - $nBytes;       
        }
        $buffer = $buffer . $recvBuffer;  
    }
    return $nBytes; 
}

/* Field query functions */
function BridgeTypeField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['type'];
}

function BridgeOpField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['op'];
}

function BridgePropField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['prop'];
}

function BridgeTaskIDField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['taskid'];
}

function BridgeFlagField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['flag'];
}

function BridgeContentField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['content'];
}

/* Frame related function */

// Type Field
function BridgeTypeFieldCheck($frame, $expect) {
    $type = BridgeTypeField($frame);
    return $type == $expect;
}
function BridgeIsRequest($frame) {
    return BridgeTypeFieldCheck($frame, BRIDGE_TYPE_REQUEST);
}
function BridgeIsReply($frame) {
    return BridgeTypeFieldCheck($frame, BRIDGE_TYPE_REPLY);
}
function BridgeIsInfo($frame) {
    return BridgeTypeFieldCheck($frame, BRIDGE_TYPE_INFO);
}
function BridgeIsManagement($frame) {
    return BridgeTypeFieldCheck($frame, BRIDGE_TYPE_MANAGEMENT);
}
function BridgeIsTransfer($frame) {
    return BridgeTypeFieldCheck($frame, BRIDGE_TYPE_TRANSFER);
}

// Op Field
function BridgeOpFieldCheck($frame, $expect) {
    $op = BridgeOpField($frame);
    return $op == $expect;
}
function BridgeIsOpEnable($frame) {
    return BridgeOpFieldCheck($frame, BRIDGE_OP_ENABLE);
}
function BridgeIsOpDisable($frame) {
    return BridgeOpFieldCheck($frame, BRIDGE_OP_DISABLE);
}
function BridgeIsOpSet($frame) {
    return BridgeOpFieldCheck($frame, BRIDGE_OP_SET);
}



// Flag Field
function BridgeFlagFieldCheck($frame, $bit) {
    $flag = BridgeFlagField($frame);
    return $flag & $bit;
}
function BridgeIsNotifySet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_NOTIFY);
}
function BridgeIsTransferSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_TRANSFER);
}
function BridgeIsTransDoneSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_TRANSFER_DONE);
}
function BridgeIsAccpetSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_ACCEPT);
}
function BridgeIsDeclineSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_DECLINE);
}
function BridgeIsReadyToSendSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_READY_TO_SEND);
}
function BridgeIsIsJobDoneSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_IS_DONE);
}
function BridgeIsJobDoneSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_JOB_DONE);
}
function BridgeIsRecoverSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_RECOVER);
}
