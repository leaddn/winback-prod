<?php
namespace App\Server;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandDetect extends AbstractController {

    /***  Var to set from request  ***/
    
    private $command = ""; // str of 2 letters (ex: DE) in 20 & 21 position to get from data received
    //private $indexToGet; // index of 8 digits to get from data received
    private $reqId; // int of 2 digits (ex: 81) in 22 & 23 position to get from data received
	private $boardType; // software type (ex: sport, fitness, comfort), 2 by default for the moment
    private $path;
	
	private $logTxt = "";
    private $ptLogSave = 0;
	
	private $nbDataToSend;
	private $dataTemp;

	private $config;
	/**
	 * Summary of responseArray
	 * [0] = $indexToGet;
	 * [1] = $response.$footer;
	 * [2] = $deviceInfo;
	 * [3] = $percentage;
	 */
	private $responseArray = array();

	private $getserverCesarMatrixTxArray=array(
			0x9E, 0xAC, 0xCF, 0x90, 0x36, 0x3A, 0x1F, 0xDC, 0xBB, 0x4B, 0x4A, 0x71, 0x61, 0x09, 0x10, 0x07,
			0x6A, 0xF1, 0x2A, 0x87, 0xF3, 0x1A, 0xBC, 0xAB, 0xE4, 0xDD, 0xD8, 0x48, 0x7B, 0x0A, 0xA4, 0xCB,
			0x29, 0xD3, 0x18, 0x80, 0x35, 0xCE, 0xD9, 0xA2, 0xC4, 0xAC, 0x0E, 0xA6, 0x97, 0x75, 0x58, 0xA0,
			0x8A, 0x86, 0x76, 0xDD, 0x07, 0x39, 0x02, 0xE6, 0x18, 0x43, 0x56, 0x6B, 0x21, 0x22, 0x90, 0x5E,
			0x28, 0xC0, 0x6C, 0xD9, 0x09, 0xD0, 0xA6, 0x0C, 0x3B, 0xB4, 0x35, 0x64, 0x79, 0xDF, 0x10, 0xBE,
			0x09, 0xE4, 0xC1, 0x5A, 0x08, 0x1D, 0x42, 0x84, 0x1B, 0x5B, 0xA1, 0x93, 0x56, 0x00, 0xF2, 0xA3,
			0xC4, 0xF8, 0x4E, 0x6A, 0x58, 0xC4, 0x67, 0x11, 0xAD, 0xC1, 0xAA, 0x13, 0x98, 0xBA, 0xD9, 0x74,
			0xF7, 0x73, 0x05, 0xB9, 0xB5, 0x6C, 0x9D, 0x49, 0x70, 0x8F, 0x09, 0x3C, 0xF0, 0xE6, 0x2F, 0x68,
			0xE1, 0x42, 0x46, 0x91, 0x41, 0x09, 0x98, 0xFB, 0x95, 0x43, 0xFD, 0x74, 0x67, 0x36, 0xA3, 0xF9,
			0x87, 0x19, 0x4D, 0x78, 0xAD, 0xF8, 0x14, 0xCC, 0xFF, 0xDF, 0x99, 0xB2, 0xDF, 0x2C, 0xCF, 0x60,
			0x13, 0x04, 0x19, 0x66, 0xAE, 0x77, 0x04, 0x27, 0x08, 0x63, 0x02, 0x6F, 0x38, 0x1F, 0xBA, 0xDB,
			0x01, 0xD1, 0xF6, 0x38, 0x7A, 0xE7, 0xC3, 0x66, 0xBC, 0x39, 0x7F, 0x4F, 0x57, 0x4E, 0x55, 0x61,
			0x28, 0xF7, 0xD0, 0x83, 0xE6, 0x8D, 0x4B, 0x0F, 0x3A, 0x58, 0x17, 0xDE, 0xEB, 0x5F, 0x91, 0x33,
			0x59, 0xE0, 0xE7, 0x57, 0x6D, 0xFD 
	);
	
	private $getserverCesarMatrixRxArray=array(
    0x03, 0x2B, 0x27, 0x14, 0x56, 0x0C, 0x32, 0x3F, 0x38, 0x3B, 0x56, 0x37, 0x5D, 0x52, 0x2C, 0x19,
    0x54, 0x32, 0x02, 0x3C, 0x10, 0x58, 0x42, 0x28, 0x5A, 0x30, 0x17, 0x3F, 0x0A, 0x1A, 0x32, 0x36,
    0x5C, 0x30, 0x54, 0x56, 0x2A, 0x38, 0x35, 0x4F, 0x53, 0x51, 0x13, 0x46, 0x5B, 0x1C, 0x04, 0x58,
    0x05, 0x52, 0x0B, 0x15, 0x1C, 0x25, 0x1B, 0x38, 0x16, 0x16, 0x03, 0x44, 0x15, 0x1B, 0x47, 0x0C,
    0x47, 0x35, 0x5B, 0x32, 0x20, 0x10, 0x02, 0x3A, 0x1C, 0x34, 0x46, 0x5D, 0x50, 0x2D, 0x42, 0x01,
    0x58, 0x54, 0x34, 0x07, 0x4B, 0x59, 0x42, 0x25, 0x3A, 0x05, 0x05, 0x4F, 0x41, 0x30, 0x01, 0x06,
    0x07, 0x38, 0x08, 0x0F, 0x0A, 0x41, 0x01, 0x1E, 0x3D, 0x2A, 0x58, 0x2A, 0x33, 0x1D, 0x15, 0x4D,
    0x16, 0x3B, 0x53, 0x33, 0x17, 0x23, 0x22, 0x49, 0x2B, 0x4F, 0x2A, 0x29, 0x03, 0x1D, 0x5A, 0x47,
    0x28, 0x16, 0x12, 0x1A, 0x30, 0x15, 0x32, 0x31, 0x36, 0x0B, 0x55, 0x05, 0x21, 0x37, 0x57, 0x50,
    0x2B, 0x45, 0x0D, 0x18, 0x08, 0x12, 0x13, 0x1D, 0x02, 0x5D, 0x55, 0x2E, 0x32, 0x02, 0x38, 0x3F,
    0x20, 0x2B, 0x2F, 0x22, 0x17, 0x40, 0x20, 0x04, 0x45, 0x0A, 0x37, 0x12, 0x0E, 0x32, 0x08, 0x13,
    0x58, 0x19, 0x48, 0x0D, 0x24, 0x15, 0x1B, 0x4D, 0x4C, 0x04, 0x47, 0x0A, 0x52, 0x23, 0x04, 0x5B,
    0x5E, 0x16, 0x02, 0x05, 0x4A, 0x4C, 0x50, 0x46, 0x4F, 0x17, 0x38, 0x25, 0x45, 0x36, 0x3E, 0x03,
    0x16, 0x0F, 0x53, 0x40, 0x48, 0x4C 
	);


	function __construct()
	{
		
	}
	/**
	 * Summary of writeLog
	 * @param string $sn
	 * @param string $deviceType
	 * @return string $logFile
	 */
	public function writeLog(string $sn, string $deviceType){
        if (!file_exists($_ENV['LOG_PATH'].deviceTypeArray[$deviceType])) {
            mkdir($_ENV['LOG_PATH'].deviceTypeArray[$deviceType], 0777, true);
        }
        $logFile = trim($sn).".txt";
		$fd = fopen($_ENV['LOG_PATH'].deviceTypeArray[$deviceType].$logFile, "a+");
		if($fd){
			fwrite($fd, $this->logTxt);
			fclose($fd);
            return $logFile;
		}else{
			echo "fd error";
		}
	}

	public function compareVersion($version, $version_test) {
		$version_split = explode(".", $version);
		$prefix = intval($version_split[0]);
		$suffix = intval($version_split[1]);
		if($version_test!=0){
			$version_split_test = explode(".", $version_test);
			$prefix_test = intval($version_split_test[0]);
			$suffix_test = intval($version_split_test[1]);
		
			if ($prefix_test > $prefix or ($prefix_test == $prefix and $suffix_test >= $suffix)) {
				//echo true;
				return true;
			}
		}
		else {
			return false;
		}
	}

	/**
	 * Get forced status from database
	 * @param array $deviceInfo
	 * @return int
	 */
	function getForced($deviceInfo) {
		if (isset($deviceInfo[FORCED_UPDATE]) && (($deviceInfo[FORCED_UPDATE] === '1') || isset($deviceInfo[FORCED_UPDATE]) && ($deviceInfo[FORCED_UPDATE] === 1))) {
			$forcedUpdate = 1;
		}
		else {
			$forcedUpdate = 0;
		}
		return $forcedUpdate;
	}

	function getFilenameFromVersion($version, $deviceType, $boardType){
		$versionData = explode(".",$version,8);
		$versionString = str_pad($versionData[0], 3 , "0" , STR_PAD_LEFT);
		if (!array_key_exists(1, $versionData)) {
			$revisionString = str_pad("0", 3 , "0" , STR_PAD_LEFT);
		}
		else {
			$revisionString = str_pad($versionData[1], 3 , "0" , STR_PAD_LEFT);
		}
		$fileName = stFILENAME."_".$deviceType."_".$boardType."_v".$versionString.'.'.$revisionString.extFILENAME;
		return $fileName;
	}

	/**
	 * Get VersionUpload from database
	 * @param array $deviceInfo
	 * @param int $boardType
	 * @param string $deviceType
	 * @param object $dataResponse
	 * @return string $fileName
	 */
	function getVersionUpload($deviceInfo, $boardType, $deviceType, $dbRequest) {
		if (isset($deviceInfo[VERSION_UPLOAD]) && !empty($deviceInfo[VERSION_UPLOAD]) && ($boardType<32768)) {
			$fileName = $this->getFilenameFromVersion($deviceInfo[VERSION_UPLOAD], $deviceType, 2);
		} else {
			$lastVersUp = $dbRequest->getDeviceTypeActualVers($deviceType);
			/*
			if (!$lastVersUp) { # default version not defined in database, keep the same version
				//$logger->error("<error>Default Version not defined for {$deviceType}. Keeping old version.</error>");
				echo "\r\nDefault Version not defined for {$deviceType}. Keeping the same version\r\n";
				$fileName = $this->getFilenameFromVersion($deviceInfo[DEVICE_VERSION], $deviceType, $boardType);
			}
			else {
			*/
				$fileName = $lastVersUp["name"];
			//}
		}
		$fileUp = $_ENV['PACK_PATH'].deviceTypeArray[$deviceType].$fileName;
		if (!file_exists($_ENV['PACK_PATH'] . deviceTypeArray[$deviceType])) {
			echo "\r\nPath ".$_ENV['PACK_PATH'] . deviceTypeArray[$deviceType]." not present on the server.\r\n";
		}
		else
		{
			if (!file_exists($fileUp)) {
				//$logger->error("<error>Package File {$fileUp} not present on the server. Keeping old version.</error>");
				echo "\r\nPackage File {$fileUp} not present on the server.\r\n";
				//$filename = $this->getFilenameFromVersion($deviceInfo[DEVICE_VERSION], $deviceType, $boardType);
				//return $filename;
				return false;
			} else {
				return $fileName;
			}
		}
	}

	public function getConfig($command, $data) {
		if($command === 'DE' || $command === 'DC' || $command === 'CD')
		{
			$boardType = hexdec(substr($data, 32, 4));
			if (intval($boardType)) {
				return $boardType;
			}
			else {
				$boardType = 2;
				return $boardType;
			}
		}
		else
		{
			$boardType = 2;
			return $boardType;
		}
	}

	public function getIndex($command, $data) {
		if($command ==='D8' || $command === 'CE' || $command === 'CB' || $command === 'D7')
		{
			$indexToGet = hexdec(substr($data, 24, 8));
		}
		elseif ($command == 'F5' || $command == 'F6' || $command == 'F7' || $command == 'F8' || $command == 'FC' || $command == 'FD') {
			$indexToGet = hexdec(substr($data, 24, 8));
		}
		elseif ($command == 'DC' || $command == 'CD') {
			$indexToGet = hexdec(substr($data, 36, 8));
			echo "Index to get: " . $indexToGet;
		}
		
		else {
			$indexToGet = 0;
		}
		
		return $indexToGet;
	}

	public function getDeviceVariables(string $data, DataResponse $dataResponse)
	{
		/** @var array $deviceObj
		 * ["Command"] = $command
		 * ["Serial Number"]
		 * ["Device Type"] = $deviceType
		 * ["Device Version"]
		 * ["Filename"]
		 * ["boardType"] = $boardType
		 * ["Index"]
		 * ["Request Id"]
		 * ["Forced Update"]
		 */

		/** @var mixed $data
		 * $deviceType = [3:4]
		 * $sn = [0:19]
		 * $command = [20:21]
		 * $boardType = [32:35]
		 * 
		 */
		$deviceObj = [
			"Command" => "",
			"Serial Number" => "",
			"Device Type" => "",
			"Device Version" => "",
			"Filename" => "",
			"BoardType" => "",
			"Index" => "",
			"Request Id" => "",
			"Forced Update" => "",
			"config" => "",
		];

		if(!empty($data)){
            if(isset($data[0]) && !empty($data[0]) && isset($data[1])){
                $command = (isset($data[20]) && isset($data[21]) !== "aa") ? $data[20].$data[21] : '';

				// Get command in data received
				$deviceObj["Command"] = $command;
				// Get device type in data received
				$deviceType = hexdec($data[3].$data[4]);
				$deviceObj["Device Type"] = $deviceType;
				/*
				if ($command=="DD" and $deviceType==14) {
					echo (strlen($data));
					echo ($data);
					exit;
				}
				*/
				if (in_array($command, cmdBack)) {
					if (strlen($data)<221) {
						echo ($data);
						//exit;
						/*
						for($i=14; $i<(14+206); $i++){
							$dataTemp = hexdec(bin2hex($data[$i])); //TODO string offset
							if($dataTemp === 127)
							{
								$dataTemp = 92 - 35 - $this->getserverCesarMatrixRxArray[($i-14)];
							}
							else 
							{
								$dataTemp = ((hexdec(bin2hex($data[$i]))-35) - $this->getserverCesarMatrixRxArray[($i-14)]); // TODO string offset
							}
							if($dataTemp < 0)
							{
								$data[$i] = chr($dataTemp+127);
							}
							else 
							{
								$data[$i] = chr($dataTemp+35);
							}
						}
						*/
					}
					for($i=28; $i<(28+206); $i++){
						$dataTemp = hexdec(bin2hex($data[$i])); //TODO string offset
						if($dataTemp === 127)
						{
							$dataTemp = 92 - 35 - $this->getserverCesarMatrixRxArray[($i-28)];
						}
						else 
						{
							$dataTemp = ((hexdec(bin2hex($data[$i]))-35) - $this->getserverCesarMatrixRxArray[($i-28)]); // TODO string offset
						}
						if($dataTemp < 0)
						{
							$data[$i] = chr($dataTemp+127);
						}
						else 
						{
							$data[$i] = chr($dataTemp+35);
						}
					}
				}
				// Define BoardType
				$boardType = $this->getConfig($command, $data);
				$deviceObj["boardType"] = $boardType;
				// Define IndexToGet
				if (($indexToGet = $this->getIndex($command, $data))!=0) {
					$deviceObj["Index"] = $indexToGet;
				}
					$sn = substr($data, 0, 20);
					$deviceObj["Serial Number"] = $sn;

					$this->reqId = isset($data[22]) ? hexdec($data[22].$data[23]) : 0;
					$deviceObj["Request Id"] = $this->reqId;
					
					switch ($command) {
						case 'F3':
							$length = 0;
							for($parse = 24; $parse < 238; $parse++){
								if($data[$parse] === "$")
								{
									break;
								}
								$this->logTxt .= $data[$parse];
								$length++;
							}
							$this->ptLogSave = $length;
							break;
						case 'DB':
							$length = 0;
							for($parse = 32; $parse < 238; $parse+=2)
							{
								$dataTemp = chr(hexdec($data[$parse].$data[$parse+1]));
								if($dataTemp === "$") //separator
									break;
								$this->logTxt .= $dataTemp;
								$length++;
							}
							$this->ptLogSave = $length;
							break;
						case 'F9':
						case 'FE':
						case 'FA':
								if(!empty($data[28]) || !empty($data[29]) || !empty($data[30]) || !empty($data[31])){			
									$version = hexdec($data[28].$data[29]).'.'.hexdec($data[30].$data[31]);
									$deviceObj["Device Version"] = $version;
								}
							break;
						case 'DD':
							//$this->config = isset($data[24]) ? hexdec($data[26].$data[27].$data[24].$data[25]) : 0; //TODO error when data not set
							if($data[25]==0 and $data[24]==0){
								$this->config = 0;	
							}
							else {	
								$this->config = hexdec($data[24].$data[25]);
							}
							$deviceObj["config"] = $this->config;
							break;
						case 'DE':
						case 'DC':
						case 'CD':
							$version = hexdec($data[28].$data[29]).'.'.hexdec($data[30].$data[31]);
							// TODO put boardType in database
							$deviceObj["Device Version"] = $version;
							break;
					case 'CF':
					case 'CE':
					case 'CC':
					case 'CB':
							if(!empty($data[0])){
								for($parse = 32; $parse < strlen($data) && $data[$parse]!='$'; $parse++){
									$this->path .= $data[$parse];
								}
							}
							break;
						default:
							# code...
							break;
					}
					return $deviceObj;
            }
        }

	}

	
	/**
	 * Create Database connection, get device information
	 * @param string $data
	 * @param string $ipAddr
	 * @param array $deviceInfo
	 * $this->responseArray[0] = $indexToGet;
	 * $this->responseArray[1] = $response.$footer;
	 * $this->responseArray[2] = $deviceInfo;
	 * $this->responseArray[3] = $percentage;
	 */
    public function start(string $data, string $ipAddr, array $deviceInfo) : false|array
	{
		$time_start_command = microtime(true);

		$dataResponse = new DataResponse();
		$request = new DbRequest();
		// DEFINE DEVICE VARIABLES
		$deviceObj = $this->getDeviceVariables($data, $dataResponse);
		$sn = $deviceObj["Serial Number"];
		$version = $deviceObj["Device Version"];
		$deviceType = $deviceObj["Device Type"];
		$command = $deviceObj["Command"];
		$reqId = $deviceObj["Request Id"];
		$indexToGet = intval($deviceObj["Index"]);
		$boardType = $deviceObj["boardType"];
		$deviceConfig = $deviceObj["config"];

	if ($command == 'DE' || $command == 'FE' || $command == 'F9') {
		$deviceTypeId = deviceTypeId[$deviceType];
		$deviceTypeName = deviceTypeName[$deviceType];
		$logFile = trim($sn).".txt";
		/** @var array $deviceInfo 
		 * Info available in database
		 * [SN] : sn
		 * [FORCED_UPDATE] : forced
		*/
		$request->initDeviceInSN($sn, $deviceTypeName);
		$deviceInfo = $request->setDeviceInfo($sn, $version, $deviceTypeId, $ipAddr, $logFile);
		$request->setDeviceToServer($sn);
		$this->responseArray[2] = $deviceInfo;
	}

		// SET FORCED //
		$forcedUpdate = $this->getForced($deviceInfo);

		$fileName = $this->getVersionUpload($deviceInfo, $boardType, $deviceType, $request);
		$deviceObj["Filename"] = $fileName;

        switch ($command) {
			/* ===== BACK COMMANDS ===== */
			//autoDetect BOARD, ASK_GMU_VERSION
			case "DE":
				$logTxt = "Version: ".$deviceInfo[DEVICE_VERSION]." Upload version: ".$deviceInfo[VERSION_UPLOAD]." Address: ".$deviceInfo[IP_ADDR]." Country: ".$deviceInfo[COUNTRY];
				$dataResponse->writeVersionLog($sn, $deviceType, $logTxt);
				//$fileContent = $dataResponse->setFileContent($dataResponse->getFileContent($deviceType, $fileName));
				$fileContent = $dataResponse->getChunk($_ENV['PACK_PATH'] . deviceTypeArray[$deviceType] . $fileName, FW_OCTETS);
				$dataResponse->setHeader($command, $this->reqId);
				$tempResponse = $dataResponse->setResponseData($fileContent);

				$tempResponse = $dataResponse->pointeurToResponse($sn, $deviceType, $tempResponse);

				$tempResponse[37] = $forcedUpdate;
				$finalResponse = $dataResponse->getDate($tempResponse);
				$pinCode = intval($deviceInfo[PIN_CODE]);
				$finalResponse[62] = chr(intval($deviceInfo[PUB_ACCEPTED]));
				$finalResponse[63] = chr($pinCode/256);
				$finalResponse[64] = chr($pinCode%256);

				//TODO $comment in database
				// length comment = 100
				if ($deviceType==14) {
					if ($this->compareVersion("3.12", $deviceInfo[VERSION_UPLOAD]) == true) {
						$firstComment = $request->getUpdateComment($request->getDeviceTypeId($deviceType), $deviceInfo[VERSION_UPLOAD]);
						if ($firstComment != "") {
							$firstComment = str_replace('\n', "\n", $firstComment);
							$endstr = 100-strlen($firstComment); //If comment lower than max size, add blank space
							$comment = $firstComment.str_repeat(" ", $endstr);
						}
						else {
							$comment = str_repeat(" ", 100);
						}
					}
					else {
						$comment = str_repeat(" ", 100);
					}
				}
				elseif ($deviceType==12) {
					if ($this->compareVersion("3.22", $deviceInfo[VERSION_UPLOAD]) == true) {
					//if ($this->compareVersion("3.15", $deviceInfo[VERSION_UPLOAD]) == true) {
						$firstComment = $request->getUpdateComment($request->getDeviceTypeId($deviceType), $deviceInfo[VERSION_UPLOAD]);
						if ($firstComment != "") {
							$firstComment = str_replace('\n', "\n", $firstComment);
							$endstr = 100-strlen($firstComment); //If comment lower than max size, add blank space
							$comment = $firstComment.str_repeat(" ", $endstr);
							//$comment = $firstComent;
						}
						else {
							$comment = str_repeat(" ", 100);
						}
					}
					else {
						$comment = str_repeat(" ", 100);
					}
				}

				/*
				if ($commentsString = $request->getUpdateComment($request->getDeviceTypeId($deviceType), $uploadVersion)) {
					echo ("\r\nComment String: " . $commentsString."\r\n");
				}
				else {
					$commentsString="Update version \nMaj touch and go";
					echo ("\r\nComment String: " . $commentsString."\r\n");
				}
				*/
				for($i = 0; $i < strlen($comment) ; $i++)
				{				
					$finalResponse[70 + $i] = $comment[$i];
				}
				$response = $dataResponse->getCesarMatrix($finalResponse);
				break;
			//multicommande: sous-commande 1: change l'IP Addresse à laquelle la machine se connecte, sous-commande 2: change la config
			case "DD":
				// case 1: IP adress
				// get config in db
				$request->setConfigDown($sn, $deviceConfig); //Update device config in db
				if ($deviceInfo[SERVER_ID] == 1) {
					$commandId = 1;
					$address = $deviceInfo[SERVER_IP];
					$port = $deviceInfo[SERVER_PORT];
					$input = $address.",".$port."\0";
					$contentSize = strlen($commandId)+strlen($input);
					$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
					$content = $dataResponse->setResponseToByte($commandId, 0);
					$tempResponse = $header.$content.$input;
					$response = $dataResponse->getCesarMatrix($tempResponse);
					// change to 0 in db
					$request->setConfigId($sn, 0, SERVER_ID);
					$deviceInfo[SERVER_ID] = 0;
				}
				else {
					// case 2: config
					// si demande de changement de config du côté du serveur
					$configId = $request->getConfigId($sn, CONFIG_ID);
					if ($configId == 1) {
						$commandId = 2; // indication sous-commande
						if ($deviceType==12 and ($this->compareVersion("3.30", $deviceInfo[DEVICE_VERSION]) == true or $deviceInfo[DEVICE_VERSION] == "0.45" or $deviceInfo[DEVICE_VERSION] == "0.47" or $deviceInfo[DEVICE_VERSION] == "0.48" or $deviceInfo[DEVICE_VERSION] == "0.49")) {
							$configUp = chr(intval($request->getConfigUp($sn))).chr(intval($request->getConfigUp($sn))>>8);
						}
						elseif ($deviceType==14 and ($this->compareVersion("3.16", $deviceInfo[DEVICE_VERSION]) == true or $deviceInfo[DEVICE_VERSION] == "0.1" or $deviceInfo[DEVICE_VERSION] == "0.4")) {
							$configUp = chr(intval($request->getConfigUp($sn))).chr(intval($request->getConfigUp($sn))>>8);
						}
						else {
							$configUp = chr(intval(0)).chr(intval(0)>>8);
						}
						$contentSize = strlen($commandId)+strlen($configUp);
						$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
						$content = $dataResponse->setResponseToByte($commandId, 0);
						$tempResponse = $header.$content.$configUp;
						$response = $dataResponse->getCesarMatrix($tempResponse);
						//$request->setConfigDown($sn, $configUp);
						$request->setConfigId($sn, 0, CONFIG_ID);
						$configId = 0;
						// réinitialiser config up une fois terminée
					}
					// si pas de demande de changement
					else {
						// commandId : 3, sous-commande
						// snId : boolean, demande de changement
						// snUp : new sn
						$snId = $request->getConfigId($sn, CONFIG_SN_ID);
						if ($snId == "1" or $snId == 1) {
							$commandId = 3;
							$snUpTrim = trim($deviceInfo[CONFIG_SN_UP]);
							$endstr = 20 - strlen($snUpTrim);
							$snUp = $snUpTrim . str_repeat("\0", $endstr)."\0";
							$contentSize = strlen($commandId)+strlen($snUp);
							$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
							$content = $dataResponse->setResponseToByte($commandId, 0);
							$tempResponse = $header.$content.$snUp;
							$response = $dataResponse->getCesarMatrix($tempResponse);
							// change to 0 in db
							$request->setConfigId($sn, 0, CONFIG_SN_ID);
							$deviceInfo[SERVER_ID] = 0;
							$snId = 0;
							//exit;
						}
						else {
							// commandId : 4, sous-commande
							// industriaId : boolean, demande de changement
							// industriaUp : industria status
							$industriaId = $request->getConfigId($sn, CONFIG_INDUS_ID);
							if ($industriaId == "1" or $industriaId == 1) {
								$commandId = 4;
								$industriaUp = $deviceInfo[CONFIG_INDUS_UP] . "\0";
								$contentSize = strlen($commandId)+strlen($industriaUp);
								$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
								$content = $dataResponse->setResponseToByte($commandId, 0);
								$tempResponse = $header.$content.$industriaUp;
								$response = $dataResponse->getCesarMatrix($tempResponse);
								$request->setConfigId($sn, 0, CONFIG_INDUS_ID);
								$industriaId = 0;
								$deviceInfo[CONFIG_INDUS_ID] = 0;
								# industriaId
							}
							else {
								$commandId = 0;
								$config = 0;
								$contentSize = strlen($commandId) + strlen($config);
								$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
								$serverContent = $dataResponse->setResponseToByte($commandId, 0);
								$configContent = $dataResponse->setResponseToByte(0, 0);
								$tempResponse = $header.$serverContent.$configContent;
								$response = $dataResponse->getCesarMatrix($tempResponse);
							}
						}
					}
				}
				break;
			//télécharger l'image
			case "D7":
				/*
				// si l'id dans la bdd est égal à 1
				if ($deviceInfo[IMAGE_ID] != 0) {
					$commandId = 1;
					if ($deviceInfo[IMAGE_ID] == 1) {
						$image_path = "C:\wamp64\www\public\winback\public\Ressource\images\\".deviceTypeArray[$deviceType].trim($sn).$deviceInfo[IMAGE_UP];
						//$image_path_copy = 
						$size = filesize($image_path)-$indexToGet;
						$filesize =  filesize($image_path);
						if ($indexToGet == $filesize or $indexToGet > $filesize) {
							$request->setImageId($sn, 0);
							$deviceInfo[IMAGE_ID] = 0;
							$commandId = 0;
						}	
						if($size>4095)$size=4095;
						$sizeCopy = strlen($commandId) + $size;
						$header = $dataResponse->setHeader($command, $this->reqId, $sizeCopy);
						$content = $dataResponse->setResponseToByte($commandId, 0);
						$input = $dataResponse->getImageFile(deviceType[$deviceType], $image_path, $indexToGet, $size);
						$tempResponse = $header . $content . $input;
						$response = $dataResponse->getCesarMatrix($tempResponse);
					}
					elseif ($deviceInfo[IMAGE_ID] == 3) { // delete image on device
						$config = $deviceInfo[IMAGE_ID];
						$contentSize = strlen($config);
						$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
						//$serverContent = $dataResponse->setResponseToByte($commandId, 0);
						$configContent = $dataResponse->setResponseToByte($config, 0);
						$tempResponse = $header.$configContent;
						echo "\r\nD7 Response 3: " . bin2hex($tempResponse) . "\r\n";
						$response = $dataResponse->getCesarMatrix($tempResponse);
						echo "\r\nD7 Response 3: " . bin2hex($response) . "\r\n";
					}
				}
				else {
				*/
					$commandId = 0;
					$config = 0;
					$contentSize = strlen($commandId) + strlen($config);
					$header = $dataResponse->setHeader($command, $this->reqId, $contentSize);
					$serverContent = $dataResponse->setResponseToByte($commandId, 0);
					$configContent = $dataResponse->setResponseToByte(0, 0);
					$tempResponse = $header.$serverContent.$configContent;
					$response = $dataResponse->getCesarMatrix($tempResponse);
				//}
				break;
			//synchro directory protocol
			case "CC":
				$subtype = $request->getConfigDown($sn);
				$dataResponse->setHeader($command, $this->reqId, 0);
				$response = $dataResponse->getProtocolDirectoryData($this->path, deviceType[$deviceType], $boardType, $subtype);
				for($i=6;$i<strlen($response);$i++)$response[$i]=chr(hexdec(bin2hex($response[$i]))+$this->getserverCesarMatrixTxArray[($i-6)%214]);
				break;
			//download protocol
			case "CB":
				$directoryPath = $_ENV['PROTO_PATH'] . deviceType[$deviceType] .$this->path;
				$pattern = "/\/" . $deviceConfig . "/";
				$directoryPath = preg_replace($pattern, "", $directoryPath, 1);
				if(file_exists($directoryPath)) {
					$size=(filesize($directoryPath)-$indexToGet);
					if($size>4096)$size=4096;
					$dataResponse->setHeader($command, $this->reqId, $size);
					$tempResponse = $dataResponse->getFile4096Bytes($directoryPath, $indexToGet, $size);
					$response = $dataResponse->getCesarMatrix($tempResponse);
				}
				else {
					$dataResponse->setHeader($command, $this->reqId, 0);
					$tempResponse = $dataResponse->getFile4096Bytes($directoryPath);
					$response = $dataResponse->getCesarMatrix($tempResponse);
				}
				break;
			//Download BOARD //Download Version
			case "DC":
			case "CD":
				// * Empêche la machine de rester forcée après un 1er téléchargement *//
				if ($deviceInfo[FORCED_UPDATE] == 1) {
					$request->setForced($sn, 0);
					$deviceInfo[FORCED_UPDATE] = 0;
					$forcedUpdate = 0;
				}
				//* return index in tcpserver to not send response if index is repeated*//
				$this->responseArray[0] = $indexToGet;
				$filesize =  filesize($_ENV['PACK_PATH'].deviceTypeArray[$deviceType].$fileName);
				$percentage = intval(($indexToGet/$filesize)*100);
				if ($percentage == 0 && $indexToGet == 4096 || $percentage == 99 && ($filesize-$indexToGet) < 4096) {
					$dataResponse->writeCommandLog($sn, $deviceType, "\r\n".date("Y-m-d H:i:s | ").$indexToGet."/".filesize($_ENV['PACK_PATH'].deviceTypeArray[$deviceType].$fileName) . ' bytes - '.$percentage." %\r\n");
					if ($percentage == 99 && ($filesize-$indexToGet) < 4096) {
						$request->setDownload($sn, 100);
						$percentage = 100;
					}
				}
				$this->responseArray[3] = $percentage;

				$fileContentArray = $dataResponse->setFileContent4096Bytes($_ENV['PACK_PATH'].deviceTypeArray[$deviceType].$fileName, $indexToGet);
				$fileContent = $fileContentArray[0];
				$nbDataToSend = $fileContentArray[1];
				$dataResponse->setHeader($command, $this->reqId, $nbDataToSend);

				$response = $dataResponse->getCesarMatrix(
					$tempResponse = $dataResponse->setResponseData($fileContent)
				);
				break;
			//Load & copy Logs
			case "DB":
				$this->writeLog($sn, $deviceType);
			//resend logs pointer
			case "D9":
				$newPointeur = $dataResponse->getPointeur($sn, $deviceType);
				//$request->setLog($sn, $newPointeur);
				$dataResponse->setHeader("DB", $this->reqId, 11);
				$response = $dataResponse->getCesarMatrix(
					$tempResponse = $dataResponse->getLogByPointer($newPointeur)
				);
				break;
			//Pubs ask date
			case "DA":
				$dataResponse->setHeader($command, $this->reqId, 9);
				$response = $dataResponse->getCesarMatrix(
					$tempResponse = $dataResponse->getPubsData(deviceTypeArray[$deviceType])
				);
				break;
			//Download PUBS
		    case "D8":
				$size = (filesize($_ENV['PUB_PATH'].deviceTypeArray[$deviceType]."PUBS.bin")-$indexToGet);
				if($size>4096)$size=4096;
				$dataResponse->setHeader($command, $this->reqId, $size);
				$response = $dataResponse->getCesarMatrix(
					$tempResponse = $dataResponse->getPubsFile(deviceType[$deviceType], $indexToGet, $size)
				);
				break;
			//Synchro library directory
			case "CF":
				$dataResponse->setHeader($command, $this->reqId, 0);
				$finalResponse = $dataResponse->getSynchroDirectoryData($this->path, deviceType[$deviceType], $boardType);
				$response = $dataResponse->getCesarMatrix($finalResponse);		
				break;
			//Download library files
			case "CE":
				if ($_ENV['APP_ENV'] == 'dev') {
					$directoryPath = $_ENV['LIB_PATH'] . deviceType[$deviceType] . $boardType.'/'.$this->path;
				}
				else {
					$directoryPath = $_ENV['LIB_PATH'] . deviceType[$deviceType] . $this->path;
				}
				$size=(filesize($directoryPath)-$indexToGet);
				if($size>4096)$size=4096;
				$dataResponse->setHeader($command, $this->reqId, $size);
				$tempResponse = $dataResponse->getFile4096Bytes($directoryPath, $indexToGet, $size);
				$response = $dataResponse->getCesarMatrix($tempResponse);
				break;

		/* ===== RSHOCK COMMANDS ===== */
			//UART_CMD_AUTODETECT //Ready To Receive
			case "FE":
				$logTxt = "Version: ".$deviceInfo[DEVICE_VERSION]." Upload version: ".$deviceInfo[VERSION_UPLOAD]." Address: ".$deviceInfo[IP_ADDR]." Country: ".$deviceInfo[COUNTRY];
				$dataResponse->writeVersionLog($sn, $deviceType, $logTxt);
				$dataResponse->setHeader($command, $this->reqId, 39);
				$fileContent = $dataResponse->getFileContent($deviceType, $fileName);
				$startOffset = $dataResponse->getIndexForImg($fileContent);
				/*
				$fileContent2 = $dataResponse->getChunk($_ENV['PACK_PATH'] . deviceTypeArray[$deviceType] . $fileName, 1024);
				$sizeContent = $dataResponse->getCRCAutoDetect(FW_OCTETS, $fileContent2);
				$tempResponse = $dataResponse->autoDetectBody($sizeContent, $fileContent2, $forcedUpdate);
				*/
				$sizeContent = $dataResponse->getCRCAutoDetect($startOffset, $fileContent);
				$tempResponse = $dataResponse->autoDetectBody($sizeContent, $fileContent, $forcedUpdate);
				$response = $dataResponse->setResponseData($tempResponse);
				break;
			//Ready To Receive
			case "F9":
				$logTxt = "Version: ".$deviceInfo[DEVICE_VERSION]." Upload version: ".$deviceInfo[VERSION_UPLOAD]." Address: ".$deviceInfo[IP_ADDR]." Country: ".$deviceInfo[COUNTRY];
				$dataResponse->writeVersionLog($sn, $deviceType, $logTxt);
				$request->setConnect('1', $sn);
				$request->setVersion($version, $sn);
				$newPointeur = $dataResponse->getPointeur2($sn, $deviceType);
				$dataResponse->setHeader($command, $this->reqId, 11);
				$content = $dataResponse->setResponseToByte($newPointeur, 11);
				$response = $dataResponse->setResponseData($content);
				break;
			//UART_CMD_UPDATE_PICTURES //update version
			case "FD":
				if ($deviceInfo[FORCED_UPDATE] == 1) {
					$request->setForced($sn, 0);
					$deviceInfo[FORCED_UPDATE] = 0;
					$forcedUpdate = 0;
				}
				$totalFileContent = $dataResponse->getFileContent($deviceType, $fileName);
				$filesize = strlen($totalFileContent);
				$percentage = intval(($indexToGet/$filesize)*100);
				$dataResponse->writeCommandLog($sn, $deviceType, "\r\n".date("Y-m-d H:i:s | ").$indexToGet."/".$filesize . ' bytes - '.$percentage." %\r\n");

				$startOffset = $dataResponse->getIndexForImg($totalFileContent);
				$fileContent = $dataResponse->setFileContent($totalFileContent, $indexToGet, $startOffset);
				$dataResponse->setHeader($command, $this->reqId);
				$response = $dataResponse->setResponseData($fileContent);
				break;
			//UART_CMD_UPDATE_SUBPROG4 //update version
			case "FC":
			case "F8":
			case "F7":
			case "F6":
			case "F5":
				$totalFileContent = $dataResponse->getFileContent($deviceType, $fileName);
				$filesize = strlen($totalFileContent);
				$percentage = intval(($indexToGet/$filesize)*100);
				$dataResponse->writeCommandLog($sn, $deviceType, "\r\n".date("Y-m-d H:i:s | ").$indexToGet."/".$filesize . ' bytes - '.$percentage." %\r\n");
				$startOffset = $dataResponse->getIndexForProg($command, $totalFileContent);
				$fileContent = $dataResponse->setFileContent($totalFileContent, $indexToGet, $startOffset);
				$dataResponse->setHeader($command, $this->reqId);
				$response = $dataResponse->setResponseData($fileContent);
				break;

			//Receive log file
            case "F3":				
				$this->writeLog($sn, $deviceType);
				$newPointeur = $dataResponse->getPointeur2($sn, $deviceType);
				$dataResponse->setHeader($command, $this->reqId, 11);
				$content = $dataResponse->setResponseToByte($newPointeur, 11);
				$response = $dataResponse->setResponseData($content);
				break;

			//Close log
            case "F2":
				//$request->setConnect('1', $sn);
				$dataResponse->setHeader($command, $this->reqId, 0);
				$response = $dataResponse->setResponseData(); // Send header with empty response to indicate ends of logs to device
				break;

            case "FA":
				$dataResponse->setHeader($command, $this->reqId, 0);
				$response = $dataResponse->setResponseData();
                break;

            default:
                break;

		
		}
		
		$sFooter = $dataResponse->setFooter($response);
		$this->responseArray[1] = $response.$sFooter;
		$time_end_command = microtime(true);
		$execution_time_command = ($time_end_command - $time_start_command)*1000;
		if ($command != "DC" and $command != "CD" and $command != "FD" and $command != "FC") {
			$dataResponse->writeCommandLog($sn, $deviceType, "\r\n".date("Y-m-d H:i:s | ")." \n| SN : ".$sn."\n| Command : ".$command."\r\n");
		}
		if ($execution_time_command > 100) {
			//echo "\r\nTime Alert: Command takes more than 100 ms:".$execution_time_command."\r\n";
			$dataResponse->writeCommandLog($sn, $deviceType, "\r\nTime Alert: Command takes more than 100 ms:".$execution_time_command."\r\n");
		}
		return $this->responseArray;
    }
}
