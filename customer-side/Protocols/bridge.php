#!/bin/php -q

<?php

include "wrapper.php";
include "definitions.php";
include "../util.php";

class Item {
    private $type;
    private $op;
    private $property;
    private $seqID;
    private $flags;
    private $length; 
    private $content;

    function __construct($type_, $op_, $property_, $seqID_, $flags_, $content_) {
        $this->$type =  $type_; 
        $this->$op = $op_;
        $this->$property = $property_;
        $this->$flags = $flags_;
        $this->$length = BRIDGE_FRAME_HEADER_LEN + strlen($content_);
        $this->$seqID = $seqID_;
        $this->$content = $content_;

        return 0;
    }

    public function flags() {
        return $this->$flags; 
    }

    public function setFlags($flags_) {
        // You may need to check whether is
        // flags value is valid.
        $this->$flags = $flags_; 
        return 0;
    }

    public function seqID() {
        return $this->$seqID; 
    }

    public function setSeqID($seqID_) {
        $this->$seqID = $seqID_; 
        return 0;
    }

    public function type() {
        return $this->$type; 
    }

    public function setType_($type_) {
        $check = $type_ != BRIDGE_TYPE_REQUEST &&
                 $type_ != BRIDGE_TYPE_INFO  &&
                 $type_ != BRIDGE_TYPE_MANAGEMENT;     
        if ($check) 
            return 1; 

        $this->$type = $type_; 
        return 0;
    }     

    public function op() {
        return $this->$op; 
    }

    public function setOp($op_) {
        $check = $op_ != BRIDGE_OP_ENABLE &&
                 $op_ != BRIDGE_OP_DISABLE &&
                 $op_ != BRIDGE_OP_SET; 
        if ($check) 
            return 1;

        $this->$op = $op_;
        return 0;
    }
    
    public function content() {
        return $this->$content; 
    }

    public function setContent($content_) {
        $this->$content = $content_; 
        return 0;
    }

    public function message() {
        $message = pack(BRIDGE_FRAME_FORMAT, $this->$type,
            $this->$op, $this->$property, $this->$seqID, 
            $this->$flags, BRIDGE_FRAME_FORMAT);
        return $message; 
    }
     
    public function length() {
        return $this->$length; 
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
        $this->$address = $address_;
        $this->$port = $port_; 
        $this->$currentTask = 0;
        $this->$waitTask = new splQueue();
        $this->$readyTask = new splDoublyLinkedList();
        $this->$inProcessing = new splDoublyLinkedList();

        $this->$socket = SocketConnect_TCP($address_, $port_);
        $this->$state = $this->$socket == null ? ENTRY_DOWN : ENTRY_UP;
        return 0;
    }

    public function dispatch($taskID, $content_) {
        $buffer = null;
        $len = 0;
        $lenShouldSent = 0;
        
        // Frame generation.
        $this->$currentTask = $taskID; 
        $this->$inProcessing->add($taskID);

        $item = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID, NULL, $content_);
        $message = $item->message();

        Bridge_send($this->$socket, $message, $item->length(), NULL);
        Bridge_recv($this->$socket, $buffer, NULL);  
        if (!BridgeIsReply($buffer)) 
            return FALSE;

        return BridgeIsAccpetSet($buffer);
    } 
 
    // Prototype of receiver is **********
    public function retrive($taskID, $receiver, $args) {
        $buffer = NULL;
        
        $this->$currentTask = $taskID;
        $item = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID, 
            BRIDGE_FLAG_RETRIVE, BRIDGE_FRAME_HEADER_LEN); 

        // fixme: Three handshake may be better for stablility
        //        but Transfer layer provide transfer buffer for us, so it's
        //        not a problem.
        Bridge_send($this->$socket, $item->message(), $item->length(), NULL);
        Bridge_recv($this->$socket, $buffer, NULL);
        if (!BridgeIsReply($buffer) || !BridgeIsReadyToSendSet($buffer))
           return FALSE;  
        $ret = $this->Bridge_retrive($receiver, $args);
        if ($ret == TRUE) {
            // Remove task from readyTask. 
        }
        return $ret;
    }

    public function taskIDAlloc() {
        $stmt = "SELECT * FROM taskID FOR UPDATE";    
    }

    public function isJobDone($taskID) {
        $buffer = NULL;
        $item = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID,
            BRIDGE_FLAG_IS_DONE, BRIDGE_FRAME_HEADER_LEN); 
        Bridge_send($this->$socket, $item->message(), $item->length(), NULL);
        Bridge_recv($this->$socket, $buffer, NULL);
        if (!BridgeIsReply($buffer))
            return FALSE;
        if (BridgeIsIsJobDoneSet($buffer))
            return TRUE;
        return FALSE;
    }
    
    // Return: True  - At least a job is done.
    //         False - None of job is done.
    public function wait() {
        $buffer = NULL;
        
        if ($this->$inProcessing->isEmpty())
            return FALSE;
        if (Bridge_recv($this->$socket, $buffer, NULL))
            return FALSE;
        if (BridgeIsInfo($buffer) && BridgeIsJobDoneSet($buffer)) {
            $this->$readyTask->add(BridgeTaskIDField($buffer)); 
            // Remove task from inProcessing list.

            return TRUE;
        }
    }

    /* Lower level connection-related function */
    private function Bridge_send($buffer, $length, $flags) {
        return $this->CHANNEL_MAINTAIN('socket_send_wrapper', $this->$socket, 
            $buffer, $length, $flags); 
    }

    private function Bridge_recv(&$buffer, $flags) {     
        $headerBuffer = null;

        $nBytes = $this->Bridge_recv_header($this->$socket, $headerBuffer, $flags);             
        if ($nBytes == FALSE || Bridge_header_validate($headerBuffer) == FALSE) {
            return FALSE; 
        }
        $header = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $headerBuffer); 
        $length = $header['length'];
        $length = $length - BRIDGE_FRAME_HEADER_LEN;
        $nBytes = $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $this->$socket, $buffer,
            $length, $flags); 
        if ($nBytes == FALSE) {
            return FALSE; 
        }
        return $nBytes + BRIDGE_FRAME_HEADER_LEN; 
    }

    private function Bridge_recv_header(&$buffer, $flags) {
        $len = BRIDGE_FRAME_HEADER_LEN;
        $rlen = &$len; 
        return $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $this->$socket, $buffer,
            $len, $flags); 
    }

    private function Bridge_retrive($receiver, $args) {
        $buffer = null;
        $len = BRIDGE_MAX_SIZE_OF_BUFFER;
        while (TRUE) {
            $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $this->$socket, $buffer,
                $len, NULL); 
            if (BridgeIsTransfer($buffer)) {
                receiver($args, BridgeContentField($buffer));
            } else if (BridgeIsTransDoneSet($buffer)) 
                return TRUE;
        } 
    }

    private function CHANNEL_MAINTAIN($RTN, $buffer, $len, $flags) {
        do {
            $ret = RTN($this->$socket, $buffer, $len, $flags); 
            if ($ret == FALSE)
                $this->CHANNEL_REBUILD();
        } while ($ret == FALSE);
    }

    private function CHANNEL_REBUILD($taskID) {
        while (TRUE) {
            $this->$socket = SocketConnect_TCP($address, $port); 
            if ($this->$socket)
                break;
        } 
        $buffer = NULL;
        $recoverReq = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, 
            $this->$currentTask, BRIDGE_FLAG_RECOVER, BRIDGE_FRAME_HEADER_LEN);
        $ret = $this->Bridge_send($this->$socket, $recoverReq->message(),
            $item->length(), NULL);
        if ($ret == FALSE)
            return FALSE;
        if ($this->Bridge_recv($this->$socket, $buffer, NULL) == FALSE)
            return FALSE;
        if (!BridgeIsReply($buffer) || !BridgeIsRecoverSet($buffer))
            return FALSE;
        return TRUE;
    }
}


