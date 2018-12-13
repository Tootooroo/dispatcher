<?php

function dataHouseConnect($dataHouseDoor, $host, $userName, $userPass, $dbName) {
    if ( !($dataHouseDoor = mysql_connect($host, $userName, $userPass)) ) {
        generic_err("mysql_connect() failed\n");  
        exit;
    }

    if (!mysql_select_db($dbName, $dataHouseDoor)) {
        generic_err("mysql_select_db() failed\n");
        exit;
    }
}

function sqlRowFetch($sqlStmt, $conn) {
    $res = mysqli_query($conn, $sqlStmt);
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        return $row['_msg']; 
    }
    return null;
}

