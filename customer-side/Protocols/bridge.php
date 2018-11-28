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

    function __construct($address_, $port_) {
        $this->$address = $address_;
        $this->$port = $port_; 

        $this->$socket = SocketConnect_TCP($address_, $port_);
        $this->$state = $this->$socket == null ? ENTRY_DOWN : ENTRY_UP;
        return 0;
    }

    public function connect() {
        return $this->reconnect(); 
    }

    public function reconnect() {
        if ($this->$state == ENTRY_UP)
            socket_close($this->$socket);
        $this->$socket = SocketConnect_TCP($this->$address, $this->$port); 
        $ret = $this->$socket == null;
        $this->$state = $ret ? ENTRY_DOWN : ENTRY_UP;
        return $ret;
    }

    public function dispatch($taskID, $content_) {
        $buffer = null;
        $len = 0;
        $lenShouldSent = 0;
        
        // Frame generation.
        $item = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID, NULL, $content_);   
        $message = $item->message();

        Bridge_send($this->$socket, $message, $item->length(), NULL);
        Bridge_recv($this->$socket, $buffer, NULL);  
        if (!BridgeIsReply($buffer)) 
            return FALSE;

        return BridgeIsAccpetSet($buffer);
    } 
    
    // Prototype of receiver is **********
    private function retrive($taskID, $receiver, $args) {
        $buffer = NULL;

        $item = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID, 
            BRIDGE_FLAG_RETRIVE, BRIDGE_FRAME_HEADER_LEN); 

        // fixme: Three handshake may be better for stablility
        //        but Transfer layer provide transfer buffer for us, so it's
        //        not a problem.
        Bridge_send($this->$socket, $item->message(), $item->length(), NULL);
        Bridge_recv($this->$socket, $buffer, NULL);
        if (!BridgeIsReply($buffer) || !BridgeIsReadyToSendSet($buffer))
           return FALSE; 
        $ret = Bridge_retrive($this->$socket, $receiver, $args); 
        return $ret;
    }

    private function isJobDone($taskID) {
        $buffer = NULL;
        $item = new Item(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID,
            BRIDGE_FLAG_IS_DONE, BRIDGE_FRAME_HEADER_LEN); 
        Bridge_send($this->$socket, $item->message(), $item->length(), NULL);
        Bridge_recv($this->$socket, $buffer, NULL);
        if (!BridgeIsReply($buffer))
            return FALSE;
        if (BridgeIsJobDoneSet($buffer))
            return TRUE;
        return FALSE;
    }
}

function bridgeEntry_Wait(array &$read) {
    $buffer = null;
    $nReady = 0;
    $flags = MSG_PEEK | MSG_DONTWAIT;
    if ($read == null)
        return 0;
    foreach ($read as $entry) {
        $nBytes = $entry->isReady();
        if ($nBytes != 0 && $nBytes != false) {
            ++$nReady; 
        }
    }
    return $nReady;
}



