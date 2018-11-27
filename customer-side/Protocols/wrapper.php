#!/bin/php -a

<?php

function Bridge_send($socket, $message, $lenShouldSent, $flags) {
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

function Bridge_recv_($socket, &$buffer, &$len, $flags) {

}

function Bridge_recv_header($socket, &$buffer, $flags) {
    $lenShouldRecv = BRIDGE_FRAME_FORMAT;
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

function Bridge_recv($socket, &$buffer, &$len, $flags) {
           
}

