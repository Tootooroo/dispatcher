<?php

include_once "config.php";
include "Protocols/definitions.php";
include "Protocols/wrapper.php";
include "Protocols/bridge.php";
include "dataHouse.php";

class WorkerHouse { 
    // 0: Weigthed Round Robin
    // 1: Dispatch by overhead of workers
    private $disMethod;
    private $workers;
    private $wareHouse;
    private $dispatchMethodArrary;
    
    function __construct($disMethod_, $workerSet) {
        $this->disMethod = $disMethod_; 
        
        $this->workers = array();
        $this->wareHouse = array(); 
        
        $this->dispatchMethodArray = array(
            0 => 'rRobinDispatch',
            1 => 'overHeadDispatch' 
        );

        foreach ($workerSet as $worker) {
            $workerInst = new Worker($worker["id"], $worker["addr"], $worker["port"]);
            $this->houseEnter($worker["id"], $workerInst); 
        }
    }

    // Note: If multimaster exists you have better
    // not to use this dispatch method.
    private function rRobinDispatch($job) {
        static $currentIdx = 0;
        $workersRef = $this->workers;
        $theWorker = current($workersRef); 

        next($workersRef); 

        $ret = $theWorker->doJob($job);
        if ($ret != 0) 
            return -1;
        return 0;
    }
         
    private function overheadDispatch($job) {
        $workerRef = $this->workers;
        $overHeadArray = array(); 
        $indexArray = array();
        $numOfWorkers = count($workerRef);
        // First, query overhead of every worker
        foreach ($workerRef as $id => $worker) {
            array_push($overHeadArray, $worker->overHead());
            array_push($indexArray, $id); 
        }
        
        // Second, make decision by the overhead of workers
        $sorted = sortIntoIndex($overHeadArray, $indexArray); 
        // Choose the worker have lowest overhead and useable.
        $choosen = -1;
        foreach ($sorted as $idx) {
            if ($overHeadArray[$idx] == -1)
                continue;
            $choosen = $idx; 
        }  

        // None of worker can handle the job.
        if ($choosen == -1) {
            return null;
        }

        $theWorker = $workerRef[$choosen]; 

        return $theWorker->doJob($job);  
    }

    public function workerConnect($id) {
        if (!array_key_exists($id, $this->workers))
            return False;
        return $this->workers[$id]->connect();
    }

    public function houseEnter($id, $worker) {
        // taskID must be unique
        if (array_key_exists($id, $this->workers))
            return False;
        if ($worker) 
            $this->workers[$id] = $worker; 
        return True;
    }  

    public function houseExit($id) {
        if (!array_key_exists($id, $this->workers))
            return False;
        if (array_key_exists($id, $this->workers))
            $this->workers[$id] = null;
        return True;
    } 

    public function dispatch($job) { 
        $rtn = $this->dispatchMethodArray[$this->disMethod];
        $pair = $this->$rtn($job);
        
        if ($pair == null) {
            return null;
        }

        $wID = $pair['wID'];
        $jID = $pair['jID'];

        if (array_key_exists($jID, $this->wareHouse) == FALSE) {
            $this->wareHouse[$jID] = $wID; 
        } else {
            // This situation must impossible.
            return null; 
        }

        return $jID;
    }
    
    public function info($taskID) {
        $worker = $this->workers[$this->wareHouse[$taskID]]; 
        return $worker->info($taskID);
    }

    public function retrive($taskID, $receiver, $args) {
        $worker = $this->workers[$this->wareHouse[$taskID]];
        return $worker->jobReceive($taskID, $receiver, $args);
    }

    public function dispatcheMethod($method) {
        if ($method == 0 || $method == 1) {
            $this->disMethod = $method; 
            return 0;
        }
        return -1;
    } 
}

class Worker {
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

    function __construct($ID_, $address_, $port_) {
        global $database;
        $this->ID = $ID_;
        // Connect to Worker
        $this->bridgeEntry = new BridgeEntry($address_, $port_);
        $this->isListening = FALSE;
        $ret = $this->bridgeEntry->connectState();
        $ret == ENTRY_DOWN ? 
            $this->STATE = WORKER_UNKNOWN_STATE :
            $this->STATE = WORKER_NORMAL_STATE;
         
        // Connect to database
        $this->dbEntry = mysqli_connect($database["host"], $database["user"],
            $database["pass"], $database["db"]); 
        if (mysqli_connect_errno($this->dbEntry)) {
            echo "Failed to connect to database: " . mysqli_connect_error();
            exit; 
        }
        return 0; 
    }
    
    public function connect() {
        $this->bridgeEntry->connect(); 
        if ($this->bridgeEntry->connectState() == ENTRY_DOWN) {
            $this->STATE = WORKER_UNKNOWN_STATE;
            return False;
        } else {
            $this->STATE = WORKER_NORMAL_STATE;
            return True;
        }
    }
    
    public function info($taskID) {
        return $this->bridgeEntry->info($taskID); 
    }

    public function isJobReady($taskID) {
        return $this->bridgeEntry->isJobReady($taskID); 
    }

    public function jobReceive($taskID, $receiver, $args) { 
        return $this->bridgeEntry->retrive($taskID, $receiver, $args);
    }

    public function getID() {
        return $this->ID; 
    }

    public function overHead() {
        $overHeadSql = "SELECT MAX, inProc, pending FROM overHead WHERE WORKER_ID = " . $this->ID; 

        if ($this->STATE == WORKER_UNKNOWN_STATE) {
            return -1; 
        } 

        $row = sqlOneRowFetch($overHeadSql, $this->dbEntry);
        $this->NUM_OF_PROCESSING_JOBS = $row['inProc'];
        $this->MAX_NUM_OF_JOBS = $row['MAX'];
        
        // Overhead calculate
        $overHead = ($this->NUM_OF_PROCESSING_JOBS / $this->MAX_NUM_OF_JOBS) 
            * $this->MAX_NUM_OF_JOBS; 
        return $overHead;
    }
    
    // Return taskID of the job.
    public function doJob($job) {
        $taskID = $this->bridgeEntry->taskIDAlloc(); 
        if ($this->bridgeEntry->dispatch($taskID, $job) === FALSE)
            $taskID = -1; 
        return array('wID' => $this->ID, 'jID' => $taskID); 
    }
    
    public function state() { 
        return $this->STATE; 
    }

    public function setState($stateCode) {
        $this->STATE = $stateCode; 
        return 0;
    }

    public function getProcessingJobs() {
        $sqlStmt = "SELECT inProc FROM overHead where ID = " . 
           $this->ID; 
        $row = sqlOneRowFetch($sqlStmt, $this->dbEntry);
        $this->NUM_OF_PROCESSING_JOBS = $row[0];
        return $this->NUM_OF_PROCESSING_JOBS;
    }
} 

