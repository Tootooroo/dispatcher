#!/bin/php -q

<?php

error_reporting(E_ALL);

include "dispatcher/customer-side/worker.php"
include "dispatcher/customer-side/util.php"
include "dispatcher/customer-side/config.php"

define("SEQERATOR", ":");

$url = $_POST["url"];
$branch = $_POST["branch"];
$commit = $_POST["commit"];

class Content {
    private $url;
    private $branch;
    private $commit;

    function __construct($url, $branch, $commit) {
    
    }

    public function url() {
        return $this->url; 
    }

    public function setUrl($url_) {
        $this->url = $url_; 
        return TRUE;
    }

    public function branch() {
        return $this->branch; 
    }

    public function setBranch($branch_) {
        $this->branch = $branch_;
       return TRUE; 
    }

    public function commit() {
        return $this->commit; 
    }

    public function setCommit($commit_) {
        $this->commit = $commit;
        return TRUE; 
    }

    public function content() {
        $url = $this->url;
        $branch = $this->branch;
        $commit = $this->commit; 

        return $url . SEPERATOR . $branch . SEPERATOR . $commit; 
    }
}

function receiver(array $args) {

}

$content = new Content($url, $branch, $commit);
$workerHouse = new WorkerHouse(DISPATCH_OVERHEAD, $workerList);
$ID = $workerHouse->dispatch($content);
$receiver = 'receiver';
$target_HOST = null;
$target_bootrom = null;
$args = array("host" => &$target_HOST, "bootrom" => &$target_bootrom);

while (TRUE) {
    if ($workerHouse->retrive($ID, $receiver, $args) == TRUE)
        break;
    sleep(1);
}

echo json_encode($args);





