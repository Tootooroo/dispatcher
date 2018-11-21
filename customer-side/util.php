#!/bin/php -q 

<?php

function generic_err($msg) {
    echo $mesg;
}

function socket_err($func) {
    echo $func . " " . "failed: reason: " .  \
        socket_strerror(socket_last_error()) . "\n";
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
        for ($j = $i - 1; $j >= 0 ; $j--) {
            if ($array[$j] > $middle) {
                $array[$j + 1] = $array[$j]; 
                $sorted[$j + 1] = $sorted[$j];
            }
        } 
        $array[$j + 1] = $middle;
        $sorted[$j + 1] = $middle_s;
    }
}

