#!/usr/bin/python

class CONST:
    WORKER_HOST_ADDR = "127.0.0.1"
    WORKER_HOST_PORT = 8013
    WORKER_ID = "0"
    DB_HOST = "10.5.2.22"
    DB_USERNAME = "bridge"
    DB_PASSWORD = "12345678"
    DB_DATABASE = "Bridge"

    DB_OVERHEAD_TBL = 'overHead'
    DB_TASKID_TBL = 'taskIDInfo'

    DB_TABLES_OVERHEAD = (
        "CREATE TABLE overHead ("
        "   ID INT NOT NULL,"
        "   STATE VARCHAR(5) NOT NULL,"
        "   PRIMARY KEY (ID))"
    )

    DB_TABLES_TASKINFO = (
        "CREATE TABLE taskIDInfo ("
        "   WORKER_ID INT NOT NULL,"
        "   MAX INT NOT NULL,"
        "   inProc INT NOT NULL,"
        "   pending INT NOT NULL,"
        "   PRIMARY KEY (wID))"
    )

    WORKING_DIR = "/tmp/Build/"
    PROJECT_NAME = "try"

    COMPILE_ROOT = "."
    COMPILE_COMMAND = ["make"]

    RESULT_PATH = "."
    RESULT_FILES = ["try"]

    COMPRESS_FILE_NAME = "COMPRESS.zip"

    COMPILE_INFO_TRANSFER_BYTES = 2048
