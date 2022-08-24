<?php
namespace App\Server;

/**
 * Define request methods to query & manage tables in database
 *
 * @author Lea
 */

//require_once ('config.php');
//include_once "../Ressource/Config/dbConfig.php";
//include_once "./Ressource/Config/dbConfig.php";
require_once dirname(__FILE__, 3).'/configServer/config.php';
require_once dirname(__FILE__, 3).'/configServer/dbConfig.php';

class DbRequest {
    
    function __construct() {
    }
    
    public function dbConnect(){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connexion = mysqli_connect(HOSTNAME, ADMIN, PWD, DB);
        if ($connexion -> connect_errno) {
            echo "Failed to connect to MySQL: " . $connexion -> connect_error;
            exit();
        }
        else {
            //printf("\r\n"."Success... %s\n", mysqli_get_host_info($connexion));
            return $connexion;
        }
    }

    function sendRq($request){
        $connexion = $this->dbConnect();
        $result = mysqli_query($connexion, $request);
        mysqli_close($connexion);
        return $result;
    }

    function select($attr, $from, $where = ""){
        $req = "SELECT $attr FROM $from";
        if(!empty($where)){
            $req .= " WHERE $where";
			
        }
        return $req;
    }

    function delete($from, $where = ""){
        $req = "DELETE FROM $from";
        if(!empty($where)){
            $req .= " WHERE $where";
        }
        
        return $req;
    }

    function update($column, $value, $table, $where=''){
        $req = "UPDATE ".$table." SET ".$column." = '".$value."'";
        if(!empty($where)){
            $req .= " WHERE $where";
        }
        
        return $req;
    }

    /* ###### USER OPERATIONS ##### */

    function createInsert($login, $pwd, $email, $role, $table){
        $req = "INSERT INTO ".$table." (login, pwd, email, role, activated) VALUES ('".$login."','".$pwd."','".$email."',".$role.",0)";
        
        return $req;
    }

	function checkUserActif($login){
        $whereCondition = LOGIN."='$login'";
        $req = $this->select('activated', USER_TABLE, $whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                if (($row['activated'] === '1') || ($row['activated'] === 1)){
                    return true;
                }
            }
        }

        return false;
	}
	
    function checkUser($login, $pwd){
        $whereCondition = "login='$login'";
        $req = $this->select("pwd", USER_TABLE, $whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                if(password_verify($pwd, $row['pwd'])){
                    return true;
                }
            }
        }

        return false;
    }
        
    function createUser($user, $encryptPwd, $email, $role){
        $req = $this->createInsert($user, $encryptPwd, $email, $role, USER_TABLE);
        $res = $this->sendRq($req);
        
    }

    public function checkExistUser($login) {
        $whereCondition = "login='$login'";
        $req = $this->select("login", USER_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return false;
            }
        }
        return true;
    }

    public function getTeamId($login){
        $whereCondition = "login='$login'";
        $req = $this->select("role", USER_TABLE,$whereCondition);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row['role'];
            }
        }
        return 0;
    }
    
    /* ####### DEVICE REQUEST ####### */
    function getElemBy($key, $table, $res){
        $req = "SELECT * FROM {$table} WHERE {$key}='{$res}'";
        $res = $this->sendRq($req);
        if ($row = mysqli_fetch_assoc($res)) {
            return true;
        }
        else{
            return false;
        }
    }

    function getListSn($sn = ''){
        $whereCond = '';
        if(!empty($sn)){
            $whereCond = SN."='$sn'";
        }
        $req = $this->select("*", DEVICE_TABLE,$whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            while($row = mysqli_fetch_assoc($res)){
                $result[] = $row;
            }
            return $result;
        }
        return false;
    }

    function setForced($sn, $forced){
        $where = SN."='".$sn."'";
        $req = $this->update(FORCED_UPDATE, $forced, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
        /*
        if($res != FALSE){
            return true;
        }else{
            return false;
        }
        
        return true;
        */
    }
    
    function setLogFile($sn, $logFile){
        $where = SN."='".$sn."'";
        $req = $this->update(LOG_FILE, $logFile, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
        /*
        if($res != FALSE){
            return true;
        }else{
            return false;
        }
        
        return true;
        */
    }

    function updateData($sn, $value){
        $where = SN."='".$sn."'";
        if (($value === '0') || ($value === '0.0')|| ($value === ''))
            $value = 'NULL';
        $req = $this->update(VERSION_UPLOAD, $value, DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
        if($res != FALSE){
            return true;
        }else{
            return false;
        }
        
        return true;
    }
    
    function updateMassData($aAttrTable){
        $where = "";
        foreach ($aAttrTable as $key => $value) {
            switch ($key) {
                case "deviceType":
                    if($where !== ""){
                        $where .= " AND ";
                    }
                    $idDevice = array_keys(deviceType, $value);
                    $where .= DEVICE_TYPE." = ".$idDevice[0];
                    break;
                case "condSn":
                    if($where !== ""){
                        $where .= " AND ";
                    }
                    $where .= "sn ".$value;
                    break;
                case "softVers":
                    if($where !== ""){
                        $where .= " AND ";
                    }
                    $where .= DEVICE_VERSION." ".$aAttrTable['condVers']." '".$value."'";
                    break;
                default:
                    break;
            }
        }
        if (($aAttrTable['updateVers'] === '0') || ($aAttrTable['updateVers'] === '0.0')|| ($aAttrTable['updateVers'] === ''))
            $aAttrTable['updateVers'] = 'NULL';

        $req = $this->update(VERSION_UPLOAD, $aAttrTable['updateVers'], DEVICE_TABLE, $where);
        $res = $this->sendRq($req);
        if($res != FALSE){
            return true;
        }else{
            return false;
        }
        
        return true;
    }
    function searchDevice($sn = ''){
        $whereExist = false;
        $where = '';
        if(!empty($sn)){
            $where .= SN." LIKE '%$sn%' ";
            $whereExist = true;
        }
        /*
        if(!empty($device)){
            if($whereExist)
                $where .= ' AND ';
            $where .= DEVICE_TYPE." LIKE '%$device%' ";
            $whereExist = true;
        }
        if(!empty($version)){
            if($whereExist)
                $where .= ' AND ';
            $where .= DEVICE_VERSION." LIKE '%$version%' ";
        }
        */
        $req = $this->select("*", DEVICE_TABLE, $where);
        echo $req;
        $res = $this->sendRq($req);
        if($res != FALSE){
            while($row = mysqli_fetch_assoc($res)){
                $result[] = $row;
            }
            return $result;
        }
        return false;
    }
    /*
    function searchDevice($sn = '', $device='', $version=''){
        $whereExist = false;
        $where = '';
        if(!empty($sn)){
            $where .= SN." LIKE '%$sn%' ";
            $whereExist = true;
        }
        if(!empty($device)){
            if($whereExist)
                $where .= ' AND ';
            $where .= DEVICE_TYPE." LIKE '%$device%' ";
            $whereExist = true;
        }
        if(!empty($version)){
            if($whereExist)
                $where .= ' AND ';
            $where .= DEVICE_VERSION." LIKE '%$version%' ";
        }
        $req = $this->select("*", DEVICE_TABLE, $where);
        echo $req;
        $res = $this->sendRq($req);
        if($res != FALSE){
            while($row = mysqli_fetch_assoc($res)){
                $result[] = $row;
            }
            return $result;
        }
        return false;
    }
    */

    function insertNewDevice(string $sn, $vers, $devType) : string
    {
        //$date = date("Y-m-d H:i:s");
        $req = "INSERT INTO ".DEVICE_TABLE." (".DEVICE_TYPE.", ".SN.", ".DEVICE_VERSION.", ".VERSION_UPLOAD.",".IS_CONNECT.",".IP_ADDR.",".LOG_POINTEUR.",".SELECTED.",".CONNECTED.",".CREATED_AT.") VALUES ('".$devType."', '".$sn."', '".$vers."', '0', '0','0','0','0','0','".date("Y-m-d | H:i:s")."')";
        return $req;
    }
    
    function initDeviceInDB($sn, $vers, $devType){
        if ($sn!="" && $vers!="" && $devType!="") {
            $req = $this->insertNewDevice($sn, $vers, $devType);
            //$res = $this->sendRq($req);
            if ($res = $this->sendRq($req)) {
                return $res;
            }
        }
        echo "\r\nSN: {$sn}\r\n";
        echo "\r\nVers: {$vers}\r\n";
        echo "\r\nDevType: {$devType}\r\n";
        echo "\r\nSN empty or vers empty or devType empty !\r\n";
        
    }
    
    function setDeviceInfo($sn, $vers, $devType)
    {
        $whereCond = SN."='$sn'";
        $req = $this->select('*', DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res){
            if($row = mysqli_fetch_assoc($res)){
                return $row;
            }else{
                $res = $this->initDeviceInDB($sn, $vers, $devType);
            }
        }
        else {
            return false;
        }
    }

    function setDeviceData($sn, $version, $logFile){
		$where = SN."='$sn'";
        //$req = $this->update($column, $value, DEVICE_TABLE, $whereCond);
        $req = "UPDATE ".DEVICE_TABLE." SET ".DEVICE_VERSION." = '".$version."',".IS_CONNECT." = 1,".LOG_FILE." = '".$logFile."'";
        if(!empty($where)){
            $req .= " WHERE $where";
        }
        
        //return $req;
        $res = $this->sendRq($req);

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
    
    /*
    function addSN($data){
        $req = "SELECT SN FROM sn WHERE SN = '".$data['SN']."'";
        $res = $this->sendRq($req); 
        
        if($row = mysqli_fetch_assoc($res)){
            $req = "UPDATE `sn` SET SN='".$data['SN']."', Device='".$data['Device']."', Date='".$data['Date']."' WHERE SN='".$data['SN']."'";   
        }else{
            $req = "INSERT INTO `sn`(`SN`, `Device`, `Date`) VALUES ('".$data['SN']."','".$data['Device']."','".$data['Date']."')";      
        }
        $res = $this->sendRq($req);  	
    }
    */
    // get sn from excel file
    /*
    function addSNfromDeviceTable(){
        
        $req = "SELECT * FROM ".DEVICE_TABLE;
        $res = $this->sendRq($req); 
        
        while($row = mysqli_fetch_assoc($res)){                   
            $data = array("ID"=>'',"SN"=>'', "DeviceType"=>'', "Date"=>'');    
            $data['SN'] = $row[SN] ;                            
            $data['SN'] = str_replace(" ", "", $data['SN']);
            $data['DeviceType'] = deviceTypeId[$row[DEVICE_TYPE]]; // search deviceType by Id to get deviceType name

            //$this->addSN($data);     
        }
        //echo $data['DeviceType'];
    }
    */

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
        $whereCond = NUMBER_ID."='$id'";
        $req = $this->select('*', DEVICE_FAMILY_TABLE, $whereCond);
        $res = $this->sendRq($req);
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row[$rowName];
            }
        }
        return false;
    }

    /*
    function getLogPtFrom($sn){
        $whereCond = SN."='$sn'";
        $req = $this->select('*', DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
        $vers = 0.0;
        $devType = "DEFAULT";
        if($res != FALSE){
            if($row = mysqli_fetch_assoc($res)){
                return $row;
            }
            else{
                $req = $this->insertNewDevice($sn, $vers, $devType);
                $res = $this->sendRq($req);
            }
        }
        return false;
	}
	*/
	
    /*
	function setRqServer($sn, $value){
        $whereCond = "sn='$sn'";
        $req = $this->update(RQ_SERVER, $value, DEVICE_TABLE, $whereCond);
        $res = $this->sendRq($req);
	}
    */

    /* ##### TREATMENT ###### */
    
    function delete_rshock_treatment_table(){
        $req="TRUNCATE `softwinback`.`rshock_treatment`";
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
}