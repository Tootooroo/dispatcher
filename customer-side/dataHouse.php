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

function oneRowFetch($sqlStmt, $conn) {
    $res = mysql_query($overHeadSql, $conn);
    if ($res) {
        $row = mysql_fetch_row($res);
        return $row; 
    }
    return null;
}

