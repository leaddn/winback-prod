<?php
namespace App\Server;

use Monolog\Logger;

ini_set('memory_limit','128M');

class Utils {
    
    //private $deviceType;
    private $deviceFamily;
    private $deviceFamilyRepository;
    private $dbRequest;
    function __construct() {
    }

    /**
     * Summary of checkLastVersion
     * @param string $deviceType
     * @param string $boardType
     * @return array|bool
     */
    function checkLastVersion(string $deviceType, string $boardType = '2')
    {
        $dbRequest = new DbRequest;
        $lastVersUp = $dbRequest->getDeviceTypeActualVers($deviceType);
        return $lastVersUp;
    }

    /**
     * set software filename, verify file exists in package folder, returns filecontent to string
     * equivalence of fileContent in original code
     * if package file exists, return package file content
     * else return last package file ?
     * @param string $deviceType
     * @param string $fileName
     * @return string|bool
     */
    
    function getFileContent(string $deviceType, string $fileName) : string|bool
    {
        if (file_exists($_ENV['PACK_PATH'])) { # check that directory exists and is accessible
            if (file_exists($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName)) {
                if ($content = file_get_contents($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName)) {
                    return $content;
                } else {
                    echo "\r\n{$fileName} is too big. Content cannot be get.\r\n";
                    return false;
                }
            } else {
                $aValue = explode('_', $fileName);
                $boardType = $aValue[2]; //TODO to be used in the future in file_get_contents
                $lastVersUp = $this->checkLastVersion($deviceType, $boardType);
                $actualFile = $lastVersUp["name"];
                if (file_exists($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $actualFile)) {
                    $content = file_get_contents($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $actualFile);
                    if (!$content) {
                        echo "\r\n{$actualFile} is too big. Content cannot be get.\r\n";
                        return false;
                    }
                    return $content;
                }
                echo "\r\n{$actualFile} doesn't exist, please check again.\r\n";
                return false;
            }
        }
        else {
            echo "\r\nDirectory". $_ENV['PACK_PATH'] ."doesn't exist. Content cannot be get.\r\n";
            return false;
        }
    }

    function getFileSize(string $deviceType, string $fileName) : string|bool
    {
        if (file_exists($_ENV['PACK_PATH'])) { # check that directory exists and is accessible
            if (file_exists($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName)) {
                $size = filesize($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName);
                return $size;
            } else {
                $aValue = explode('_', $fileName);
                $boardType = $aValue[2]; //TODO to be used in the future in file_get_contents
                $lastVersUp = $this->checkLastVersion($deviceType, $boardType);
                $actualFile = $lastVersUp["name"];
                if (file_exists($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $actualFile)) {
                    $size = filesize($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $actualFile);
                    return $size;
                }
                echo "\r\n{$actualFile} doesn't exist, please check again.\r\n";
                return false;
            }
        }
        else {
            echo "\r\nDirectory". $_ENV['PACK_PATH'] ."doesn't exist. Content cannot be get.\r\n";
            return false;
        }
    }

    function getFileContentTest(string $deviceType, string $fileName) : string|bool
    {
        if (file_exists($_ENV['PACK_PATH'])) { # check that directory exists and is accessible
            if (file_exists($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName)) {
                if ($content = file_get_contents($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName)) {
                    return true;
                } else {
                    echo "\r\n{$fileName} is too big. Content cannot be get.\r\n";
                    return false;
                }

                /*
                $content = file_get_contents($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $fileName);
                if ($content) {
                    return $content;
                } else {
                    echo "\r\nContent cannot be get.\r\n";
                    return false;
                }
                */
            } else {
                $aValue = explode('_', $fileName);
                $boardType = $aValue[2]; //TODO to be used in the future in file_get_contents
                $lastVersUp = $this->checkLastVersion($deviceType, $boardType);
                $actualFile = $lastVersUp["name"];
                if (file_exists($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $actualFile)) {
                    $content = file_get_contents($_ENV['PACK_PATH'] . DEVICE_TYPE_ARRAY[$deviceType] . $actualFile);
                    if (!$content) {
                        echo "\r\nContent cannot be get.\r\n";
                        return false;
                    }
                    return true;
                }
                echo "\r\nFile doesn't exist, please check again.\r\n";
                return false;
            }
        }
        else {
            echo "\r\nDirectory". $_ENV['PACK_PATH'] ."doesn't exist. Content cannot be get.\r\n";
            return false;
        }
    }
    /*
    function getFileContentTest(string $deviceType, string $fileName) : bool
    {
		if(file_exists($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[$deviceType].$fileName)){
            //echo("\r\n ".$fileName . " file exists !\r\n");
            $content = file_get_contents($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[$deviceType].$fileName);
            if (!$content) {
                //throw new Exception('Content cannot be get.');
                echo "\r\nContent cannot be get.\r\n";
                return false;
            }
            return true;
		}
        else
        {
            
			$aValue = explode('_', $fileName);
            //echo "\r\naValue: ".$aValue[2];
            $boardType = $aValue[2]; //TODO to be used in the future in file_get_contents
            $lastVersUp = $this->checkLastVersion($deviceType, $boardType);
			$actualFile = $lastVersUp["name"];
            if(file_exists($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[$deviceType].$actualFile)){
                $content = file_get_contents($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[$deviceType].$actualFile);
                if (!$content) {
                    echo "\r\nContent cannot be get.\r\n";
                    return false;
                }
                return true;
            }
            echo "\r\nFile doesn't exist, please check again.\r\n";
            return false;
		}
    }
    */

    /**
     * Extract specific data from content file, based on an index and a certain length
     * Return string corresponding to data or false if nothing is found
     *
     * @param string $deviceType
     * @param integer $index
     * @param [type] $length
     * @return string|boolean
     */
    function getContentFromIndex($fileContent, int $index=0, int $length=FW_OCTETS) : string|bool
    {
        //$fileContent = $this->getFileContent($deviceType);
        //$result = substr($fileContent, $index, $length);
        return substr($fileContent, $index, $length);
    }

    function clean($string) {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
     
        return preg_replace('/[^A-Za-z0-9\-]/', '_', $string); // Removes special chars.
    }
}