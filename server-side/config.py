#!/usr/bin/python

class CONST:
    WORKER_HOST_ADDR = "10.5.4.26"
    WORKER_HOST_PORT = 8013
    WORKER_ID = "0"
    DB_HOST = "10.5.2.22"
    DB_USERNAME = "bridge"
    DB_PASSWORD = "12345678"
    DB_DATABASE = "Bridge"

    DB_OVERHEAD_TBL = 'overHead'
    DB_TASKID_TBL = 'taskIDInfo'
    DB_IDSEED_TBL = 'idSeed'

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

    DB_TABLES_IDSEED = (
        "CREATE TABLE idSeed ("         
        "   seed INT NOT NULL"
        ")"
    )

    WORKING_DIR = "E:/WORKING_DIR/"
    PROJECT_NAME = "OT6800"

    COMPILE_ROOT = "./gbn/src"
    COMPILE_COMMAND = [
        ".\\TiNetS8600_build_bootrom.bat 1",
        ".\\TiNetS8600_build_host.bat 1"
    ]

    RESULT_FILES = [
        # ControlBoard 
        ["boot/config/OT6800_CONTROL_BSP/image/", "bootrom_flash.bin", "host.arj"]
    ]

    COMPRESS_FILE_NAME = "COMPRESS.zip"

    COMPILE_INFO_TRANSFER_BYTES = 4096
