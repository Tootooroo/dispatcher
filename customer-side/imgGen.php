#!/bin/php -q

<?php

error_reporting(E_ALL);

$workers = array(
    "10.5.2.12" => "8012"
);

function socket_err($func) {
    echo $func . " failed: reason: " . socket_strerror(socket_last_error()) . "\n";
}

foreach ($workers as $address => $port) {
    if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false)
        socket_err("socket_create()");
    if (socket_connect($socket, $address, $port) === false) 
        socket_err("socket_connect()");
}





