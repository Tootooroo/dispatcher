#!/bin/php -q

<?php

// Num of channel rebuild upper bound
define("BRIDGE_CHANNEL_REBUILD_NUM", 5);
// Maximum size of buffer
define("BRIDGE_MAX_SIZE_OF_BUFFER", 1024);
// Binary buffer format
define("BRIDGE_FRAME_FORMAT_PACK", "vvvvvIA*");
define("BRIDGE_FRAME_FORMAT_UNPACK", "vtype/vop/vprop/vtaskID/vflag/Ilength/A*content");
// TYPE(2) + OP(2) + PROP(2) + SEQID(2) + FLAG(2) + LEN(4) = 14 Bytes
define("BRIDGE_FRAME_HEADER_LEN", 14);
// Entry state
define("ENTRY_UP", 0x01);
define("ENTRY_DOWN", 0x00);

// Bridge Type Field Constant
define("BRIDGE_TYPE_REQUEST", 0x01);
define("BRIDGE_TYPE_REPLY", 0x02);
define("BRIDGE_TYPE_INFO", 0x03);
define("BRIDGE_TYPE_MANAGEMENT", 0x04);
define("BRIDGE_TYPE_TRANSFER", 0x05);

// Bridge Op Field Constant
define("BRIDGE_OP_ENABLE", 0x01);
define("BRIDGE_OP_DISABLE", 0x02);
define("BRIDGE_OP_SET", 0x03);

// Bridge Flag Field Constant
define("BRIDGE_FLAG_NOTIFY", 0x0001);
define("BRIDGE_FLAG_TRANSFER", 0x0002);
define("BRIDGE_FLAG_TRANSFER_DONE", 0x0004);
define("BRIDGE_FLAG_ACCEPT", 0x0008);
define("BRIDGE_FLAG_DECLINE", 0x0010);
define("BRIDGE_FLAG_RETRIVE", 0x0020);
define("BRIDGE_FLAG_READY_TO_SEND", 0x0040);
define("BRIDGE_FLAG_ERROR", 0x0080);
define("BRIDGE_FLAG_IS_JOB_DONE", 0x0100);
define("BRIDGE_FLAG_RECOVER", 0x0200);
define("BRIDGE_FLAG_JOB_DONE", 0x0400);

// Recover options
define("BRIDGE_RECOVER_RESTART", 0);
define("BRIDGE_RECOVER_CONTINUE", 1);

// Bridge Property Field Constant Specific Property is pending


