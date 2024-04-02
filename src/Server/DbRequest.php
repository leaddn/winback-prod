<?php
namespace App\Server;

//use mysqli;
//use PDO;

/**
 * Define request methods to query & manage tables in database
 *
 * @author Lea
 */

require_once dirname(__FILE__, 3).'/configServer/config.php';
require_once dirname(__FILE__, 3).'/configServer/dbConfig.php';

class DbRequest {
    
//public ?mysqli $database = null;
    function __construct() {
    }
    
    /**
     * Connect to DB & return connexion
     * @return \mysqli|bool|string
    */
    /*
    public function dbConnect(){
        if ($this->database === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->database = mysqli_connect($_ENV['HOSTNAME'], $_ENV['ADMIN'], $_ENV['PWD'], $_ENV['DB'], $_ENV['DB_PORT']);
            //var_dump($this->database);
            if ($this->database -> connect_errno) {
                echo "Failed to connect to MySQL: " . $this->database -> connect_error;
                return "error";
            }
        }
        return $this->database;
    }
    */
    /*
    public function dbConnect() {
        if ($this->database === null) {
            $dsn = 'mysql:dbname='.$_ENV['DB'].';host='.$_ENV['HOSTNAME'];
            $user = $_ENV['ADMIN'];
            $password = $_ENV['PWD'];
            $this->database = new PDO($dsn, $user, $password);
            return $this->database;
        }
    }
    */
    public function dbConnect(){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        //$connexion = new \mysqli($host, $username, $password, $database);
        $connexion = new \mysqli($_ENV['DB_HOSTNAME'], $_ENV['DB_ADMIN'], $_ENV['DB_PWD'], $_ENV['DB_NAME']);
        //$connexion = mysqli_connect($_ENV['HOSTNAME'], $_ENV['ADMIN'], $_ENV['PWD'], $_ENV['DB'], $_ENV['DB_PORT']);
        if ($connexion -> connect_errno) {
            echo "Failed to connect to MySQL: " . $connexion -> connect_error;
            return "error";
        }
        else {
            return $connexion;
        }
    }

    /**
     * Check connexion & send request to DB
     * @param mixed $request
     * @return \mysqli_result|bool query result
     */
    /*
    function sendRq($request){
        $this->database = $this->dbConnect();
        $result = mysqli_query($this->database, $request);
        //mysqli_close($this->database);
        return $result;
    }
    */
    function sendRq($request){
        $connexion = $this->dbConnect();
        $result = mysqli_query($connexion, $request);
        mysqli_close($connexion);
        return $result;
    }

    /**
     * Write Select request
     * @param string $attr db-column
     * @param string $from db-table
     * @param string $where db-condition
     * @return string request
     */
    function select($attr, $from, $where = ""){
        $req = "SELECT $attr FROM $from";
        if(!empty($where)){
            $req .= " WHERE $where";
        }
        return $req;
    }

    /**
     * Write Update request
     * @param string $column
     * @param string $value
     * @param string $table
     * @param string $where
     * @return string request
     */
    function update($column, $value, $table, $where=''){
        $req = "UPDATE ".$table." SET ".$column." = '".$value."'";
        if(!empty($where)){
            $req .= " WHERE $where";
        }
        return $req;
    }
    
    /* ####### DEVICE REQUEST ####### */

    /**
     * Modify (update or create) forced column in DB
     */
    function setForced($sn, $forced){
        $where = SN."='".$sn."'";
        $req = $this->update(FORCED_UPDATE, $forced, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
    }

    function setConfigDown($sn, $index){
        $where = SN."='".$sn."'";
        $req = $this->update(DEVICE_CONFIG, $index, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
    }

    function setConfigId($sn, $index, $column){
        $where = SN."='".$sn."'";
        $req = $this->update($column, $index, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
    }

    function getConfigId($sn, $column){
        $whereCondition = SN."='".$sn."'";
        $req = $this->select($column, DEVICE_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[$column];
            }
        }
        return 0;
    }

    function getConfigUp($sn){
        $whereCondition = SN."='".$sn."'";
        $req = $this->select(CONFIG_UP, DEVICE_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[CONFIG_UP];
            }
        }
        return 0;
    }

    function getConfigDown($sn){
        $whereCondition = SN."='".$sn."'";
        $req = $this->select(DEVICE_CONFIG, DEVICE_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[DEVICE_CONFIG];
            }
        }
        return 0;
    }

    function setImageId($sn, $index){
        $where = SN."='".$sn."'";
        $req = $this->update(IMAGE_ID, $index, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
    }
    /**
     * init Device request In Device Table
     * @param string $sn
     * @param string $vers
     * @param int $devType
     * @param string $ipAddr
     * @param string $logFile
     * @return \mysqli_result|bool
     */
    function initDeviceInDB(string $sn, string $vers, int $devType, string $ipAddr, string $logFile){
        //$geography = $this->getIpAddrFromSn($ipAddr);
        if ($sn!="" && $vers!="" && $devType!="" && $ipAddr!="" && $logFile!="") {
            $req = "INSERT INTO ".DEVICE_TABLE." (".DEVICE_TYPE.", ".SN.", ".DEVICE_VERSION.", ".VERSION_UPLOAD.",".IS_CONNECT.",".IP_ADDR.",".LOG_POINTEUR.",".SELECTED.",".CONNECTED.",".CREATED_AT.",".LOG_FILE.",".COUNTRY.",".CITY.") VALUES ('".$devType."', '".$sn."', '".$vers."', '0', '1','".$ipAddr."','0','0','0','".date("Y-m-d | H:i:s")."', '".$logFile."', '0', '0')";
            if ($res = $this->sendRq($req)) {
                return $res;
            }
        }
        echo "\r\nSN: {$sn}\r\n";
        echo "\r\nVers: {$vers}\r\n";
        echo "\r\nDevType: {$devType}\r\n";
        echo "\r\nIpAddr: {$ipAddr}\r\n";
        echo "\r\nSN empty or vers empty or devType empty !\r\n";
        return false;
    }
        
    /**
     * Init Device request in SN Table
     * @param string $sn
     * @param string $devType
     * @return \mysqli_result|bool
     */
    function initDeviceInSN($sn, $devType){
        if ($sn!="" && $devType!="") {
            if (str_contains($sn, 'B3TX')) {
                $devType = "BACK3TX";
            }
            $req = "INSERT IGNORE INTO ".SN_TABLE." (".SN_DEVICE.", ".SN_ID.", ".SN_DATE.") VALUES ('".$devType."', '".$sn."', '".date("Y-m-d | H:i:s")."')";
            if ($res = $this->sendRq($req)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Init Device request in Server Table
     * @param string $sn
     * @return \mysqli_result|bool
     */
    function initDeviceInServer($sn){
        if ($sn!="") {
            $req = "INSERT INTO device_server (device_id, date) VALUES ('".$sn."', '".date("Y-m-d | H:i:s")."')";
            if ($res = $this->sendRq($req)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * If device exists in device Table, select 
     * Else, init device in db
     * & return row
     * @param string $sn 20-length
     * @param string $vers
     * @param int $devType
     * @param string $ipAddr
     * @param string $logFile
     * @param string $deviceConfig
     * @return array|bool|null $deviceInfo - row of all columns
     */
    /*
    function setDeviceInfo(string $sn, string $vers, int $devType, string $ipAddr, string $logFile, $deviceConfig)
    {
        $utils = new Utils();
        $whereCond = SN." = '".$sn."'";
        $geography = $this->getLocationInfoByIp($ipAddr); //get location by ip
        $geography["country"] = $utils->clean($geography["country"]);
        $geography["city"] = $utils->clean($geography["city"]);
        // treat punctuation in name
        $req = $this->select('*', DEVICE_TABLE, $whereCond);
        if($res = $this->sendRq($req)){
            if($row = mysqli_fetch_assoc($res)){
                $req = "UPDATE ".DEVICE_TABLE." SET ".DEVICE_VERSION." = '".$vers."',".IS_CONNECT." = 1,".LOG_FILE." = '".$logFile."',".DOWNLOAD." = 0,".UPDATED_AT." = '".date('Y-m-d | H:i:s')."',".IP_ADDR." = '".$ipAddr."',".COUNTRY." = '".$geography['country']."',".CITY." = '".$geography['city']."',".DEVICE_CONFIG." = '".$deviceConfig."'";
                if(!empty($whereCond)){
                    $req .= " WHERE ".$whereCond;
                }
                $res = $this->sendRq($req);
                return $row;
            }else{
                $res = $this->initDeviceInDB($sn, $vers, $devType, $ipAddr, $logFile, $deviceConfig);
                $res2 = $this->sendRq($req);
                if($row = mysqli_fetch_assoc($res2)){
                    return $row;
                }
            }
        }
        else {
            return false;
        }
    }
    */
    function setDeviceInfo(string $sn, string $vers, int $devType, string $ipAddr, string $logFile)
    {
        $whereCond = SN." = '".$sn."'";
        /*
        $geography = $this->getLocationInfoByIp($ipAddr); //get location by ip
        $geography["country"] = $utils->clean($geography["country"]);
        $geography["city"] = $utils->clean($geography["city"]);
        */
        // treat punctuation in name
        $req = $this->select('*', DEVICE_TABLE, $whereCond);
        if($res = $this->sendRq($req)){
            if($row = mysqli_fetch_assoc($res)){
                $req = "UPDATE ".DEVICE_TABLE." SET ".DEVICE_VERSION." = '".$vers."',".IS_CONNECT." = 1,".LOG_FILE." = '".$logFile."',".DOWNLOAD." = 0,".UPDATED_AT." = '".date('Y-m-d | H:i:s')."',".IP_ADDR." = '".$ipAddr."'";
                if(!empty($whereCond)){
                    $req .= " WHERE ".$whereCond;
                }
                $res = $this->sendRq($req);
                return $row;
            }else{
                $res = $this->initDeviceInDB($sn, $vers, $devType, $ipAddr, $logFile);
                $res2 = $this->sendRq($req);
                if($row = mysqli_fetch_assoc($res2)){
                    return $row;
                }
            }
        }
        else {
            return false;
        }
    }

    /**
     * Insert Device to SN Table if not exists yet
     * @param string $sn
     * @param string $devType
     * @return array|bool|null
     */
    /*
    function setDeviceToSN(string $sn, string $devType)
    {
        $whereCond = SN_ID." = '".$sn."'";
        $req = $this->select('*', SN_TABLE, $whereCond);
        if($res = $this->sendRq($req)){
            if(!$row = mysqli_fetch_assoc($res)){
                echo ("Device not found in DB.");
                $res = $this->initDeviceInSN($sn, $devType);
                $res2 = $this->sendRq($req);
                if($row = mysqli_fetch_assoc($res2)){
                    echo ("Device added in DB.");
                    return $row;
                }
            }
        }
        else {
            return false;
        }
    }
    */

    function setDeviceToServer(string $sn)
    {
        $whereCond = "`device_id` = '".$sn."' AND DATE(date) = CURRENT_DATE";
        $req = $this->select('*', 'device_server', $whereCond);
        if($res = $this->sendRq($req)){
            if($row = mysqli_fetch_assoc($res)){
                $req = "UPDATE `device_server` SET `date` = '".date('Y-m-d | H:i:s')."'";
                if(!empty($whereCond)){
                    $req .= " WHERE ".$whereCond;
                }
                $res = $this->sendRq($req);
                $date = date('Y-m-d | H:i:s', strtotime('-2 months'));
                $req2 = "DELETE FROM `device_server` WHERE `date` < '".$date."' AND `device_id` = '".$sn."'";
                $res2 = $this->sendRq($req2);
                return true;
            }else{
                $res = $this->initDeviceInServer($sn);
                $res2 = $this->sendRq($req);
                $date = date('Y-m-d | H:i:s', strtotime('-3 months'));
                $req2 = "DELETE FROM `device_server` WHERE `date` < '".$date."' AND `device_id` = '".$sn."'";
                $res3 = $this->sendRq($req2);
                if($row = mysqli_fetch_assoc($res2)){
                    return true;
                }
            }
        }
        else {
            return false;
        }
    }

    function getIpAddrFromSn($sn){
        $whereCondition = SN."='".$sn."'";
        $req = $this->select(IP_ADDR, DEVICE_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[IP_ADDR];
            }
        }
        return 0;
    }

    function setIpAddr($ipAddr, $sn){
        $whereCond = SN."='$sn'";
        $req = $this->update(IP_ADDR, $ipAddr, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
    }
	
	function setVersion($version, $sn){
		$whereCond = SN."='$sn'";
        $req = $this->update(DEVICE_VERSION, $version, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);

	}
    
    function setUploadVersion($version, $sn){
		$whereCond = SN."='$sn'";
        $req = $this->update(VERSION_UPLOAD, $version, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);

	}

    /**
     * Update pointeur in db
     */
	function setLog($sn, $newPointeur){
        $whereCond = SN."='$sn'";
        $req = $this->update(LOG_POINTEUR, $newPointeur, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
		
	}

	function setConnect($connected, $sn="", $ip=""){
		if ($sn!="") {
            $whereCond = SN."='$sn'";
            $req = $this->update(IS_CONNECT, $connected, DEVICE_TABLE, $whereCond);
            $res = $this->sendRq($req);
        }
        elseif ($ip!="") {
            $whereCond = IP_ADDR."='$ip'";
            $req = $this->update(IS_CONNECT, $connected, DEVICE_TABLE, $whereCond);
            $res = $this->sendRq($req);
        }

	}

    function setConnectAll($connected, $sn=""){
        $req = $this->update(IS_CONNECT, $connected, DEVICE_TABLE);
        $res = $this->sendRq($req);
	}

    function setCreatedAt($sn, $date){
        $whereCond = SN."='$sn'";
        $req = $this->update(CREATED_AT, $date, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
    }
    function setUpdatedAt($sn, $date){
        $whereCond = SN."='$sn'";
        $req = $this->update(UPDATED_AT, $date, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
    }

    function setDownload($sn, $percentage){
        $whereCond = SN."='$sn'";
        $req = $this->update('download', $percentage, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
    }
    
    function setIndex($sn, $index){
        $whereCond = SN."='$sn'";
        $req = $this->update('indextoget', $index, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
    }

    function getIndex($sn){
        $whereCondition = SN."='".$sn."'";
        $req = $this->select("indextoget", DEVICE_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row["indextoget"];
            }
        }
        return 0;
    }

    function getDevice($sn, $rowName)
    {
        $whereCond = SN."='$sn'";
        $req = $this->select('*', DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[$rowName];
            }
        }
        return false;
    }

    function getDeviceType($id, $rowName)
    {
        $whereCond = DEVICE_TYPE_NB_ID."='$id'";
        $req = $this->select('*', DEVICE_FAMILY_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[$rowName];
            }
        }
        return false;
    }

    function getDeviceTypeId($deviceType)
    {
        $whereCond = DEVICE_TYPE_NB_ID."='$deviceType'";
        $req = $this->select(DEVICE_TYPE_ID, DEVICE_FAMILY_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[DEVICE_TYPE_ID];
            }
        }
        return false;
    }

    /**
     * Get actual version of a given device type
     * - ex: 12 (back4) -> actual version: filename.bin
     * @param int $deviceType
     * @return array|bool|null
     */
    function getDeviceTypeActualVers($deviceType)
    {
        $whereCond = DEVICE_TYPE_NB_ID."='$deviceType'";
        $req = $this->select('actual_version', DEVICE_FAMILY_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                $actual_version = $row['actual_version'];
                $whereCond2 = "name='$actual_version'";
                $req2 = $this->select('name, version', SOFTWARE_TABLE, $whereCond2);
                $res2 = $this->sendRq($req2);
                if ($row2 = mysqli_fetch_assoc($res2)) {
                    return $row2;
                }
            }
        }
        return false;
    }

    /**
     * Summary of getLocationInfoByIp
     * @param mixed $ip
     * @return array
     */
    function getLocationInfoByIp($ip){
        $result = [];
        $result["country"] = "";
        $result["city"] = "";
        $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));    
        if($ip_data && $ip_data->geoplugin_countryName != null){
            $result['country'] = $ip_data->geoplugin_countryCode;
            $result['city'] = $ip_data->geoplugin_city;
        }
        return $result;
    }

    function getUpdateComment($deviceType, $version)
    {
        $whereCond = DEVICE_TYPE." = '$deviceType' AND ".DEVICE_VERSION." = '$version'";
        $req = $this->select(UPDATE_COMMENT, SOFTWARE_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[UPDATE_COMMENT];
            }
        }
        return false;
    }

    /* ##### TREATMENT ###### */
    
    function delete_rshock_treatment_table(){
        $req="TRUNCATE `winback_test`.`rshock_treatment`";
        $res = $this->sendRq($req); 	
    }
    
    function addTreatment($data){
        $req = "INSERT INTO `rshock_treatment`(`ID`, `SN`, `version`, `zone`, `type_patho`, `patho`, `num_seance`, `position`, `accessoires`, `douleurAv`, `douleurAp`, `tecar0`, `pulse0`, `mix0`, `shock0`, `tecarDyn0`, `pulseDyn0`, `tpsTecar0`, `duree0`, `courbe0`, `tecar1`, `pulse1`, `mix1`, `shock1`, `tecarDyn1`, `pulseDyn1`, `tpsTecar1`, `duree1`, `courbe1`, `tecar2`, `pulse2`, `mix2`, `shock2`, `tecarDyn2`, `pulseDyn2`, `tpsTecar2`, `duree2`, `courbe2`, `tecar3`, `pulse3`, `mix3`, `shock3`, `tecarDyn3`, `pulseDyn3`, `tpsTecar3`, `duree3`, `courbe3`, `tecar4`, `pulse4`, `mix4`, `shock4`, `tecarDyn4`, `pulseDyn4`, `tpsTecar4`, `duree4`, `courbe4`, `tecar5`, `pulse5`, `mix5`, `shock5`, `tecarDyn5`, `pulseDyn5`, `tpsTecar5`, `duree5`, `courbe5`, `tecar6`, `pulse6`, `mix6`, `shock6`, `tecarDyn6`, `pulseDyn6`, `tpsTecar6`, `duree6`, `courbe6`, `tecar7`, `pulse7`, `mix7`, `shock7`, `tecarDyn7`, `pulseDyn7`, `tpsTecar7`, `duree7`, `courbe7`, `tecar8`, `pulse8`, `mix8`, `shock8`, `tecarDyn8`, `pulseDyn8`, `tpsTecar8`, `duree8`, `courbe8`, `tecar9`, `pulse9`, `mix9`, `shock9`, `tecarDyn9`, `pulseDyn9`, `tpsTecar9`, `duree9`, `courbe9`) VALUES (NULL,'".$data['SN']."','".$data['version']."','".$data['zone']."','".$data['type_patho']."','".$data['patho']."','".$data['num_seance']."','".$data['position']."','".$data['accessoires']."','".$data['douleurAv']."','".$data['douleurAp']."','".$data['tecar0']."','".$data['pulse0']."','".$data['mix0']."','".$data['shock0']."','".$data['pulseDyn0']."','".$data['tecarDyn0']."','".$data['tpsTecar0']."','".$data['duree0']."','".$data['courbe0']."','".$data['tecar1']."','".$data['pulse1']."','".$data['mix1']."','".$data['shock1']."','".$data['pulseDyn1']."','".$data['tecarDyn1']."','".$data['tpsTecar1']."','".$data['duree1']."','".$data['courbe1']."','".$data['tecar2']."','".$data['pulse2']."','".$data['mix2']."','".$data['shock2']."','".$data['pulseDyn2']."','".$data['tecarDyn2']."','".$data['tpsTecar2']."','".$data['duree2']."','".$data['courbe2']."','".$data['tecar3']."','".$data['pulse3']."','".$data['mix3']."','".$data['shock3']."','".$data['pulseDyn3']."','".$data['tecarDyn3']."','".$data['tpsTecar3']."','".$data['duree3']."','".$data['courbe3']."','".$data['tecar4']."','".$data['pulse4']."','".$data['mix4']."','".$data['shock4']."','".$data['pulseDyn4']."','".$data['tecarDyn4']."','".$data['tpsTecar4']."','".$data['duree4']."','".$data['courbe4']."','".$data['tecar5']."','".$data['pulse5']."','".$data['mix5']."','".$data['shock5']."','".$data['pulseDyn5']."','".$data['tecarDyn5']."','".$data['tpsTecar5']."','".$data['duree5']."','".$data['courbe5']."','".$data['tecar6']."','".$data['pulse6']."','".$data['mix6']."','".$data['shock6']."','".$data['pulseDyn6']."','".$data['tecarDyn6']."','".$data['tpsTecar6']."','".$data['duree6']."','".$data['courbe6']."','".$data['tecar7']."','".$data['pulse7']."','".$data['mix7']."','".$data['shock7']."','".$data['pulseDyn7']."','".$data['tecarDyn7']."','".$data['tpsTecar7']."','".$data['duree7']."','".$data['courbe7']."','".$data['tecar8']."','".$data['pulse8']."','".$data['mix8']."','".$data['shock8']."','".$data['pulseDyn8']."','".$data['tecarDyn8']."','".$data['tpsTecar8']."','".$data['duree8']."','".$data['courbe8']."','".$data['tecar9']."','".$data['pulse9']."','".$data['mix9']."','".$data['shock9']."','".$data['pulseDyn9']."','".$data['tecarDyn9']."','".$data['tpsTecar9']."','".$data['duree9']."','".$data['courbe9']."')";
        //echo $req;
        $res = $this->sendRq($req);  
        /*if($res != FALSE){
            echo 'ok';
        }else echo 'ko';*/		
    }
    
    function dbRequest_get_rshock_treatment_table($distinct,$where,$order){
        //  $whereCondition = "sn='".$sn."'";
        $req = "SELECT";
        if(!empty($distinct)){
            $req .= " DISTINCT ".$distinct;
        }else $req .= " *";
        $req .= " FROM rshock_treatment";
        
        if(!empty($where)){
            $req .= " WHERE ".$where;
        }
        
        if(!empty($order)){
            $req .= " ORDER BY ".$order;
        }
        $res = $this->sendRq($req);
        
        //echo $req;
        if($res != FALSE){
            return $res;
        }

        return 0;
    }

    function updateNewSoftware($name, $devType, $version, $date){
        $req = "INSERT INTO ".SOFTWARE_TABLE." (".NAME.", ".FAMILY_TYPE.", ".SOFT_VERSION.", ".SOFT_CREATED_AT.") VALUES ('".$name."', '".$devType."', '".$version."', '".$date."') ON DUPLICATE KEY UPDATE ".SOFT_VERSION."= '".$version."',".UPDATED_AT."= '".date("Y-m-d | H:i:s")."'";
        return $req;
    }
	
    function updateSoftwareInDB($name, $devType, $version, $date){
        $req = $this->updateNewSoftware($name, $devType, $version, $date);
        $res = $this->sendRq($req);
        
        return false;
    }
}
