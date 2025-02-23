<?php
/* Define DB table */

if (!defined('USER_TABLE')) define('USER_TABLE', 'user');
if (!defined('DEVICE_TABLE')) define('DEVICE_TABLE', 'device');
if (!defined('DEVICE_FAMILY_TABLE')) define('DEVICE_FAMILY_TABLE', 'device_family');
if (!defined('SOFTWARE_TABLE')) define('SOFTWARE_TABLE', 'software');
if (!defined('TREATMENT_TABLE')) define('TREATMENT_TABLE', 'treatment');
if (!defined('SN_TABLE')) define('SN_TABLE', 'sn');

/* Define Device Table properties */

if (!defined('DEVICE_TYPE')) define('DEVICE_TYPE', 'device_family_id');
//if (!defined('DEVICE_TYPE')) define('DEVICE_TYPE', 'device_family');
if (!defined('SN')) define('SN', 'sn');
if (!defined('DEVICE_VERSION')) define('DEVICE_VERSION', 'version');
if (!defined('VERSION_UPLOAD')) define('VERSION_UPLOAD', 'version_upload');
if (!defined('FORCED_UPDATE')) define('FORCED_UPDATE', 'forced');
if (!defined('IP_ADDR')) define('IP_ADDR', 'ip_addr');
if (!defined('LOG_POINTEUR')) define('LOG_POINTEUR', 'log_pointeur');
//define('RQ_SERVER', 'rqServer');
if (!defined('PUB_ACCEPTED')) define('PUB_ACCEPTED', 'pub');
if (!defined('PIN_CODE')) define('PIN_CODE', 'code_pin');
if (!defined('SELECTED')) define('SELECTED', 'selected');
if (!defined('CREATED_AT')) define('CREATED_AT', 'created_at');
if (!defined('UPDATED_AT')) define('UPDATED_AT', 'updated_at');
//define('IS_ACTIVE', 'is_active');
if (!defined('LOG_FILE')) define('LOG_FILE', 'log_file');
if (!defined('SERVER_DATE')) define('SERVER_DATE', 'server_date');
if (!defined('IS_CONNECT')) define('IS_CONNECT', 'is_active');
if (!defined('CONNECTED')) define('CONNECTED', 'connected');
if (!defined('DOWNLOAD')) define('DOWNLOAD', 'download');
if (!defined('UPDATE_COMMENT')) define('UPDATE_COMMENT', 'update_comment');
if (!defined('COUNTRY')) define('COUNTRY', 'country');
if (!defined('CITY')) define('CITY', 'city');
if (!defined('SERVER_ID')) define('SERVER_ID', 'server_id');
if (!defined('SERVER_IP')) define('SERVER_IP', 'server_ip'); // config server ip
if (!defined('SERVER_PORT')) define('SERVER_PORT', 'server_port'); // config server port
if (!defined('DEVICE_CONFIG')) define('DEVICE_CONFIG', 'config'); // config down
if (!defined('CONFIG_UP')) define('CONFIG_UP', 'config_up'); // config up
if (!defined('CONFIG_ID')) define('CONFIG_ID', 'config_id'); // boolean to change config
if (!defined('CONFIG_SN_UP')) define('CONFIG_SN_UP', 'sn_up'); // serial number to replace
if (!defined('CONFIG_SN_ID')) define('CONFIG_SN_ID', 'sn_id'); // boolean to change sn config
if (!defined('IMAGE_ID')) define('IMAGE_ID', 'image_id'); // boolean to change image config
if (!defined('IMAGE_UP')) define('IMAGE_UP', 'image_up'); // new image config
if (!defined('CONFIG_INDUS_ID')) define('CONFIG_INDUS_ID', 'config_indus_id'); // boolean to change indus config
if (!defined('CONFIG_INDUS_UP')) define('CONFIG_INDUS_UP', 'config_indus_up'); // new indus config
/* Define User Table properties */

if (!defined('LOGIN')) define('LOGIN', 'username');

/* Define Software Table properties */
if (!defined('NAME')) define('NAME', 'name');
if (!defined('FAMILY_TYPE')) define('FAMILY_TYPE', 'device_family_id');
if (!defined('FAMILY_NAME')) define('FAMILY_NAME', 'device_family');
if (!defined('SOFT_VERSION')) define("SOFT_VERSION", "version");
if (!defined('SOFT_CREATED_AT')) define("SOFT_CREATED_AT", "created_at");

/* Define Device Type properties */
if (!defined('DEVICE_TYPE_ID')) define('DEVICE_TYPE_ID', 'id');
if (!defined('DEVICE_TYPE_NB_ID')) define('DEVICE_TYPE_NB_ID', 'number_id');

/* Define SN Table properties */
if (!defined('SN_ID')) define('SN_ID', 'SN');
if (!defined('SN_DEVICE')) define('SN_DEVICE', 'Device');
if (!defined('SN_DATE')) define('SN_DATE', 'Date');