<?php
/*
function dataHouseConnect($host, $userName, $userPass, $dbName) {
    $dbEntry = null;
    if ( !($dbEntry = mysql_connect($host, $userName, $userPass)) ) {
        generic_err("mysql_connect() failed\n");  
        return Null;
    }

    if (!mysql_select_db($dbName, $dbEntry)) {
        generic_err("mysql_select_db() failed\n");
        return Null;
    }
    return $dbEntry;
}

function sqlOneRowFetch($sqlStmt, $conn) {
    $res = mysqli_query($conn, $sqlStmt);
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        return $row; 
    } else {
        echo "Failed to query " . "(" . $conn->errno . "):" . $conn->error;
    }
    return null;
}

function sqlExecution($sqlStmt, $conn) {
    mysqli_query($conn, $sqlStmt);
}
 */
