<?php

include_once "wrapper.php";
include_once "definitions.php";
include_once "../dispatcher/customer-side/config.php";
include_once "../dispatcher/customer-side/util.php";

define("BRIDGE_RET_CODE_NOT_READY", 3);

class BridgeMsg {
    private $type;
    private $op;
    private $property;
    private $seqID;
    private $flags;
    private $length; 
    private $content;

    function __construct($type_, $op_, $property_, $seqID_, $flags_, $content_) {
        $this->type =  $type_; 
        $this->op = $op_;
        $this->property = $property_;
        $this->flags = $flags_;
        $this->length = BRIDGE_FRAME_HEADER_LEN + strlen($content_);
        $this->seqID = $seqID_;
        $this->content = $content_;

        return 0;
    }

    public function flags() {
        return $this->flags; 
    }

    public function setFlags($flags_) {
        // You may need to check whether is
        // flags value is valid.
        $this->flags = $flags_; 
        return 0;
    }

    public function seqID() {
        return $this->seqID; 
    }

    public function setSeqID($seqID_) {
        $this->seqID = $seqID_; 
        return 0;
    }

    public function type() {
        return $this->type; 
    }

    public function setType_($type_) {
        $check = $type_ != BRIDGE_TYPE_REQUEST &&
                 $type_ != BRIDGE_TYPE_INFO  &&
                 $type_ != BRIDGE_TYPE_MANAGEMENT;     
        if ($check) 
            return 1; 

        $this->type = $type_; 
        return 0;
    }     

    public function op() {
        return $this->op; 
    }

    public function setOp($op_) {
        $check = $op_ != BRIDGE_OP_ENABLE &&
                 $op_ != BRIDGE_OP_DISABLE &&
                 $op_ != BRIDGE_OP_SET; 
        if ($check) 
            return 1;

        $this->op = $op_;
        return 0;
    }
    
    public function content() {
        return $this->content; 
    }

    public function setContent($content_) {
        $this->content = $content_; 
        return 0;
    }

    public function message() {
        $message = pack(BRIDGE_FRAME_FORMAT_PACK, $this->type,
            $this->op, $this->property, $this->seqID, 
            $this->flags, $this->length, $this->content);
        return $message; 
    }
     
    public function length() {
        return $this->length; 
    }
}

class BridgeEntry {
    private $state;
    private $socket;  
    private $address;
    private $port;
    private $dbEntry;

    // The ID of task that in processing.
    private $currentTask; 
    private $readyTask;
    private $inProcessing;

    function __construct($address_, $port_) {
        global $database;

        $this->address = $address_;
        $this->port = $port_; 
        $this->socket = Null;
        $this->state = ENTRY_DOWN;
        $this->currentTask = 0;
        $this->readyTask = new BridgeList();
        $this->inProcessing = new BridgeList();

        $dbEntry = new mysqli($database['host'], $database['user'],
            $database['pass'], $database['db']);
        if ($dbEntry->connect_errno) {
            echo "Failed to connect to Mysql: " . $dbEntry->connect_errno . " - " .
                $dbEntry->connect_error . ".\n";
            exit(1);
        }
        $this->dbEntry = $dbEntry;
    }

    public function connect() { 
        $this->socket = SocketConnect_TCP($this->address, $this->port);
        if ($this->socket == null) {
            $this->state = ENTRY_DOWN; 
            return False;
        } else {
            $this->state = ENTRY_UP;
            return True; 
        }
    }

    public function connectState() {
        return $this->state;
    }

    public function dispatch($taskID, $content_) {
        $buffer = null;

        // Frame generation.
        $this->currentTask = $taskID; 

        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, 0, 0, $taskID, 
            BRIDGE_FLAG_NOTIFY, $content_);
        $message = $item->message();

        $ret = $this->BRIDGE_REQUEST($message, $buffer, BRIDGE_RESEND_COUNT); 
        if ($ret === False) {
            echo "BRIDGE_REQUEST Failed.";
           return False; 
        }

        if (!BridgeIsReply($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/dispatch: Is not a reply frame.\n");
            return False;
        }
        
        if (!BridgeIsAcceptSet($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/dispatch: Dispatch is not accepted.\n");
            return False;
        }
        $this->inProcessing->add($taskID);
        return True;
    } 

    public function info($taskID) {
        $frame_recv = null; 
        
        $this->currentTask = $taskID;
        $item = new BridgeMsg(BRIDGE_TYPE_INFO, 0, 0, $taskID, BRIDGE_FLAG_NOTIFY, "");
        $frame = $item->message();
        $ret = $this->BRIDGE_REQUEST($frame, $frame_recv, BRIDGE_RESEND_COUNT, ""); 
        if ($ret === False) {
            echo "BRIDGE_REQUEST() Failed."; 
            return False;
        }

        if (!BridgeIsInfo($frame_recv)) {
            BRIDGE_DEBUG_MSG("Bridge/info: Is not a info frame.\n"); 
            return False;
        }
        
        if (BridgeIsDeclineSet($frame_recv)) {
            BRIDGE_DEBUG_MSG("Bridge/info: info is not ready yet.\n"); 
            return BRIDGE_RET_CODE_NOT_READY;
        }

        if (BridgeIsJobDoneSet($frame_recv)) {
            return True;   
        }
        
        $content = BridgeContentField($frame_recv); 
        return $content;
    }

    // Prototype of receiver is **********
    public function retrive($taskID, $receiver, $args) {
        $buffer = NULL;
        
        $this->currentTask = $taskID;
        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, 0, 0, $taskID, 
            BRIDGE_FLAG_RETRIVE, ""); 

        $ret = $this->BRIDGE_REQUEST($item->message(), $buffer, BRIDGE_RESEND_COUNT); 
        if ($ret == False)
            return False;

        if (!BridgeIsReply($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/retrive: Is not a reply frame.\n"); 
            return False;  
        }
        
        if (!BridgeIsReadyToSendSet($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/retrive: Worker is not ready to send.\n"); 
            return BRIDGE_RET_CODE_NOT_READY;
        }

        $ret = $this->Bridge_retrive($receiver, $args);
        if ($ret == True) {
            $this->markJobDone($taskID);
        }
        return $ret;
    }

    public function taskIDAlloc() {
        $taskID = 0;
        $seed = 0;
        $dbEntry = $this->dbEntry;

        $dbEntry->autocommit(False);

        $result = $dbEntry->query("SELECT seed FROM idSeed FOR UPDATE");
        if ($result === False) {
            echo "SELECT for idSeed failed.\n"; 
            exit(1);
        }

        $row = $result->fetch_assoc();
        $seed = $row['seed'];
        $taskID = $seed;
        
        $seed += 1;
        if ($seed > BRIDGE_MAXIMUM_TASK_ID) 
            $taskID = 0; 

        if ($dbEntry->query("UPDATE idSeed SET seed = " . $seed) === False) {
            echo "UPDATE for idSeed failed.\n"; 
            exit(1);
        }

        if ($dbEntry->commit() === False) {
            echo "Commit failed.\n"; 
            exit(1);
        }

        $dbEntry->autocommit(True);

        return $taskID;
    }

    public function isJobReady($taskID) {
        $buffer = NULL;
        $item = new BridgeMsg(BRIDGE_TYPE_REQUEST, NULL, NULL, $taskID,
            BRIDGE_FLAG_IS_DONE, BRIDGE_FRAME_HEADER_LEN); 

        $ret = $this->BRIDGE_REQUEST($item->message(), $buffer, BRIDGE_RESEND_COUNT);

        if (!BridgeIsReply($buffer)) {
            BRIDGE_DEBUG_MSG("Bridge/isJobReady: Is not a reply frame.\n");
            return False;
        }

        if (BridgeIsIsJobDoneSet($buffer)) {
            $this->markJobReady($taskID);              
            return True;
        } else {
            return False;
        }
    }
    
    private function markJobReady($taskID) {
        $process = $this->inProcessing; 
        $ready = $this->readyTask;

        $idx = $this->inProcessing.search($taskID);
        if ($idx == -1)
            return False;
        $this->inProcessing->remove($taskID);
        $this->readyTask->add($taskID);
        return True;
    }

    private function markJobDone($taskID) {
        $ready = $this->readyTask; 
        $idx = $ready->search($taskID);
        if ($idx == -1)
            return False;
        $ready->remove($taskID);
        return True;
    }

    public function readyJobs() {
        return $this->readyTask->toArray();
    }

    public function inProcessingJobs() {
        return $this->inProcessing->toArray(); 
    }

    // Return: True  - At least a job is done.
    //         False - None of job is done.
    public function wait() {}

    /* Lower level connection-related function */
    private function Bridge_send($buffer, $length, $flags) {
        return $this->CHANNEL_MAINTAIN('socket_send_wrapper', $buffer, 
            $length, $flags, BRIDGE_RECOVER_RESTART); 
    }

    private function Bridge_recv(&$buffer, $flags) {     
        $headerBuffer = null;
        $contentBuffer = null;

        $nBytes = $this->Bridge_recv_header($headerBuffer, $flags); 
        $valid = Bridge_header_validate($headerBuffer);
        if ($nBytes == False || $valid == False) {
            BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Header error.\n");
            return False; 
        }
        $header = unpack(BRIDGE_FRAME_FORMAT_UNPACK, $headerBuffer); 
        $length = $header['length'];

        BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Length Field value is " . 
            $length . "\n");
        $length = $length - BRIDGE_FRAME_HEADER_LEN;

        if ($length == 0) {
            $buffer = $headerBuffer;
            return True;
        }

        $nBytes = $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $contentBuffer,
            $length, $flags, BRIDGE_RECOVER_EXIT); 
        if ($nBytes == False) {
            BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Connection failed.\n");
            return False; 
        }
        
        BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: " . "Content length is " . 
            strlen($contentBuffer) . "\n");

        $buffer = $headerBuffer . $contentBuffer;

        BRIDGE_DEBUG_MSG("Bridge/Bridge_recv: Frame len is " . 
            strlen($buffer) . "\n");
        
        return $nBytes + BRIDGE_FRAME_HEADER_LEN; 
    }

    private function Bridge_recv_header(&$buffer, $flags) {
        $len = BRIDGE_FRAME_HEADER_LEN;
        return $this->CHANNEL_MAINTAIN('socket_recv_wrapper', $buffer, 
            $len, $flags, BRIDGE_RECOVER_EXIT); 
    }

    private function Bridge_retrive($receiver, $args) {
        $buffer = null;
        while (True) {
            $this->Bridge_recv($buffer, Null);

            if (BridgeIsTransDoneSet($buffer)) {
                BRIDGE_DEBUG_MSG("Bridge/Bridge_retrive: Transfer done.\n");
                return True;
            }
            if (BridgeIsTransfer($buffer)) {
                $receiver($args, BridgeContentField($buffer));
            } else {
                BRIDGE_DEBUG_MSG("Bridge/Bridge_retrive: Is not a transfer frame.");
                return False; 
            }
        } 
    }

    private function CHANNEL_MAINTAIN($RTN, &$buffer, $len, $flags, $recover) {
        $again = True;
         
        while ($again) {
            $ret = $RTN($this->socket, $buffer, $len, $flags); 
            if ($ret == False) {
                $again = $this->CHANNEL_REBUILD($recover);
            } else {
                break; 
            }
        }
        if ($ret != False)
            return True;
        return False;
    }

    private function CHANNEL_REBUILD($recover) {
        $count = 0;

        socket_close($this->socket);

        while ($count++ < BRIDGE_CHANNEL_REBUILD_NUM) {
            $this->socket = SocketConnect_TCP($this->address, $this->port); 
            if ($this->socket)
                break;
            sleep(0.1);
        } 

        if ($this->socket == null)
            return false;
        
        if ($recover == BRIDGE_RECOVER_CONTINUE) {
            $buffer = NULL;
            $recoverReq = new BridgeMsg(BRIDGE_TYPE_REQUEST, NULL, NULL, 
                $this->currentTask, BRIDGE_FLAG_RECOVER, BRIDGE_FRAME_HEADER_LEN);
            $ret = $this->Bridge_send($recoverReq->message(),
                $item->length(), NULL);
            if ($ret == False)
                return False;
            if ($this->Bridge_recv($this->socket, $buffer, NULL) == False)
                return False;
            if (!BridgeIsReply($buffer) || !BridgeIsRecoverSet($buffer))
                return False;
        } else if ($recover == BRIDGE_RECOVER_EXIT) {
            return False; 
        } else if ($recover == BRIDGE_RECOVER_RESTART) {
            return True; 
        }
         
        return True;
    }

    private function BRIDGE_REQUEST($beSent, &$received, $retryCount) {
        $count = 0;
        $retry = True; 
        $length = strlen($beSent);

        while ($retry && $count < $retryCount) {
            $retry = False || !$this->Bridge_send($beSent, $length, 0); 
            $retry = $retry || !$this->Bridge_recv($received, 0);
            
            if ($retry && $count++ != 0)
               sleep(1); 
        }

        return !$retry;
    }
}


// Unit Testing
function taskIDAlloc_testing() {
    $bridgeEntry = new BridgeEntry("123", 11);
    $taskID = $bridgeEntry->taskIDAlloc();
    echo $taskID;
}
function unitTesting() {
    taskIDAlloc_testing();
}

