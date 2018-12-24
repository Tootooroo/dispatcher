#!/usr/bin/python

from mysql import connector as DB_CONN
from mysql.connector import errorcode

def db_connect(user_, password_, host_):
    try:
        dbConn = DB_CONN.connect(user=user_, password=password_, host=host_)
    except DB_CONN.Error as connError:
        if connError.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print("Username or password mismatch")
        if connError.errno == errorcode.ER_BAD_DB_ERROR:
            print("Database doesn not exists")
        return False
    return dbConn

def db_create(fd, dbName):
    try:
        fd.execute("CREATE DATABASE {}".format(dbName))
    except DB_CONN.Error as DB_ERR:
        if DB_ERR.errno == errorcode.ER_DB_CREATE_EXISTS:
            return DB_ERR.errno
        else:
            print("Can't create database ERR: {}".format(DB_ERR))
            return False

def db_use(fd, dbName):
    try:
        fd.execute("USE {}".format(dbName))
    except DB_CONN.Error as DB_ERR:
        print("Error in db_use(): {}".format(DB_ERR))
        return False

def table_create(fd, tbl_description):
    try:
        fd.execute(tbl_description)
    except DB_CONN.Error as DB_ERR:
        if DB_ERR.errno == errorcode.ER_TABLE_EXISTS_ERROR:
            return DB_ERR.errno
        else:
            print("Can't create table ERR: {}".format(DB_ERR))
            return False

def db_query(fd, stmt):
    fd.execute(stmt)

def db_fetch(fd):
    return fd.fetchone()

def db_insert(fd, tbl, col, values):
    fd.execute("INSERT INTO {} {}  VALUES{}".format(tbl, col, values))

def row_update(fd, tbl, row, doUpdate):
    try:
        fd.execute("UPDATE {} SET {} WHERE {}".format(tbl, row, doUpdate))
    except DB_CONN.Error as DB_ERR:
        return DB_ERR.errno
def table_clear(fd, tbl):
    fd.execute("DELETE * FROM {}".format(tbl))

if __name__ == '__main__':
    DB_USERNAME = ""
    DB_PASSWORD = ""
    DB_HOST = ""

    dbConn = DB_OP.db_connect(DB_USERNAME, DB_PASSWORD, DB_HOST)
     
    

