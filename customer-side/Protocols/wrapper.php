#!/bin/php -a

<?php

/* Reconnect */
function CHANNEL_REBUILD(array $args) {
 // Reconnect to worker 
    while (true) {
        $args['socket'] = SocketConnect_TCP($args['address'], $args['port']);
        if ($args['socket'])
            break;
    } 
    // Send Recover request
    $recoverReq = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $args['ID'],
        BRIDGE_FLAG_RECOVER, BRIDGE_FRAME_HEADER_LEN);
    if (Bridge_send($args['socket'], $recoverReq->message() $item->length(), NULL) == FALSE)
       return FALSE; 
    if (Bridge_recv($args['socket'], $buffer, ,NULL) == FALSE)
       return FALSE; 
    if (!BridgeIsReply($buffer) || !BridgeIsAccpetSet($buffer))
        return FALSE;  

}
function CHANNEL_MAINTAIN_SEND($RTN, array $args) {
    do {
        $ret = $RTN($args['socket'], $args['buffer'], $args['len'], $args['flags']); 
        if ($ret == FALSE) 
            CHANNEL_REBUILD($args); 
    } while ($ret == FALSE);
}

function CHANNEL_MAINTAIN_RECV($RTN, array $args) {
    do {
        $ret = $RTN($args['socket'], $args['buffer'], $args['len'], $args['flags']); 
        /* Try Reconnect if lost */
        if ($ret == FALSE) 
            CHANNEL_REBUILD($args); 
    } while ($ret == FALSE);
}

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

function Bridge_send($socket, $message, $lenShouldSent, $flags) {
    return CHANNEL_MAINTAIN_SEND('socket_send_wrapper', $socket, $message, $lenShouldSent, $flags);
}

function Bridge_recv_header($bridgeEntry, &$buffer, $flags) {
    $buffer_ = $buffer;
    $len = BRIDGE_FRAME_HEADER_LEN;
    return CHANNEL_MAINTAIN_RECV('socket_recv_wrapper', $socket, $buffer_, $len, $flags);
}

function Bridge_header_validate($buffer) {
    return TRUE; 
}

function Bridge_recv($socket, &$buffer, $flags) {
    $contentBuffer = null;

    $nBytes = Bridge_recv_header($socket, $buffer, $flags);             
    if ($nBytes == FALSE || Bridge_header_validate($buffer) == FALSE) {
        return FALSE; 
    }
    $header = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $buffer); 
    $length = $header['length'];
    $length = $length - BRIDGE_FRAME_HEADER_LEN;
    $nBytes = CHANNEL_MAINTAIN_RECV('socket_recv_wrapper', $socket, $contentBuffer, $length, $flags); 
    if ($nBytes == FALSE) {
        return FALSE; 
    }
    return $nBytes + BRIDGE_FRAME_HEADER_LEN; 
}

function Bridge_retrive($socket, $receiver, &$args) {
    $buffer = null;
    $len = BRIDGE_MAX_SIZE_OF_BUFFER;
    while (TRUE) {
        CHANNEL_MAINTAIN_RECV('socket_recv_wrapper', $socket, $buffer, $len, NULL); 

        if (BridgeIsTransfer($buffer)) {
            receiver($args, $content); 
        } else {
            if (BridgeIsTransDoneSet($buffer)) 
                return TRUE;      
        } 
    }
    return FALSE;
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
function BridgeIsJobDoneSet($frame) {
    return BridgeFlagFieldCheck($frame, BRIDGE_FLAG_IS_DONE);
}

