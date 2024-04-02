<?php
//ini_set('memory_limit','16M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Paris');

/**
 * cmdByte values
 */
if (!defined('cmdSoft')) define('cmdSoft', array('FE', 'FD', 'FC', 'FA', 'F9', 'F8', 'F7', 'F6', 'F5', 'F3', 'F2','DE','DD','DC','DB','DA','D9','D8','CF','CE', 'CD', 'CC', 'CB', 'D7'));
if (!defined('cmdBack')) define('cmdBack', array('DE','DD','DC','DB','DA','D9','D8','CF','CE', 'CD', 'CC', 'CB', 'D7'));

/* Define PATH */
if (!defined('DEVICE_TYPE_ARRAY')) define('DEVICE_TYPE_ARRAY', array(10 => 'RSHOCK/', 11 => 'CRYOBACK4/', 12 => 'BACK4/', 13 => 'BIOBACK/', 14 => 'BACK3TX/', 15 => 'BACK3TE/'));
if (!defined('deviceTypeId')) define('deviceTypeId', array(10 => 1, 12 => 3, 11 => 4, 14 => 5, 13 => 6, 15 => 7)); //Ids corresponding to Ids automatically created in SQLdb
if (!defined('deviceIdType')) define('deviceIdType', array(1 => 10, 3 => 12, 4 => 11, 5 => 14, 6 => 13, 7 => 15));

/*
 *      DEFINE File Info
*/

if (!defined('FW_OCTETS')) define("FW_OCTETS", 256);
if (!defined('stFILENAME')) define("stFILENAME", "WLE256");
if (!defined('extFILENAME')) define("extFILENAME", ".bin");

if (!defined('ADDRESS_ARRAY')) define('ADDRESS_ARRAY', array($_ENV['ADDRESS']));
if (!defined('PORT_ARRAY')) define('PORT_ARRAY', array($_ENV['PORT'], $_ENV['SERVER_SECURE_PORT']));