#!/bin/php -q

<?php

include "definitions.php"
include "../util.php"

define("ENTRY_UP", 1);
define("ENTRY_DOWN", 0);
define("BIN_FORMAT", "vvvA*"); 

class Item {
    private $op;
    private $cate;
    private $priority;
    private $content;
    
    function __construct($op_, $cate_, $priority_, $content_) {
        $this->setOp($op_);  
        $this->setCate($cate_);
        $this->setPri($priority);
        $this->setContent(($content_);
        return 0;
    }

    public function getOp() {
        return $this->$op; 
    }

    public function setOp($op_) {
        $check = op_ != BRIDGE_OP_DISPATCH &&
                 op_ != BRIDGE_OP_DELIVER  &&
                 op_ != BRIDGE_OP_CONTROL;     
        if ($check) 
            return 1; 

        $this->$op = $op_; 
        return 0;
    }     

    public function getCate() {
        return $this->$cate; 
    }

    public function setCate($cate_) {
        $check = $cate_ != BRIDGE_CATE_DEFAULT &&
                 $cate_ != BRIDGE_CATE_PROPERTY; 
        if ($check) 
            return 1;

        $this->$cate = $cate_;
        return 0;
    }
    
    public function getPri() {
        return $this->$priority; 
    }

    public function setPri($pri) {
        if ($pri < 0 || pri > 100)
            return 1;
        $this->$priority = $pri;
        return 0;
    }

    public function getContent() {
        return $this->$cate; 
    }

    public function setContent($content_) {
        $this->$content = $content_; 
        return 0;
    }

    public function message() {
        if ($this->$op == BRIDGE_OP_DISPATCH) { } else if ($this->$op == BRIDGE_OP_) 
        $message = pack(BIN_FORMAT, $this->$op, $this->$cate,
            $this->$priority, $this->$content); 
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
        $item = new Item(BRIDGE_OP_DISPATCH, BRIDGE_CATE_DEFAULT, 0, $content_);   
        $message = $item->message();
        $ret = socket_send($socket, $message, strlen($message), NULL);
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

