#!/bin/php -q

<?php

// Binary buffer format
define("BRIDGE_FRAME_FORMAT", "vvIA*");
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
define("BRIDGE_FLAG_NOTIFY", 0x01);
define("BRIDGE_FLAG_TRANSFER", 0x02);
define("BRIDGE_FLAG_TRANSFER_DONE", 0x03);

// Bridge Property Field Constant
// Specific Property is pending


