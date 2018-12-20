#!/usr/bin/python

class CONST:
    DB_HOST = "localhost"
    DB_USERNAME = "bridge"
    DB_PASSWORD = "12345678"
    DB_DATABASE = "Bridge"
    
    WORKING_DIR = "/tmp/Build/"
    PROJECT_NAME = "try"

    COMPILE_ROOT = "."
    COMPILE_COMMAND = ["make"]

    RESULT_PATH = WORKING_DIR + "/" + PROJECT_NAME
    RESULT_FILES = ["try"]

    COMPRESS_FILE_NAME = "COMPRESS.zip"


