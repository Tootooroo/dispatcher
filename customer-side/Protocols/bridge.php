<?php

include "../wrapper.php";
include "../definitions.php";
include "../../util.php";

define("BRIDGE_RET_CODE_NOT_READY", 3);

class BridgeMsg {
    private $type;
    private $op;
    private $property;
    private $seqID;
    private $flags;
    private $length; 
    private $content;

    function __construct($type_, $op_, $property_, $seqID_, $flags_, $content_) {
        $this->type =  $type_; 
        $this->op = $op_;
        $this->property = $property_;
        $this->flags = $flags_;
        $this->length = BRIDGE_FRAME_HEADER_LEN + strlen($content_);
        $this->seqID = $seqID_;
        $this->content = $content_;

        return 0;
    }

    public function flags() {
        return $this->flags; 
    }

    public function setFlags($flags_) {
        // You may need to check whether is
        // flags value is valid.
        $this->flags = $flags_; 
        return 0;
    }

    public function seqID() {
        return $this->seqID; 
    }

    public function setSeqID($seqID_) {
        $this->seqID = $seqID_; 
        return 0;
    }

    public function type() {
        return $this->type; 
    }

    public function setType_($type_) {
        $check = $type_ != BRIDGE_TYPE_REQUEST &&
                 $type_ != BRIDGE_TYPE_INFO  &&
                 $type_ != BRIDGE_TYPE_MANAGEMENT;     
        if ($check) 
            return 1; 

        $this->type = $type_; 
        return 0;
    }     

    public function op() {
        return $this->op; 
    }

    public function setOp($op_) {
        $check = $op_ != BRIDGE_OP_ENABLE &&
                 $op_ != BRIDGE_OP_DISABLE &&
                 $op_ != BRIDGE_OP_SET; 
        if ($check) 
            return 1;

        $this->op = $op_;
        return 0;
    }
    
    public function content() {
        return $this->content; 
    }

    public function setContent($content_) {
        $this->content = $content_; 
        return 0;
    }

    public function message() {
        $message = pack(BRIDGE_FRAME_FORMAT_PACK, $this->type,
            $this->op, $this->property, $this->seqID, 
            $this->flags, $this->length, $this->content);
        return $message; 
    }
     
    public function length() {
        return $this->length; 
    }
}

class BridgeEntry {
    private $state;
    private $socket;  
    private $address;
    private $port;
    private $dbEntry;

    // The ID of task that in processing.
    private $currentTask; 
    private $readyTask;
    private $inProcessing;

    function __construct($address_, $port_) {
        $this->address = $address_;
        $this->port = $port_; 
        $this->socket = Null;
        $this->state = ENTRY_DOWN;
        $this->currentTask = 0;
        $this->readyTask = new BridgeList();
        $this->inProcessing = new BridgeList();
    }

    public function connect() { 
        $this->socket = SocketConnect_TCP($this->address, $this->port);
        $this->state = $this->socket == null ? ENTRY_DOWN : ENTRY_UP;
    }

    public function connectState() {
        if ($this->socket == null) {
            return False; 
        } else {
            return True; 
        }
    }

    public function dispatch($taskID, $content_) {
        $buffer = null;

        // Frame generation.
        $this->currentTask = $taskID; 

        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, 0, 0, $taskID, 
            BRIDGE_FLAG_NOTIFY, $content_);
        $message = $item->message();

        $ret = $this->BRIDGE_REQUEST($message, $buffer, BRIDGE_RESEND_COUNT); 
        if ($ret == False) {
            echo "BRIDGE_REQUEST Failed.";
           return False; 
        }

        if (!BridgeIsReply($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/dispatch: Is not a reply frame.<br>");
            return False;
        }
        
        if (!BridgeIsAcceptSet($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/dispatch: Dispatch is not accepted.<br>");
            return False;
        }
        $this->inProcessing->add($taskID);
        return True;
    } 
 
    // Prototype of receiver is **********
    public function retrive($taskID, $receiver, $args) {
        $buffer = NULL;
        
        $this->currentTask = $taskID;
        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, 0, 0, $taskID, 
            BRIDGE_FLAG_RETRIVE, BRIDGE_FRAME_HEADER_LEN); 

        $ret = $this->BRIDGE_REQUEST($item->message(), $buffer, BRIDGE_RESEND_COUNT); 
        if ($ret == False)
            return False;

        if (!BridgeIsReply($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/retrive: Is not a reply frame.<br>"); 
            return False;  
        }
        
        if (!BridgeIsReadyToSendSet($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/retrive: Worker is not ready to send.<br>"); 
            return BRIDGE_RET_CODE_NOT_READY;
        }

        $ret = $this->Bridge_retrive($receiver, $args);
        if ($ret == True) {
            $this->markJobDone($taskID);
        }
        return $ret;
    }

    public function taskIDAlloc() {
        $stmt = "SELECT * FROM taskID FOR UPDATE";    
        return 1;
    }

    public function isJobReady($taskID) {
        $buffer = NULL;
        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID,
            BRIDGE_FLAG_IS_DONE, BRIDGE_FRAME_HEADER_LEN); 

        $ret = $this->BRIDGE_REQUEST($item->message(), $buffer, BRIDGE_RESEND_COUNT);

        if (!BridgeIsReply($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/isJobReady: Is not a reply frame.<br>");
            return False;
        }

        if (BridgeIsIsJobDoneSet($buffer)) {
            $this->markJobReady($taskID);              
            return True;
        } else {
            return False;
        }
    }
    
    private function markJobReady($taskID) {
        $process = $this->inProcessing; 
        $ready = $this->readyTask;

        $idx = $this->inProcessing.search($taskID);
        if ($idx == -1)
            return False;
        $this->inProcessing->remove($taskID);
        $this->readyTask->add($taskID);
        return True;
    }

    private function markJobDone($taskID) {
        $ready = $this->readyTask; 
        $idx = $ready->search($taskID);
        if ($idx == -1)
            return False;
        $ready->remove($taskID);
        return True;
    }

    public function readyJobs() {
        return $this->readyTask->toArray();
    }

    public function inProcessingJobs() {
        return $this->inProcessing->toArray(); 
    }

    // Return: True  - At least a job is done.
    //         False - None of job is done.
    public function wait() {}

    /* Lower level connection-related function */
    private function Bridge_send($buffer, $length, $flags) {
        return $this->CHANNEL_MAINTAIN('socket_send_wrapper', $buffer, 
            $length, $flags, BRIDGE_RECOVER_RESTART); 
    }

    private function Bridge_recv(&$buffer, $flags) {     
        $headerBuffer = null;
        $contentBuffer = null;

        $nBytes = $this->Bridge_recv_header($headerBuffer, $flags); 
        $valid = Bridge_header_validate($headerBuffer);
        if ($nBytes == False || $valid == False) {
            BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Header error.<br>");
            return False; 
        }
        $header = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $headerBuffer); 
        $length = $header['length'];

        BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Length Field value is " . 
            $length . "<br>");
        $length = $length - BRIDGE_FRAME_HEADER_LEN;

        if ($length == 0) {
            $buffer = $headerBuffer;
            return True;
        }

        $nBytes = $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $contentBuffer,
            $length, $flags, BRIDGE_RECOVER_EXIT); 
        if ($nBytes == False) {
            BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Connection failed.<br>");
            return False; 
        }
        
        BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: " . "Content length is " . 
            strlen($contentBuffer) . "<br>");

        $buffer = $headerBuffer . $contentBuffer;

        BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Frame len is " . 
            strlen($buffer) . "<br>");
        
        return $nBytes + BRIDGE_FRAME_HEADER_LEN; 
    }

    private function Bridge_recv_header(&$buffer, $flags) {
        $len = BRIDGE_FRAME_HEADER_LEN;
        return $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $buffer, 
            $len, $flags, BRIDGE_RECOVER_EXIT); 
    }

    private function Bridge_retrive($receiver, $args) {
        $buffer = null;
        while (True) {
            $this->Bridge_recv($buffer, Null);

            if (BridgeIsTransDoneSet($buffer)) {
                BRIDGE_DEBUG_MSG("Bridge/Bridge_retrive: Transfer done.<br>");
                return True;
            }
            if (BridgeIsTransfer($buffer)) {
                $receiver($args, BridgeContentField($buffer));
            } else {
                BRIDGE_DEBUG_MSG("Bridge/Bridge_retrive: Is not a transfer frame.");
                return False; 
            }
        } 
    }

    private function CHANNEL_MAINTAIN($RTN, &$buffer, $len, $flags, $recover) {
        $again = True;
         
        while ($again) {
            $ret = $RTN($this->socket, $buffer, $len, $flags); 
            if ($ret == False) {
                $again = $this->CHANNEL_REBUILD($recover);
            } else {
                break; 
            }
        }
        if ($ret != False)
            return True;
        return False;
    }

    private function CHANNEL_REBUILD($recover) {
        $count = 0;

        socket_close($this->socket);

        while ($count++ < BRIDGE_CHANNEL_REBUILD_NUM) {
            $this->socket = SocketConnect_TCP($this->address, $this->port); 
            if ($this->socket)
                break;
            sleep(0.1);
        } 

        if ($this->socket == null)
            return false;
        
        if ($recover == BRIDGE_RECOVER_CONTINUE) {
            $buffer = NULL;
            $recoverReq = new BridgeMsg(BRIDGE_TYPE_REQUEST, NULL, NULL, 
                $this->currentTask, BRIDGE_FLAG_RECOVER, BRIDGE_FRAME_HEADER_LEN);
            $ret = $this->Bridge_send($recoverReq->message(),
                $item->length(), NULL);
            if ($ret == False)
                return False;
            if ($this->Bridge_recv($this->socket, $buffer, NULL) == False)
                return False;
            if (!BridgeIsReply($buffer) || !BridgeIsRecoverSet($buffer))
                return False;
        } else if ($recover == BRIDGE_RECOVER_EXIT) {
            return False; 
        } else if ($recover == BRIDGE_RECOVER_RESTART) {
            return True; 
        }
         
        return True;
    }

    private function BRIDGE_REQUEST($beSent, &$received, $retryCount) {
        $count = 0;
        $retry = True; 
        $length = strlen($beSent);

        while ($retry && $count < $retryCount) {
            $retry = !$this->Bridge_send($beSent, $length, 0); 
            $retry = $retry || !$this->Bridge_recv($received);
            
            if ($retry && $count++ != 0)
               sleep(1); 
        }

        return !$retry;
    }
}


