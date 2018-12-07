<?php

function generic_err($msg) {
    echo $msg;
}

function socket_err($func) {
    echo $func . " " . "failed: reason: " . socket_strerror(socket_last_error()) . "\n";
}

function swap(&$left, &$right) {
    $middle = $left;
    $left = $right;
    $right = $middle;
    return 0;
}

function sortIntoIndex($array) {
    $numOfElem = count($array, COUNT_NORMAL);
    $sorted = array();
    $middle = null;

    for ($i = 0; i < $numOfElem; $i++) {
        array_push($sorted, $i); 
    }
    
    for ($i = 1; i < $numOfElem; $i++) {
        $middle = $array[$i];
        $middle_s = $i;
        for ($j = $i - 1; $j >= 0 && $array[$j] > $middle; $j--) {
                $array[$j + 1] = $array[$j]; 
                $sorted[$j + 1] = $sorted[$j];
        } 
        $array[$j + 1] = $middle;
        $sorted[$j + 1] = $middle_s;
    }
    return $sorted;
}

function SocketConnect_TCP($address, $port) {
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!$socket) {
        generic_err("Unable to create AF_INET socket.<br>");
        socket_close($socket);
        return null;
    }
    $ret = socket_connect($socket, $address, $port);
    if ($ret == false) {
        generic_err("Unable to connect to " . $address . ".<br>"); 
        return null;
    }

    return $socket;
}

