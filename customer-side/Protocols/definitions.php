#!/bin/php -q

<?php

// Bridge Op Code Constant
define("BRIDGE_TYPE_REQUEST", 0x01);
define("BRIDGE_TYPE_REPLY", 0x02);
define("BRIDGE_TYPE_INFO", 0x03);
define("BRIDGE_TYPE_MANAGEMENT", 0x04);

define("BRIDGE_OP_ENABLE", 0x01);
define("BRIDGE_OP_DISABLE", 0x02);
define("BRIDGE_OP_SET", 0x03);

// Bridge Cate Code Constant
define("BRIDGE_CATE_DEFAULT", 0x00);
define("BRIDGE_CATE_PROPERTY", 0x01);

