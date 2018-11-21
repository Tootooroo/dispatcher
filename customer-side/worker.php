#!/bin/php -q

<?php

class Job {
    private $order;
    private $content;
    private $priority;

    public function setOrd($type_) { 
    
    }

    public function setContent($content_) { 
    
    }

    public function jobStr() { 
    
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
    
}

class Worker {
    /* Connection */
    private $address;
    private $port;
    private $socket;
    private $dbConn;  

    /* Management Purpose Infos */
    private $MAX_NUM_OF_JOBS;
    private $NUM_OF_PROCESSING_JOBS;
    // 0: Free
    // 1: Normal
    // 2: Congested
    // 3: Emergency
    private $STATE;

    private function command($jobStr) { 
       socket_write($this->$socket, $jobStr); 
    }

    public function connect() {
        if ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) === false)
            socket_err("socket_create()");
        if (socket_connect($this->$socket, $this->$address, $this->$port) === false)
            socket_err("socket_connect()"); 
    }

    public function overHead() {
        $overHeadSql = "SELECT overhead FROM loadTable where address = " . \
            $address . ";"; 
        $this->$row = oneRowFetch($this->$overHeadSql, $this->$dbConn);
        $this->$NUM_OF_PROCESSING_JOBS = $row[1];
        $this->$MAX_NUM_OF_JOBS = $row[2];
        $this->$STATE = $row[3];

        // Overhead calculate
        $overHead = ($this->$NUM_OF_PROCESSING_JOBS / $this->$MAX_NUM_OF_JOBS) \
            * $this->$MAX_NUM_OF_JOBS; 
        return $overHead;
    }

    public function doJob($job) {
        command($job->jobStr());  
    }

    public function state() { 
        return $this->$STATE; 
    }
} 

