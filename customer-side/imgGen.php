#!/bin/php -q

<?php

error_reporting(E_ALL);

include "worker.php"
include "util.php"

$workers = array(
    "10.5.2.12" => "8012"
);

$orderList = array(
    "Do" => 0x01,
    "ImportantJob" => 0x02
    "Drop" => 0x03,
    "DropAll" => 0x04,
);


$workerHouse = new WorkerHouse();

foreach ($workers as $address => $port) {

}

// Choose a node by the level of overhead.




