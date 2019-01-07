<?php

/* Send & Recv function */

class BridgeList {

    private $list;

    function __construct() {
        $this->list = new SplDoublyLinkedList(); 
    }
    
    // Found: return index
    // Not Found: return -1
    public function search($taskID) {
        $listHead = $this->list->rewind; 
        while ($listHead->valid()) {
            if ($taskID == $listHead->current()) {
                return $listHead->key(); 
            } 
        }
        return -1;
    }

    public function add($taskID) {
        return $this->list->push($taskID); 
    }

    public function remove($taskID) {
        $key = $this->search($taskID); 
        if ($key == -1)
            return False;
        $this->list->offsetUnset($key);
        return True;
    }

    public function toArray() {
        $array = array();
        $listHead = $this->list;
        
        while ($listHead->valid()) {
            array_push($array, $listHead->current());
            $listHead->next(); 
        }  
        return $array;
    }
}

define("BRIDGE_DEBUG_OFF", 0);
define("BRIDGE_DEBUG_ON", 1);
$BRIDGE_DEBUG_SWITCH = BRIDGE_DEBUG_ON;

function BRIDGE_DEBUG_MSG($msg) {
    global $BRIDGE_DEBUG_SWITCH;
    if ($BRIDGE_DEBUG_SWITCH)
        echo $msg;
}

function socket_send_wrapper($socket, $message, $lenShouldSent, $flags) {
    while ($lenShouldSent > 0) {
        $nBytes = socket_send($socket, $message, $lenShouldSent, $flags);
        if ($nBytes == FALSE || $nBytes == 0) 
            return FALSE;
        $lenShouldSent = $lenShouldSent - $nBytes;
        $message = substr($message, -($lenShouldSent)); 
    }
    return $nBytes;
}

function socket_recv_wrapper($socket, &$buffer, $lenShouldRecv, $flags) {
    while ($lenShouldRecv > 0) {
        $nBytes = socket_recv($socket, $recvBuffer, $lenShouldRecv, $flags);
        if ($nBytes == FALSE) {
            return FALSE; 
        }
        $lenShouldRecv = $lenShouldRecv - $nBytes;       
        $buffer = $buffer . $recvBuffer;  
    }
    return $nBytes; 
}

function Bridge_header_validate($frame) {
    if ($frame == NULL) 
        return False;
    
    if (strlen($frame) != BRIDGE_FRAME_HEADER_LEN) {
        return False; 
    }
    
    $type = BridgeTypeField($frame);
    if ($type < BRIDGE_TYPE_REQUEST || $type > BRIDGE_TYPE_TRANSFER) 
        return False; 

    $op = BridgeOpField($frame);
    if ($op < BRIDGE_OP_NONE || $op > BRIDGE_OP_SET) 
        return False; 

    $prop = BridgePropField($frame);
    if ($prop != BRIDGE_PROP_NONE) 
        return False;

    $taskID = BridgeTaskIDField($frame);
    if ($taskID < 0)
        return False;

    $flags = BridgeFlagField($frame);
    if ($flags < BRIDGE_FLAG_EMPTY || $flags > BRIDGE_FLAG_JOB_DONE) 
        return False;

    $len = BridgeLengthField($frame);
    if ($len < BRIDGE_FRAME_HEADER_LEN)
        return False;

    return True;
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

function BridgeLengthField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['length'];
}

function BridgeContentField($frame) {
    $frameBuffer = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $frame);
    return $frameBuffer['content'];
} /* Frame related function */ 
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
function BridgeIsAcceptSet($frame) {
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

