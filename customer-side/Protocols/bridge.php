<?php

include "../wrapper.php";
include "../definitions.php";
include "../../util.php";

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
    private $waitTask;
    private $inProcessing;

    function __construct($address_, $port_) {
        $this->address = $address_;
        $this->port = $port_; 
        $this->socket = SocketConnect_TCP($address_, $port_);
        $this->state = $this->socket == null ? ENTRY_DOWN : ENTRY_UP;

        $this->currentTask = 0;
        $this->waitTask = new splQueue();
        $this->readyTask = new splDoublyLinkedList();
        $this->inProcessing = new splDoublyLinkedList();
    }

    public function dispatch($taskID, $content_) {
        $buffer = null;

        // Frame generation.
        $this->currentTask = $taskID; 
        $this->inProcessing->add($taskID);

        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, 0, 0, $taskID, 
            BRIDGE_FLAG_NOTIFY, $content_);
        $message = $item->message();
        
        $ret = $this->BRIDGE_REQUEST($message, $buffer, BRIDGE_RESEND_COUNT); 
        if ($ret == False)
           return False; 

        if (!BridgeIsReply($buffer) || BridgeIsAcceptSet($buffer))
            return False;
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

        if (!BridgeIsReply($buffer) || !BridgeIsReadyToSendSet($buffer))
           return False;  

        $ret = $this->Bridge_retrive($receiver, $args);
        if ($ret == True) {
            // Remove task from readyTask. 
        }
        return $ret;
    }

    public function taskIDAlloc() {
        $stmt = "SELECT * FROM taskID FOR UPDATE";    
    }

    public function isJobDone($taskID) {
        $buffer = NULL;
        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID,
            BRIDGE_FLAG_IS_DONE, BRIDGE_FRAME_HEADER_LEN); 
        $this->Bridge_send($item->message(), $item->length(), NULL);
        $this->Bridge_recv($buffer, NULL);
        if (!BridgeIsReply($buffer))
            return False;
        if (BridgeIsIsJobDoneSet($buffer)) {
            // Move the task to readyQueue
            return True;
        }
        return False;
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

        $nBytes = $this->Bridge_recv_header($headerBuffer, $flags); 
        $valid = Bridge_header_validate($headerBuffer);
        if ($nBytes == False || $valid == False) {
            return False; 
        }
        $header = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $headerBuffer); 
        $length = $header['length'];

        $length = $length - BRIDGE_FRAME_HEADER_LEN;
        $nBytes = $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $buffer,
            $length, $flags, BRIDGE_RECOVER_EXIT); 
        if ($nBytes == False) {
            return False; 
        }
        return $nBytes + BRIDGE_FRAME_HEADER_LEN; 
    }

    private function Bridge_recv_header(&$buffer, $flags) {
        $len = BRIDGE_FRAME_HEADER_LEN;
        return $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $buffer, 
            $len, $flags, BRIDGE_RECOVER_EXIT); 
    }

    private function Bridge_retrive($receiver, $args) {
        $buffer = null;
        $len = BRIDGE_MAX_SIZE_OF_BUFFER;
        while (True) {
            $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $buffer,
                $len, NULL, BRIDGE_RECOVER_CONTINUE); 
            if (BridgeIsTransfer($buffer)) {
                receiver($args, BridgeContentField($buffer));
            } else if (BridgeIsTransDoneSet($buffer)) {
                return True;
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
        return $ret;
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
        $retry = False; 
        $length = strlen($beSent);

        while ($retry && $count < $retryCount) {
            $retry = !$this->Bridge_send($beSent, $length, 0); 
            $retry = $retry || !$this->Bridge_recv($received);
            
            if ($count++ != 0)
               sleep(1); 
        }

        return !$retry;
    }
}


