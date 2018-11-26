#!/bin/php -q

<?php

include "definitions.php";
include "../util.php";

// Use to seperate request-reply pair
$SEQID = 0;

function seqIDAlloc() {
    $allocID = $SEQID;
    if ($SEQID < 255)
        $SEQID = $SEQID + 1;
    else
        $SEQID = 0;
    return $allocID;
}

class Item {
    private $type;
    private $op;
    private $content;
    private $flags;
    private $length; 

    function __construct($type_, $op_, $property_, $flags_, $content_) {
        $this->$type =  $type_; 
        $this->$op = $op_;
        $this->$property = $property_;
        $this->$flags = $flags_;
        $this->$content = $content_;
        $this->$length = BRIDGE_FRAME_HEADER_LEN;

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
            $this->$op, $this->$property, seqIDAlloc(), 
            $this->$flags, BRIDGE_FRAME_FORMAT);
        return $message; 
    }
}

class BridgeEntry {
    private $state;
    private $socket;  
    private $address;
    private $port;
    private $buffer;
    private $bufferSize;

    function __construct($address_, $port_, $bufferSize) {
        $this->$address = $address_;
        $this->$port = $port_; 
        $this->$bufferSize = $bufferSize;
        
        $this->$socket = SocketConnect_TCP($address_, $port_);
        $this->$state = $this->$socket == null ? ENTRY_DOWN : ENTRY_UP;
        return 0;
    }

    public function reconnect() {
        if ($this->$state == ENTRY_UP)
            socket_close($this->$socket);
        $this->$socket = SocketConnect_TCP($this->$address, $this->$port); 
        $ret = $this->$socket == null;
        $this->$state = $ret ? ENTRY_DOWN : ENTRY_UP;
        return $ret;
    }

    public function dispatch($content_) {
        $len = 0;
        $lenShouldSent = 0;

        $item = new Item(BRIDGE_OP_DISPATCH, BRIDGE_CATE_DEFAULT, 0, $content_);   
        $message = $item->message();
        
        Bridge_send($this->$socket, $message, $strlen(message), NULL);
        $ret = socket_recv($socket, $message, $len, NULL);
    } 

    private function recv_($len, $flags) {
        if ($this->$state == ENTRY_DOWN) {
            generic_err("This Entry is not ready to receive data."); 
        }
        $nBytes = socket_recv($socket, $this->$buffer, $this->$bufferSize, $flags); 
        return $nBytes;
    }

    // Data receiving
    public function recv() {
        $flags = 0;                              
        return $this->recv_($this->$bufferSize, $flags);
    }

    // Data receiving without blocked
    public function tryRecv() {
        $flags = MSG_DONTWAIT; 
        return $this->recv_($this->$bufferSize, $flags);
    }

    public function isReady() {
        $flags = MSG_DONTWAIT | MSG_PEEK; 
        return $this->recv_(1, $flags);
    }

    public function data() {
        return $this->$buffer; 
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

