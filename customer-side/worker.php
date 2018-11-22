#!/bin/php -q

<?php

include "config.php"

class Job {
    private $order;
    private $content;
    private $priority;

    function __construct($order_, $content_, $pri_) {
        $this->$order = $order_; 
        $this->$content = $content_;
        $this->$priority = $pri_; 
    }

    public function getOrd() {
        return $this->$order; 
    }

    public function setOrd($type_) { 
        $this->$order = $orderType[$type_];     
    }

    public function getContent() {
        return $this->$content; 
    }

    public function getPri() {
        return $this->$priority; 
    }    

    public function setPri($pri) {
        $this->$priority = $pri; 
        return 0;
    }

    public function setContent($content_) { 
        $this->$content = $content_; 
    }

    public function jobStr() { 
        $jobStr = $pack("vvH*", $this->$order, $this->$priority, $this->$content_); 
        return $jobStr;
    }
}

class WorkerHouse { 
    // 0: Weigthed Round Robin
    // 1: Dispatch by overhead of workers
    private $disMethod;
    private $workers = new splDoublyLinkedList();
    private $dispatchMethodArrary = array(
        0 => 'rRobinDispatch',
        1 => 'overHeadDispatch' 
    );
    
    function __construct($disMethod_) {
        $this->$disMethod = $disMethod_; 
    }

    // Note: If multimaster exists you have better
    // not to use this dispatch method.
    private function rRobinDispatch($job) {
        static $currentIdx = 0;
        $workersRef = $this->$workers;
        $numOfWorkers = $workersRef->count();
        
        $theWorker = $workerRef->offsetGet($currentIdx++);   
        if ($currentIdx > $numOfWorkers)
            $currentIdx = 0;
        $ret = $theWorker->doJob($job);
        if ($ret != 0) 
            return -1;
        return 0;
    }
         
    private function overheadDispatch($job) {
        $workerRef = $this->$workers;
        $overheadArray = array(); 
        $numOfWorkers = $workersRef->count();

        // First, query overhead of every worker
        for ($idx = 0; $idx < $numOfWorkers; $idx++) {
            $theWorker = $workerRef->offsetGet($idx);
            array_push($overheadArray, $theWorker->overHead()); 
        }

        // Second, make decision by the overhead of workers
        $sorted = sortIntoIndex($overheadArray); 
        $theWorker = $workersRef->offsetGet($sorted[0]); 
        
        $ret = $theWorker->doJob($job);  
        if ($ret != 0)
           return -1; 
        return 0;
    }

    public function houseEnter($worker) {
        if ($worker) 
            $this->$workers->add(0, $worker);  
    }  

    public function houseExit($address) {
        $begin = $this->$workers->current();
        $iter = $this->$workers;
        while ($iter.current().addr() != $address) {
            $iter.next(); 
            if ($iter.current == $begin)
                return -1;
        }            
        $iter.offsetUnset($iter.key());
        return 0;
    } 

    public function dispatch($job) {
        return $this->$dispatchMethodArrary[$disMethod]($job);        
    }

    public function dispatcheMethod($method) {
        if ($method == 0 || $method == 1) {
            $this->$disMethod = $method; 
            return 0;
        }

        return -1;
    } 
    public function workerInfo($wID) {
    
    }
}

class Worker {
    /* Connection */
    private $address;
    private $port;
    private $socket;
    private $dbConn;  

    /* Management Purpose Infos */
    private $ID;
    private $MAX_NUM_OF_JOBS;
    private $NUM_OF_PROCESSING_JOBS;
    // 0: Free
    // 1: Normal
    // 2: Congested
    // 3: Emergency
    private $STATE;

    function __construct($address_, $port_) {
        $this->$address = $address_;
        $this->$port = $port_;
    }

    private function command($jobStr) { 
       socket_write($this->$socket, $jobStr); 
    }

    public function connect() {
        if ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) === false)
            socket_err("socket_create()");
        if (socket_connect($this->$socket, $this->$address, $this->$port) === false)
            socket_err("socket_connect()"); 
    }

    public function getID() {
        return $this->$ID; 
    }

    public function overHead() {
        $overHeadSql = "SELECT overhead FROM worker where address = " . \
            $this->$address . ";"; 
        $this->$row = oneRowFetch($overHeadSql, $this->$dbConn);
        $this->$NUM_OF_PROCESSING_JOBS = $row[1];
        $this->$MAX_NUM_OF_JOBS = $row[2];
        $this->$STATE = $row[3];

        // Overhead calculate
        $overHead = ($this->$NUM_OF_PROCESSING_JOBS / $this->$MAX_NUM_OF_JOBS) \
            * $this->$MAX_NUM_OF_JOBS; 
        return $overHead;
    }

    public function doJob($job) {
        return command($job->jobStr());          
    }

    public function state() { 
        return $this->$STATE; 
    }

    public function getMaxNumOfJobs() {
        return $this->$MAX_NUM_OF_JOBS; 
    }

    public function setMaxNumOfJobs($num) {
        $this->$MAX_NUM_OF_JOBS = $num;
        $job = new Job("mod", strval($num), 0);
        return command($job->jobStr());
    }

    public function getProcessingJobs() {
        $sqlStmt = "SELECT processing FROM worker where address = " . \
           $this->$address; 
        $this->$NUM_OF_PROCESSING_JOBS = oneRowFetch($sqlStmt, $this->$dbConn);
        return $this->$NUM_OF_PROCESSING_JOBS;
    }
} 

