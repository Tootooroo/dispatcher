#!/usr/bin/python

class CONST:
    WORKER_HOST_ADDR = "127.0.0.1"
    WORKER_HOST_PORT = 8013
    WORKER_ID = ""
    DB_HOST = "localhost"
    DB_USERNAME = "bridge"
    DB_PASSWORD = "12345678"
    DB_DATABASE = "Bridge"

    DB_TABLES_OVERHEAD = (
        "CREATE TABLE overHead ("
        "   ID INT NOT NULL,"
        "   STATE VARCHAR(5) NOT NULL,"
        "   PRIMARY KEY (ID))"
    )

    DB_TABLES_TASKINFO = (
        "CREATE TABLE taskIDInfo ("
        "   wID INT NOT NULL,"
        "   max INT NOT NULL,"
        "   inProc INT NOT NULL,"
        "   pending INT NOT NULL,"
        "   PRIMARY KEY (wID))"
    )

    WORKING_DIR = "/tmp/Build/"
    PROJECT_NAME = "try"

    COMPILE_ROOT = "."
    COMPILE_COMMAND = ["make"]

    RESULT_PATH = WORKING_DIR + "/" + PROJECT_NAME
    RESULT_FILES = ["try"]

    COMPRESS_FILE_NAME = "COMPRESS.zip"

