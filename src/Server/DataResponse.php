<?php
namespace App\Server;

use Monolog\Logger;
require_once("Utils.php");
require_once("DbRequest.php");



class DataResponse extends Utils
{
    private $fileName;
    private $header;
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

    function __construct() {
        
    }

    /**
     * Returns specific substracted data from file content, the beginning of data corresponds to startOffset & the data length corresponds to fromIndex
     *
     * @param string $fileContent //$fileContent extracted with getFileContent
     * @param integer $fromIndex
     * @param integer $startOffset
     * @return string
     */
    function setFileContent(string $fileContent, $fromIndex = 0, $startOffset = 0){
        $startOffset += $fromIndex;
        $fileContentSubstr = substr($fileContent, $startOffset, $length = FW_OCTETS); // Get content of file from a specific index
        return $fileContentSubstr;
    }

    function getChunk($filename, $chunkSize, $fromIndex = 0, $startOffset = 0) {
        $startOffset += $fromIndex;
        $fileHandle = fopen($filename, 'rb');

        if ($fileHandle === false) {
            die('Unable to open file.');
        }
        
        if ($fromIndex != 0) {
            fseek($fileHandle, $fromIndex);
        }
        while (!feof($fileHandle)) {
            $chunk = fread($fileHandle, $chunkSize);
            //echo strlen($chunk);
            flush();
            return $chunk;
        }
        
        fclose($fileHandle);
    }
    /**
     * Summary of getIndexForImg
     * @param mixed $fileContent
     * @return int
     */
    function getIndexForImg($fileContent){
        $aIndex[0] = hexdec(bin2hex($fileContent[18]))*256;
        $aIndex[1] = hexdec(bin2hex($fileContent[19]));
        $startOffset = $aIndex[0] + $aIndex[1];
        return intval($startOffset);
    }
    
    /**
     * Summary of getIndexForProg
     * @param string $command
     * @param string $fileContent
     * @return int
     */
    function getIndexForProg(string $command, string $fileContent){
        $listProgIndex = 
            array(
                "FC" => array(8,9,10,11),
                "F8" => array(28,29,30,31),
                "F7" => array(36,37,38,39),
                "F6" => array(44,45,46,47),
                "F5" => array(52,53,54,55)
            );

        $aIndex[0] = hexdec(bin2hex($fileContent[$listProgIndex[$command][0]]))*256*256*256;
        $aIndex[1] = hexdec(bin2hex($fileContent[$listProgIndex[$command][1]]))*256*256;
        $aIndex[2] = hexdec(bin2hex($fileContent[$listProgIndex[$command][2]]))*256;
        $aIndex[3] = hexdec(bin2hex($fileContent[$listProgIndex[$command][3]]));
        $startOffset = $aIndex[0] + $aIndex[1] + $aIndex[2] + $aIndex[3];
        return intval($startOffset);
    }

    /**
     * Extract content from file, between startOffset and 4096
     * @param string $fileName
     * @param int $fromIndex
     * @param int $startOffset
     * @return array
     */
    function setFileContent4096Bytes($fileName, $fromIndex = 0, $startOffset = 0){
        $startOffset += $fromIndex;
        //Export to a new file
        $fileContentFromIndex = file_get_contents($fileName, $use_include_path=false, $context=null, $offset=$startOffset, $length=4096);
        $resultArray = array($fileContentFromIndex, strlen($fileContentFromIndex));
		return $resultArray;
    }

    /**
     * Header of 6 bytes
     * @param string $cmd
     * @param int $reqId
     * @param int $dataSize
     * @return string
     */
    function setHeader(string $cmd, int $reqId, $dataSize=FW_OCTETS){
        $this->header = chr(hexdec('AA')).chr(0).chr($reqId).chr(hexdec($cmd)).chr(intval($dataSize/256)).chr($dataSize%256);
        return $this->header;
    }
    
    /**
     * Summary of setFooter
     * @param string $oTram
     * @return string
     */
    function setFooter($oTram){
        $tramSize = 0;
        for($parse = 0; $parse < strlen($oTram); $parse++){
			$tramSize = $tramSize + (hexdec(bin2hex($oTram[$parse])) * (($parse%255)+1));
        }
        return chr($tramSize);
    }
	
    /**
     * return structure of library folder to the device
     * @param string $path
     * @param string $device
     * @param string $config - boardType
     * @return string $Response
     */
    function getSynchroDirectoryData($path, $device, $config){
        if ($_ENV['APP_ENV'] == 'dev') {
            $pathSynchro = $_ENV['LIB_PATH'] . $device . $config.'/' . $path;
        }
        else {
            $pathSynchro = $_ENV['LIB_PATH'] . $device . $path;
        }
        if (file_exists($pathSynchro)) {
            $listFiles = array_values(array_diff(scandir($pathSynchro), array('..', '.'))); //list files, delete '.' in directory array, reset array keys
            $directoryListString='';
            for($i=0; $i<count($listFiles); $i++){
                if (strpos($listFiles[$i], '.')) {
                    $handle = fopen($pathSynchro . $listFiles[$i], 'rb');
                    if ($handle) {
                        $contents = fread($handle, 9);
                        fclose($handle);
                        $version = hexdec(bin2hex($contents[7]));
                        $revision = hexdec(bin2hex($contents[8]));
                        $directoryListString .= $listFiles[$i] . ',' . ((intval($version / 100)) % 10) . ((intval($version / 10)) % 10) . ($version % 10) . ((intval($revision / 100)) % 10) . ((intval($revision / 10)) % 10) . ($revision % 10) . '|';
                    }
                } else 
                    $directoryListString .= $listFiles[$i] . '|';
            }
            $dataSize=strlen($directoryListString);
            $this->header[4]=chr(intval($dataSize/256));
            $this->header[5]=chr($dataSize%256);
            $Response = $this->header.$directoryListString;
            return $Response;
        }
        else {            
            $directoryListString = false;
            $this->header[4]=chr(0);
            $this->header[5]=chr(0);
            $Response = $this->header.$directoryListString;
            return $Response;
        }
	}
    
    /**
     * Summary of getProtocolDirectoryData
     * @param mixed $path
     * @param mixed $device
     * @param mixed $config
     * @param mixed $subtype sub device type present in the serial number in the form of B4, EM or NE
     * @return string
     */
    function getProtocolDirectoryData($path, $device, $config, $subtype){
            $pathProto = $_ENV['PROTO_PATH'] . $device . $config.'/' . $subtype . '/' . $path;
            if (file_exists($pathProto)) {
                echo "\r\n".$pathProto."\r\n";
                echo "\r\nsubtype :" . $subtype."\r\n";
                $listFiles = scandir($pathProto);
                $listFiles = array_slice($listFiles, 0, 102);
                $directoryListString='';
                for($i=0;$i<count($listFiles);$i++)if($listFiles[$i][0]!='.'){
                    if(strpos($listFiles[$i],'.')){
                        $handle = fopen($pathProto.$listFiles[$i], 'rb');
                        if($handle){
                            $contents=fread($handle, 9);
                            fclose($handle);
                            $version=hexdec(bin2hex($contents[7]));
                            $revision=hexdec(bin2hex($contents[8]));
                            $directoryListString.=$listFiles[$i].','.((intval($version/100))%10).((intval($version/10))%10).($version%10).((intval($revision/100))%10).((intval($revision/10))%10).($revision%10).'|';
                        }
                    }else $directoryListString.=$listFiles[$i].'|';
                }
                echo "\r\ndirectory liststring: " . $directoryListString."\r\n";
                $dataSize=strlen($directoryListString);
                $this->header[4]=chr(intval($dataSize/256));
                $this->header[5]=chr($dataSize%256);
                $Response = $this->header.$directoryListString;
                return $Response;
            }
            else {
                echo "\r\n".$pathProto."\r\n";
                echo "\r\nsubtype :" . $subtype."\r\n";
                $directoryListString = false;
                $this->header[4]=chr(0);
                $this->header[5]=chr(0);
                $Response = $this->header.$directoryListString;
                echo "false CC: " . bin2hex($Response);
                return $Response;
            }
	}

    /**
     * Write command logs to logfile with sn as filename
     * @param string $sn
     * @param string $deviceType
     * @param string $logTxt
     * @return string $logFile
     */
	function writeCommandLog(string $sn, string $deviceType, string $logTxt){
        $path = $_ENV['LOG_PATH']."command/".DEVICE_TYPE_ARRAY[$deviceType];
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		$logFile = trim($sn).".txt";
        if (file_exists($path.$logFile) && filesize($path.$logFile) < 1000000) {
            $fd = fopen($_ENV['LOG_PATH']."command/".DEVICE_TYPE_ARRAY[$deviceType].$logFile, "a+");
            if($fd){
                fwrite($fd, $logTxt);
                fclose($fd);
                return $logFile;
            }else{
                echo "fd error";
            }
        }
        else {
            $fd = fopen($_ENV['LOG_PATH']."command/".DEVICE_TYPE_ARRAY[$deviceType].$logFile, "w");
            if($fd){
                fwrite($fd, $logTxt);
                fclose($fd);
                return $logFile;
            }else{
                echo "fd error";
            }
        }
	}

    function writeVersionLog(string $sn, string $deviceType, string $inputTxt){
        $path = $_ENV['LOG_PATH']."version/".DEVICE_TYPE_ARRAY[$deviceType];
        $logTxt = "\r\n".date("Y-m-d H:i:s | ").$inputTxt."\r\n";
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		$logFile = trim($sn).".txt";
        if (file_exists($path.$logFile) && filesize($path.$logFile) < 1000) {
            $fd = fopen($_ENV['LOG_PATH']."version/".DEVICE_TYPE_ARRAY[$deviceType].$logFile, "a+");
            if($fd){
                fwrite($fd, $logTxt);
                fclose($fd);
                return $logFile;
            }else{
                echo "fd error";
            }
        }
        else {
            $fd = fopen($_ENV['LOG_PATH']."version/".DEVICE_TYPE_ARRAY[$deviceType].$logFile, "w");
            if($fd){
                fwrite($fd, $logTxt);
                fclose($fd);
                return $logFile;
            }else{
                echo "fd error";
            }
        }
	}

    function getPubsData(string $deviceType){
        $handle = fopen($_ENV['PUB_PATH'].$deviceType."PUBS".extFILENAME, 'rb');
		if($handle){
			$contents = fread($handle, 5); // Get 5 first characters of contents
			fclose($handle);
            $pubSize = filesize($_ENV['PUB_PATH'].$deviceType."PUBS".extFILENAME);
            // Concatenate header, contents & pubsize to form response
            $response = $this->header.$contents.chr(intval($pubSize/256/256/256)).chr(intval($pubSize/256/256)).chr(intval($pubSize/256)).chr(intval($pubSize%256));
            return $response;
		}
		else
        {
            echo "\r\nError: Check that pubs folder exists for {$deviceType}.\r\n";
        }
	}
	
    /**
     * Get content of pub file and build response with header & content
     * @param string $deviceType
     * @param int $fromIndex
     * @param int $size
     * @return bool|string
     */
    function getPubsFile(string $deviceType, $fromIndex = 0, $size = 0){
		
		$contents = file_get_contents($_ENV['PUB_PATH'].$deviceType."PUBS.bin");	
		if ($contents) {
            $response = $this->header.substr($contents, $fromIndex, $size);
            for($aInit = strlen($response); $aInit < ($size+6); $aInit++){
                $response[$aInit] = chr(255);
            }
            return $response;
        }
        else
        {
            echo "\r\nError: Check that pubs file exists for {$deviceType}.\r\n";
            return false;
        }
	} 

    function getImageFile(string $deviceType, $pathImage, $fromIndex = 0, $size = 0){
		
		$contents = file_get_contents($pathImage);	
		if ($contents) {
            $response = substr($contents, $fromIndex, $size);
            for($aInit = strlen($response); $aInit < ($size); $aInit++){
                $response[$aInit] = chr(255);
            }
            return $response;
        }
        else
        {
            echo "\r\nError: Check that image file exists for {$deviceType}.\r\n";
            return false;
        }
	}

    /**
     * Summary of getFile4096Bytes
     * @param string $path
     * @param string $device
     * @param int $fromIndex
     * @param int $size
     * @return string
     */
    function getFile4096Bytes($directoryPath, $fromIndex = 0, $size = 0){
        if (file_exists($directoryPath)) {
            $contents = file_get_contents($directoryPath);
            $Response = $this->header.substr($contents, $fromIndex, $size);
            for($aInit = strlen($Response); $aInit < ($size+6); $aInit++){
                $Response[$aInit] = chr(255);
            }
            //echo "\r\nResponse: " . bin2hex($Response) . "\r\n";
            return $Response;
        }
        else {
            $Response = $this->header;
            //$Response = $this->header;
            //echo "\r\nResponse: " . bin2hex($Response) . "\r\n";
            return $Response;
        }
	}

    /**
     * Summary of getCRCAutoDetect
     * @param string $deviceType
     * @param int $startOffset
     * @param string $fileName
     * @return string
     */
    //function getCRCAutoDetect(string $deviceType, $startOffset, $fileName)
    function getCRCAutoDetect($startOffset, $crcFileContent)
    {
        //$crcFileContent = $this->getFileContent($deviceType, $fileName);
        $fileContentCRC = substr($crcFileContent, $startOffset, strlen($crcFileContent) - $startOffset);
        $sizeContent = 0;
        for($parse = 0; $parse < strlen($fileContentCRC); $parse++){
            $sizeContent = $sizeContent + (hexdec(bin2hex($fileContentCRC[$parse])));
        }
        return chr($sizeContent);
    }
    
    /**
     * Initialize response array with 39 empty elements
     * @param int $nbData
     * @return array<string>
     */
    function initAutoDetectResponse($nbData = 39){
        for($aInit = 0; $aInit < $nbData; $aInit++){
            $aResponse[$aInit] = chr(0);
        }
        return $aResponse;
    }

    /**
     * Fill tempResponse with elements in fileContent
     * @param string $sizeContent
     * @param string $fileContent
     * @param mixed $forced [0 or 1]
     * @return string
     */
    function autoDetectBody(string $sizeContent, string $fileContent, int $forced = 0){
        $aResponse = $this->initAutoDetectResponse(); // Init aResponse content

        $aResponse[0] = $fileContent[4];

        $aResponse[7] = $fileContent[2];
        $aResponse[8] = $fileContent[3];

        $aResponse[9] = $fileContent[12];
        $aResponse[10] = $fileContent[13];
        $aResponse[11] = $fileContent[14];
        $aResponse[12] = $fileContent[15];

        $aResponse[13] = $fileContent[20];
        $aResponse[14] = $fileContent[21];
        $aResponse[15] = $fileContent[22];
        $aResponse[16] = $fileContent[23];
        $aResponse[17] = $fileContent[24];
        $aResponse[18] = $fileContent[25];
        $aResponse[19] = $fileContent[26];
        $aResponse[20] = $fileContent[27];
		
        $aResponse[21] = $fileContent[32];
        $aResponse[22] = $fileContent[33];
        $aResponse[23] = $fileContent[34];
        $aResponse[24] = $fileContent[35];
		
        $aResponse[25] = $fileContent[40];
        $aResponse[26] = $fileContent[41];
        $aResponse[27] = $fileContent[42];
        $aResponse[28] = $fileContent[43];
		
        $aResponse[29] = $fileContent[48];
        $aResponse[30] = $fileContent[49];
        $aResponse[31] = $fileContent[50];
        $aResponse[32] = $fileContent[51];
		
        $aResponse[33] = $fileContent[56];
        $aResponse[34] = $fileContent[57];
        $aResponse[35] = $fileContent[58];
        $aResponse[36] = $fileContent[59];

        $aResponse[37] = chr($forced);
		
        $aResponse[38] = $sizeContent;

        $aResponse = implode('', $aResponse);
        return $aResponse;
	}

    /**
     * Join temporary response in string, concatenate header & string
     * --> same as getResponseData
     * @param string $aResponse
     * @return string $response
     */
    function getAutoDetectResponse($aResponse){
        $response = $this->header.$aResponse;
        return $response;

    }

    /**
     * Concatenate header & response content
     * @param string $content
     * @return string $response
     */
    function setResponseData($content='') : string
    {
        $response = $this->header.$content;
        return $response;
    }
	
    /**
     * For a pointer of 5 digits:
     * Initiate a response array of size 11
     * response 6,7,8,9,10 = pointer 1,2,3,4,5
     *
     * @param string $pointer
     * @return string $aResponse
     */
	function setResponseLog($pointer = 0){
        $aResponse = array_fill(0, 11, chr(0)); //Init response array filled with n zeros
        $ptLength = strlen($pointer);
		$parse = 0;
		for($i = $ptLength - 1; $i >= 0 ; $i--){
			$aResponse[10 - $i] = chr($pointer[$parse]);
			$parse++;
		}
        $aResponse = implode('', $aResponse);
        return $aResponse;
	}
	
    /**
     * Convert integer to chr to send in response
     * @param int $number
     * @return string
     */
    function setResponseToByte($number=0, $prefix=0) {
        $array  = array_map('intval', str_split($number));
        $response = array();
        foreach ($array as $key=>$value) {
            $response[]=chr($value);
        }
        $response = implode('', $response);
        if ($prefix!=0) {
            $sub = $prefix - strlen($number);
            $preResponse = str_repeat(chr(0), $sub);
            $response = $preResponse . $response;
        }
        //echo "\r\nresponse".$response."\r\n";
        //echo "\r\nresponse to byte".bin2hex($response)."\r\n";
        return $response;
    }

    /**
     * Summary of getLogByPointer
     * @param mixed $pointer
     * @return string
     */
	function getLogByPointer($pointer = 0){
		$aResponse = $this->setResponseLog($pointer);
		//$response = $this->getAutoDetectResponse($aResponse);
        $response = $this->setResponseData($aResponse);
		return $response;
	}

    /**
     * Encode response with a cesar matrix
     * Header is length 6, do not encode header with i = 6
     * @param string $tempResponse
     * @return string
     */
    function getCesarMatrix($tempResponse)
    {
        for($i=6; $i<strlen($tempResponse);$i++)
        {
            $tempResponse[$i] = chr(hexdec(bin2hex($tempResponse[$i])) + $this->getserverCesarMatrixTxArray[($i-6)%214]);
        }
        return $tempResponse;
    }

    function getDate($response)
    {
        $dateString = date("Y-m-d H:i:s");
        $punkt = [":", "-", " "]; // special characters to be deleted from datestring
        $dateString = str_replace($punkt, "", $dateString);
        $dateString = substr($dateString, 2, strlen($dateString)-2);
        for($i = 0; $i < strlen($dateString) ; $i++) // Copy datestring in response and omitting two first characters of year
        {				
            $response[50 + $i] = $dateString[$i];
        }
        return $response;
    }

    /**
     * Filesize of logfile if it exist to be used as a pointeur to copy data from device
     *
     * @param string $sn
     * @param string $deviceType
     * @return string $newPointeur
     */
    function getPointeur($sn, $deviceType)
    {
        $path = $_ENV['LOG_PATH'].DEVICE_TYPE_ARRAY[$deviceType].trim($sn).".txt";
        if(file_exists($path)){
            $newPointeur = filesize($path);
            //echo "\r\nPointeur before: ".$newPointeur."\r\n";
            /*
            if (($newPointeur%10000000)>= 9999458) {
                $newPointeur+=(10000000-($newPointeur%10000000));
            echo "\r\nPointeur after: ".$newPointeur."\r\n";
            }
            */
        }
        else {
            $newPointeur = 0;
        }
        $newPointeur = strval($newPointeur);
        return $newPointeur;
    }
    
    
    function getPointeur2($sn, $deviceType)
    {
        $path = $_ENV['LOG_PATH'].DEVICE_TYPE_ARRAY[$deviceType].trim($sn).".txt";
        if(file_exists($path)){
            $newPointeur = filesize($path);
        }
        else {
            $newPointeur = 0;
        }
        $newPointeur = strval($newPointeur);
        return $newPointeur;
    }
    
    /**
     * Get the pointer/size of log file, init pointer in response
     * - ex: ptLength = 7, i = 6 (taking account index 0), pointer is init in response
     * @param string $sn
     * @param int $deviceType
     * @param string $temporaryResponse
     * @return string
     */
    function pointeurToResponse($sn, $deviceType, $temporaryResponse)
    {
        $newPointeur = $this->getPointeur($sn, $deviceType);
        $ptLenght = strlen($newPointeur);
        $parse = 0;
        for($i = $ptLenght - 1; $i >= 0 ; $i--){				
            $temporaryResponse[49 - $i] = chr($newPointeur[$parse]);
            $parse++;
        }
        return $temporaryResponse;
    }
}