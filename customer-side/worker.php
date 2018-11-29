#!/bin/php -q

<?php

include "config.php";
include "Protocols/definitions.php";
include "Protocols/wrapper.php";
include "Protocols/bridge.php";

class Job {
    private $jobID;
    private $jobContent; 
    
    function __construct($jobID_, $jobContent_) {
        $this->$jobID = $jobID_;
        $this->$jobContent = $jobContent_;
    }

    function ID() {
        return $this->$jobID; 
    }

    function setID($jobID_) {
        $this->$jobID = $jobID_; 
        return TRUE;
    }

    function content() {
        return $this->$jobContent; 
    }

    function setContent($jobContent_) {
        $this->$jobContent = $jobContent_;
        return TRUE; 
    }
}

class WorkerHouse { 
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
    private $bridgeEnry;     
    private $dbEntry;    

    /* Management Purpose Infos */
    private $ID;
    private $MAX_NUM_OF_JOBS;
    private $NUM_OF_PROCESSING_JOBS;
    private $isListening;

    // 0: Unknow
    // 1: Free
    // 2: Normal
    // 3: Congested
    // 4: Emergency
    private $STATE;

    function __construct($address_, $port_) {
        $this->$bridgeEntry = new BridgeEntry($address_, $port_);
        $ret = $bridgeEntry->connect();
        $this->$isListening = FALSE;
        $ret == ENTRY_DOWN ? 
            $this->$STATE = WORKER_UNKNOWN_STATE :
            $this->$STATE = WORKER_NORMAL_STATE;
        return 0; 
    }

    public function isJobDone($taskID) {
        return $bridgeEntry->isJobDone($taskID);  
    }

    public function jobReceive() { 
        /* Implement it after protocols has been designed. */ 
    }

    public function getID() {
        return $this->$ID; 
    }

    public function overHead() {
        $overHeadSql = "SELECT overhead FROM worker where address = " .
            $this->$address . ";"; 
    
        if ($this->$STATE == WORKER_UNKNOWN_STATE) {
            return -1; 
        } 

        $this->$row = oneRowFetch($overHeadSql, $this->$dbEntry);
        $this->$NUM_OF_PROCESSING_JOBS = $row[1];
        $this->$MAX_NUM_OF_JOBS = $row[2];
        $this->$STATE = $row[3];

        // Overhead calculate
        $overHead = ($this->$NUM_OF_PROCESSING_JOBS / $this->$MAX_NUM_OF_JOBS) 
            * $this->$MAX_NUM_OF_JOBS; 
        return $overHead;
    }
    
    // Return taskID of the job.
    public function doJob($job) {
        $taskID = $this->bridgeEnry->taskIDAlloc(); 
        $content = $job->content();
         
        $bridgeEntry->dispatch($taskID, $content); 
        if ($this->$isListening == FALSE) {
           $this->start(); 
        }
        $this->$isListening = TRUE;

        return $taskID; 
    }
    
    private function run() {
                     
    }

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
        $sqlStmt = "SELECT processing FROM worker where address = " . 
           $this->$address; 
        $this->$NUM_OF_PROCESSING_JOBS = oneRowFetch($sqlStmt, $this->$dbEntry);
        return $this->$NUM_OF_PROCESSING_JOBS;
    }
} 

