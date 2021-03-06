<?php

define("HostIdx", 0);
define("PortIdx", 1);

// Worker state constant
define("WORKER_UNKNOWN_STATE", 0);
define("WORKER_FREE_STATE", 1);
define("WORKER_NORMAL_STATE", 2);
define("WORKER_CONGESTED_STATE", 3);
define("WORKER_EMERGENCY_STATE", 4);

// Order constant
define("ORDER_DO", 0);
define("ORDER_MOD", 1);

// Dispatch Method
define("DISPATCH_ROUNDROBIN", 0);
define("DISPATCH_OVERHEAD", 1);

// Protocol Bytes
define("DONE_BYTE", 0x0102);
define("READ_TO_RECV_BYTE", 0x0304);

$workerList = array(
    array("id" => 0, "addr" => "10.5.4.26", "port" => 8013)
);

$database = array(
    "host" => "localhost", "user" => "bridge", "pass" => "12345678", "db" => "Bridge"
);

