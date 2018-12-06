#!/bin/php -q

<?php

include "../definitions.php";
include "../bridge.php";

# Localhost
$address = 127.0.0.1;
# Port
$port = 8813;

$entry = new BridgeEntry($address, $port);
$entry->dispatch(1, "1234");


