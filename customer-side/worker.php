#!/bin/php -q

<?php

include "config.php";

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
        $this->$order = $type_;     
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

class WorkerHouse extends Thread { 
    // 0: Weigthed Round Robin
    // 1: Dispatch by overhead of workers
    private $disMethod;
    private $workers;
    private $wareHouse;
    private $dispatchMethodArrary = array(
        0 => 'rRobinDispatch',
        1 => 'overHeadDispatch' 
    );
    
    function __construct($disMethod_, $workerSet) {
        $this->$disMethod = $disMethod_; 
        
        $workers = new splDoublyLinkedList();
        $wareHouse = new splDoublyLinkedList();

        foreach ($workerSet as $worker) {
            $worker = new Worker($worker[HostIdx], $worker[PortIdx]);
            $this->houseEnter($worker); 
            if ($worker->connect()) {
                // Connect error set the state of worker to unknow
                $worker->setState(WORKER_UNKNOWN_STATE); 
            }
        }
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
        // Choose the worker have lowest overhead and useable.
        $choosen = -1;
        foreach ($sorted as $idx) {
            if ($overheadArray[$idx] == -1)
                continue;
            $choosen = $idx; 
        }  

        // None of worker can handle the job.
        if ($choosen == -1)
            return -1;
        $theWorker = $workersRef->offsetGet($choosen); 
        
        $ret = $theWorker->doJob($job);  
        if ($ret != 0)
           return -1; 
        return 0;
    }

    public function run() {
        $count = 0;
        $iter = $this->$workers;

        // Iterating over all the workers in the house
        // and check job ready state of each of it. 
        do {
            if ($iter->current()->isJobDone()) {
                $iter->current()->start();  
            }
            if (($count++ % 20) == 0)
                sleep(1);
        } while($iter->next())
    }

    public function houseEnter($worker) {
        if ($worker) {
            $this->$workers.add(0, $worker);  
        }
    }  

    public function houseExit($address) {
        $begin = $this->$workers->current();
        $iter = $this->$workers;
        while ($iter->current().addr() != $address) {
            $iter->next(); 
            if ($iter->current == $begin)
                return -1;
        }            
        $iter.offsetUnset($iter->key());
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

class Worker extends Thread {
    /* Connection */
    private $address;
    private $port;
    private $socket;
    private $dbConn;  

    /* Management Purpose Infos */
    private $ID;
    private $MAX_NUM_OF_JOBS;
    private $NUM_OF_PROCESSING_JOBS;
    
    // 0: Unknow
    // 1: Fre
    // 2: Normal
    // 3: Congested
    // 4: Emergency
    private $STATE;

    function __construct($address_, $port_) {
        $this->$address = $address_;
        $this->$port = $port_;
    }

    public function connect() {
        $ret = 0;
        if ($ret = $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) === false) {
            socket_err("socket_create()");
            return $ret;
        }
        if ($ret = socket_connect($this->$socket, $this->$address, $this->$port) === false) {
            socket_err("socket_connect()"); 
            return $ret;
        }
        return $ret;
    }

    public function isJobDone() {
        $buf = ' ';
        $ret = socket_recv($this->$socket, $buf, 2, MSG_DONTWAIT); 
        if ($ret <= 0)
            return false;
        return (int)$buf == DONE_BYTES;
    }

    public function jobReceive() { // Implement it after protocols has been designed. }

    public function getID() {
        return $this->$ID; 
    }

    public function overHead() {
<<<<<<< HEAD
        $overHeadSql = "SELECT overhead FROM worker where address = " . $this->$address . ";"; 
=======
        $overHeadSql = "SELECT overhead FROM worker where address = " .
            $this->$address . ";"; 
>>>>>>> 0c0680ed70d2af4f6d70094f295ab81631363249
    
        if ($this->$STATE == WORKER_UNKNOWN_STATE) {
            return -1; 
        } 

        $this->$row = oneRowFetch($overHeadSql, $this->$dbConn);
        $this->$NUM_OF_PROCESSING_JOBS = $row[1];
        $this->$MAX_NUM_OF_JOBS = $row[2];
        $this->$STATE = $row[3];

        // Overhead calculate
<<<<<<< HEAD
        $overHead = ($this->$NUM_OF_PROCESSING_JOBS / $this->$MAX_NUM_OF_JOBS) * $this->$MAX_NUM_OF_JOBS; 
=======
        $overHead = ($this->$NUM_OF_PROCESSING_JOBS / $this->$MAX_NUM_OF_JOBS) 
            * $this->$MAX_NUM_OF_JOBS; 
>>>>>>> 0c0680ed70d2af4f6d70094f295ab81631363249
        return $overHead;
    }

    public function doJob($job) {}

    public function state() { 
        return $this->$STATE; 
    }

    public function setState($stateCode) {
        $this->$STATE = $stateCode; 
        return 0;
    }

    public function getMaxNumOfJobs() {
        return $this->$MAX_NUM_OF_JOBS; 
    }

    public function setMaxNumOfJobs($num) {
        $this->$MAX_NUM_OF_JOBS = $num;
        $job = new Job("mod", strval($num), 0);
        // fixme: should send command via protocols 
    }

    public function getProcessingJobs() {
<<<<<<< HEAD
        $sqlStmt = "SELECT processing FROM worker where address = " . $this->$address; 
=======
        $sqlStmt = "SELECT processing FROM worker where address = " . 
           $this->$address; 
>>>>>>> 0c0680ed70d2af4f6d70094f295ab81631363249
        $this->$NUM_OF_PROCESSING_JOBS = oneRowFetch($sqlStmt, $this->$dbConn);
        return $this->$NUM_OF_PROCESSING_JOBS;
    }
} 

