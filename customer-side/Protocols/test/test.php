<?php

include "../definitions.php";
include "../bridge.php";

# Localhost
$address = "127.0.0.1";
# Port
$port = 8813;

$list = new SplDoublyLinkedList();
$list->push(1);
$list->push(2);
$list->rewind();

while ($list->valid()) {
    echo $list->key() . $list->current();
    $list->next();
}


$entry = new BridgeEntry($address, $port);
$ret = $entry->dispatch(1, "1234");
echo "Done";

