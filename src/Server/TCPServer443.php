<?php
namespace App\Server;

use App\Repository\DeviceFamilyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Server\CommandDetect;
use App\Server\DataResponse;

use App\Server\DbRequest;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

//require_once dirname(__FILE__, 3).'/configServer/config.php';
//require_once dirname(__FILE__, 3).'/configServer/dbConfig.php';


//require_once(dirname(__FILE__, 2).'/Server/CommandDetect.php');
require_once(dirname(__FILE__, 2).'/Server/DataResponse.php');
require_once(dirname(__FILE__, 2).'/Server/DbRequest.php');

class TCPServer443 extends Application
{
	private $timeOut;
	/**
	 * @param array $linkConnection - array of sn connected as key and array of sockets linked to this sn as values (ex: [WIN0D_TEST_61706    ] => Array), the subarray is a key-index paired with each socket (ex: [1] => Socket Object())
	 * @param array $clients - array of socket object: [0] => Socket Object
	 * 
	 */
	private $linkConnection = [];
	private $clients;

	/**
	 * Confirm system can run script
	 */
	function preflight()
	{
		$phpversion_array = explode('.', phpversion());
		if ((int)$phpversion_array[0].$phpversion_array[1] < 80) {
			die('minimum php required is 8.0. exiting');
		}
	}

	/**
	 * Write server logs to a log file with date as filename
	 * @param string $logTxt
	 * @return string
	 */
	function writeServerLog(string $logTxt){
		if (!file_exists($_ENV['LOG_PATH']."server/")) {
			mkdir($_ENV['LOG_PATH']."server/", 0777, true);
		}
		$logFile = date("Y-m-d").".txt";
		if (file_exists($_ENV['LOG_PATH']."server/".$logFile) && filesize($_ENV['LOG_PATH']."server/".$logFile) < 200000) {
			$fd = fopen($_ENV['LOG_PATH']."server/".$logFile, "a+");
			if($fd){
				fwrite($fd, $logTxt);
				fclose($fd);
				return $logFile;
			}else{
				echo "fd error";
				return false;
			}
		}
		else {
			$fd = fopen($_ENV['LOG_PATH']."server/".$logFile, "w");
			if($fd){
				fwrite($fd, $logTxt);
				fclose($fd);
				return $logFile;
			}else{
				echo "fd error";
				return false;
			}
		}
	}

	/**
	 * Verify if command in data exists in command array
	 * @param mixed $data
	 * @return bool
	 */
	function dataToTreat($data){
		if(isset($data[20]) && !empty($data[20])){
			$cmdRec = $data[20].$data[21];
			if (in_array($cmdRec, cmdSoft)) {
				return true;
			}
			return false;
		}
		return false;
	}

	function disconnect($request, $logger, $clientsInfo, $clients, $i)
	{
		$request->setConnect(0, $clientsInfo[$i][0]);
		socket_close($clients[$i]);
		echo "\n".date("Y-m-d H:i:s | ")."client ".$clientsInfo[$i][0]." ip ".$clientsInfo[$i][1]." with key ".$i." disconnected.\n";
		$logger->info("client ".$clientsInfo[$i][0]." ip ".$clientsInfo[$i][1]." with key ".$i." disconnected.");
		unset($clients[$i]);	
		unset($clientsInfo[$i]);
		$disconnectArray[0] = $clients;
		$disconnectArray[1] = $clientsInfo;
		return $disconnectArray;
	}
	/**
	 * Create Socket and connect to server
	 * @return $resultArray array|bool - array of sockets ? [0] => Array ([0]=> Socket Object()), [1]=> Socket Object()
	 */
	function createServer()
	{
		set_time_limit(0);
		ob_implicit_flush();

		$msg = str_repeat("\r\n".str_repeat("#", 30)."\r\n", 3)."\r\n==========   SERVER STARTED {$_ENV['ADDRESS']}:{$_ENV['SERVER_SECURE_PORT']}   ==========\r\n".str_repeat("\r\n".str_repeat("#", 30)."\r\n", 3);
		
		echo($msg);
		//$request->setConnectAll(0);

		$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		// set the option to reuse the port
		socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
		// bind the socket to the address defined in config on port
		if (socket_bind($sock, $_ENV['ADDRESS'], $_ENV['SERVER_SECURE_PORT']) === false) {
			echo "socket_bind() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
			return false;
		}
		// start listen for connections
		if (socket_listen($sock, 5) === false) {
			echo "socket_listen() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
			return false;
		}
		
		
		$clients = array($sock);
		$resultArray = array($clients, $sock);
		return $resultArray;
	}

	function runServer(LoggerInterface $logger, DeviceFamilyRepository $deviceFamilyRepository)
	{
		/**
		 * @var array $resultArray
		 * [0] = $this->clients
		 * [1] = $sock
		 */
		/**
		 * @var array $clientsInfo
		 * [0] = sn
		 * [1] = ip:port
		 * [2] = timeOut
		 * [3] = ip
		 * [4] = port
		 * [5] = command history //used to show the last command send by the device
		 * [6] = index //used to get indexToGet when downloading version to avoid downloading the same data chunk if server is too slow or blocked
		 * [7] = device info
		 * [8] = download percentage
		 */

		 /**
		  * @var array $clients

		  */
		/**
		 * @var array $responseArray
		 * Response array return from CommandDetect
		 * [0] = $indexToGet;
		 * [1] = $response.$footer;
		 * [2] = $deviceInfo;
		 * [3] = $percentage;
		 */

		/** @var array $deviceInfo
		*  	[id] =>
		*	[device_family_id] =>
		*	[device_family] =>
		*	[sn] => 
		*	[version] =>
		*	[version_upload] =>
		*	[forced] =>
		*	[ip_addr] =>
		*	[log_pointeur] =>
		*	[pub] => 
		*	[code_pin] =>
		*	[selected] =>
		*	[server_date] =>
		*	[connected] =>
		*	[created_at] => 
		*	[updated_at] => 
		*	[is_active] =>
		*	[device_file] =>
		*	[log_file] => LOGFILE.txt
		*	[download] => download percentage
		*	[indextoget] =>
		*	[comment] =>
		*	[update_comment] => NOTUSED
		*	[country]
		*	[city]
		*/

		/**
		 * @clientsInfo : array of sn connected as key and array of info linked to this sn as values (ex: [WIN0D_TEST_61706    ] => Array), the subarray is a key-index paired with each info (ex: [1] => 'ip address : port')
		 */
		//$output = new ConsoleOutput();
		$this->preflight();
		$request = new DbRequest();
		$dataResponse = new DataResponse();
		$resultArray = $this->createServer();
		$clients = $resultArray[0];
		$sock = $resultArray[1];
		$clientsInfo = array(array("sn unknown","ip unknown",hrtime(true)));
		$this->timeOut = 300000000000;
		
		while (true)
		{
			
			// get a list of all the clients that have data to be read from
			// if there are no clients with data, go to next iteration
			reset($clients);
			reset($clientsInfo);
			//TODO Close socket automatically after too much time without receiving data
			foreach ($clientsInfo as $i=>$client)
			{
				if ($i > 0) {
					if(isset($clientsInfo[$i][2])){
						// If process takes too much time, close socket
							if($clientsInfo[$i][2] < hrtime(true)){
								/*
								$request->setConnect(0, $clientsInfo[$i][0]);
								socket_close($clients[$i]);
								echo "\n".date("Y-m-d H:i:s | ")."client ".$clientsInfo[$i][0]." ip ".$clientsInfo[$i][1]." with key ".$i." disconnected.\n";
								$logger->info("client ".$clientsInfo[$i][0]." ip ".$clientsInfo[$i][1]." with key ".$i." disconnected.");
								unset($clients[$i]);	
								unset($clientsInfo[$i]);
								*/
								$disconnectArray = $this->disconnect($request, $logger, $clientsInfo, $clients, $i);
								$clients = $disconnectArray[0];
								$clientsInfo = $disconnectArray[1];
							}
					}
				}
			}
			
			reset($clients);

			// create a copy, so $clients doesn't get modified by socket_select()
			$read = $clients;
			$write = null;
			$except = null;

			if (socket_select($read, $write, $except, 0) < 1) {
				continue;
			}

			// check if there is a client trying to connect

			if (in_array($sock, $read))
			{	
				$newsock = socket_accept($sock);
				$clients[] = $newsock;
				socket_getpeername($newsock, $ip, $port);
				echo "\r\n".date("Y-m-d H:i:s | ")."New client connected: {$ip} : {$port}\r\n";
				$logger->info("New client connected: {$ip} : {$port}");
				// remove the listening socket from the clients-with-data array
				$key = array_search($sock, $read); 
				unset($read[$key]);
				unset($key);
				$key = array_search($newsock, $clients);

				$clientsInfo[$key][0] = "sn unknown";
				$clientsInfo[$key][1] = "{$ip} : {$port}";
				$clientsInfo[$key][2] = hrtime(true)+$this->timeOut;
				$clientsInfo[$key][3] = "{$ip}";
				$clientsInfo[$key][4] = "{$port}";
				$clientsInfo[$key][5] = ""; //command history
				$clientsInfo[$key][6] = ""; //index
				$clientsInfo[$key][7] = array(); //device info
				$clientsInfo[$key][8] = ""; //percentage
			}

			// loop through all the clients that have data to read from
			
			//foreach ($read as $i=>$read_sock)
			foreach ($read as $read_sock)
			{
				// read until newline or 1024 bytes
				//$data = @socket_read($read_sock, 4096, PHP_BINARY_READ) or socket_strerror(socket_last_error());//die("Could not read input\n");
				//$data = @socket_read($read_sock, 4096, PHP_BINARY_READ);

				$data = @socket_read($read_sock, 4096, PHP_BINARY_READ);
					//=> If data exists
					if (!empty($data))
					{
						
						reset($clientsInfo);
						//$logger->info("********************* Connected list *****************************");
						echo "\r\n********************* Connected list *****************************\r\n";
						//$this->writeServerLog("\r\n********************* Connected list *****************************\r\n");
						/*
						if ($i > 0) {
							echo "\r\n".$i." | SN : ".$clientsInfo[$i][0]." | IP : ".$clientsInfo[$i][1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")." | Time : ".end($clientsInfo[$i][7])." | Cmd : ".end($clientsInfo[$i][5])."\r\n";
						}
						*/
						//=> Initiate client info (id, sn, ip, time) and update it at each iteration
						// if sn or ip from db not in connected list
						for($i=1; $i<count($clients); $i++){
						//foreach ($clients as $i=>$client){
							next($clientsInfo);
							if (isset(current($clientsInfo)[1]) && isset(current($clientsInfo)[0])) {
								echo "\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nDate : ".date("H:i:s")." | Cmd : ".current($clientsInfo)[5]." | Percentage : ".current($clientsInfo)[8]."\r\n";
								//$logger->info($i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nDate : ".date("H:i:s")." | Cmd : ".current($clientsInfo)[5]." | Percentage : ".current($clientsInfo)[8]);
								//$this->writeServerLog("\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")."\r\n");
								
							}
						}
						
						
						// if commands are returned from device data
						if($this->dataToTreat($data)) // => msg from device
						{ 
							$deviceKey = array_search($read_sock, $clients);			
							$clientsInfo[$deviceKey][2] = hrtime(true)+$this->timeOut;
							if(substr($data, 0, 1) == 'W' && $data[3] == 0 && array_key_exists(hexdec($data[3].$data[4]), DEVICE_TYPE_ARRAY)){ // Verify that data comes from a device (all devices start with W)
								//echo ("\r\nData received: " . $data . "\r\n");
								$time_start_socket = microtime(true);
								$task = new CommandDetect();
								//$clientServeur = new Client();
								$sn = substr($data, 0, 20);
								$deviceType = hexdec($data[3].$data[4]);
								$clientsInfo[$deviceKey][0] = $sn; // Show serial number in terminal
								$deviceCommand = $data[20].$data[21];

								//$deviceInfo = $clientsInfo[$deviceKey][7];

								$responseArray = $task->start($data, $clientsInfo[$deviceKey][3], $clientsInfo[$deviceKey][7], $deviceFamilyRepository);

								if ($responseArray != False) {
									// récupérer deviceInfo
									if (array_key_exists(2, $responseArray)) {
										$clientsInfo[$deviceKey][7] = $responseArray[2];
										//print_r($clientsInfo[$deviceKey][7]);
									}
									
									// check if index is not duplicated
									if (array_key_exists(0, $responseArray)) {
										$indexToGet = $responseArray[0];
										//register dc index in array
										if ($indexToGet != $clientsInfo[$deviceKey][6]) {
											socket_write($clients[$deviceKey], $responseArray[1]);
											$clientsInfo[$deviceKey][6] = $indexToGet;
											//echo "Index: ".$indexToGet;
											//$clientServeur->main($data);
										}
									}
									// Check & show percentage number
									if (array_key_exists(3, $responseArray)) {
										$percentage = $responseArray[3];
										//echo "\r\n".$percentage."\r\n";
										//register dc index in array
										if ($percentage != $clientsInfo[$deviceKey][8]) {
											$time_start = microtime(true);
											//echo "\r\n".$percentage." and ".$clientsInfo[$deviceKey][8]."\r\n";
											//$logger->info($percentage." and ".$clientsInfo[$deviceKey][8]);
											$clientsInfo[$deviceKey][8] = $percentage;
											$request->setDownload($sn, $percentage);
											//$dataResponse->writeCommandLog($sn, $deviceType, "Downloading: ".$percentage." %");
											if ($percentage == 25 or $percentage == 50 or $percentage == 75 or $percentage == 100) {
												$logger->info($sn." Downloading: ".$percentage." %");
											}
										
											$time_end = microtime(true);
											$execution_time = ($time_end - $time_start)*1000;
											//echo "\r\n".$execution_time."\r\n";
											//socket_write($clients[$deviceKey], $responseArray[1]);
											
										}
									}
									else {
										//print_r($responseArray[1]);
										socket_write($clients[$deviceKey], $responseArray[1]);
										//$clientServeur->main($data);
									}
									
									//socket_write($clients[$key], $responseArray[1]);
								}
								else {
									$this->writeServerLog("\r\nResponse is empty! Please check that your device can connect to the server!\r\n");
								}
								
								$clientsInfo[$deviceKey][5] = $deviceCommand;
								
								//if (($deviceCommand === 'F9') || ($deviceCommand === 'FA') || ($deviceCommand === 'FE') || ($deviceCommand === 'DE')){
								
								//When server reconnects or device reconnects, avoid duplicate of linkConnection for one device and disconnect the preceding index from the list
								if(isset($this->linkConnection[$clientsInfo[$deviceKey][0]]) && !empty($this->linkConnection[$clientsInfo[$deviceKey][0]][0])){
									
									foreach ($clientsInfo as $i=>$client) {

										if( isset($clientsInfo[$i][0]) && isset($clientsInfo[$deviceKey][0]) && $clientsInfo[$i][0] == $clientsInfo[$deviceKey][0])
										{
											if($i!=$deviceKey)
											{
												$disconnectArray = $this->disconnect($request, $logger, $clientsInfo, $clients, $i);
												$clients = $disconnectArray[0];
												$clientsInfo = $disconnectArray[1];
												/*
												echo("socket is closed :".$clientsInfo[$i][0]."with key: ".$i);
												$logger->info("Socket is closed :".$clientsInfo[$i][0]."with key: ".$i);
												//$this->writeServerLog("socket is closed :".$clientsInfo[$i][0]."with key: ".$i);
												$key2del = array_search($this->linkConnection[$clientsInfo[$deviceKey][0]][0], $clients);

												//$request->setConnect(0, $clientsInfo[$i][0]);
												socket_close($clients[$i]);
												unset($clients[$i]);
												unset($clientsInfo[$i]);
												//array_splice($clients, $i, 1);
												//array_splice($clientsInfo, $i, 1);
												*/
											}
										}
									}
									
									$this->linkConnection[$sn][0] = $read_sock;
								}else{
									$this->linkConnection[$sn][0] = $read_sock;
								}
								//}

								$time_end_socket = microtime(true);
								$execution_time_socket = ($time_end_socket - $time_start_socket);
								echo "\r\nTotal Execution Time Socket: ".($execution_time_socket*1000)." Milliseconds\r\n";
								unset($data);
								
							}
							else
							{
								$key = array_search($read_sock, $clients);
								if($key){
									socket_close($clients[$key]);
									echo "\r\nSocket closed ! Data doesn't come from a device !\r\n";
									unset($clients[$key]);
									unset($clientsInfo[$key]);
								}				
							}
							
						}
						//=> if no commands are returned from data device
						else //=> msg between Device & Computer
						{	
							//if ($read_sock!=false && array_key_exists($read_sock, $clients)) {
							if ($read_sock!=false && in_array($read_sock, $clients)) {
								$key = array_search($read_sock, $clients);
								echo "\r\nComputer with IP {$clientsInfo[$key][1]} send {$data} to device.\r\n";
								$logger->info("Computer with IP {$clientsInfo[$key][1]} send {$data} to device.");
	
								// check data starts with serial number
								if (($data[0] == 'W')) {
									if(($data[0] == 'W') && (strlen($data) == 20)) 
									{
										$sn = substr($data, 0, 20);
										if(isset($this->linkConnection)){
											$keyLink = array_key_exists($data, $this->linkConnection);
											if($keyLink){
												echo 'Add link connection >>>>>>>>>>>>>>>>>>>>> '.$keyLink." !!!!!!!\n";
												if(isset($this->linkConnection[$data][1]) && !empty($this->linkConnection[$data][1])){
													//Check if sn exists in clients
													$key1 = array_search($this->linkConnection[$data][1], $clients);
													
													if($key1){
														echo "\nSocket is closed with key1:".$clientsInfo[$key1][1]."\n";
														$logger->info("Socket is closed :".$clientsInfo[$key1][1]);
														$request->setConnect(0, $data);
														socket_close($clients[$key1]);
														unset($clients[$key1]);
														unset($clientsInfo[$key1]);	
		
													}
												}
												$this->linkConnection[$data][1] = $read_sock;
												socket_write($read_sock, $keyLink);
												//print_r($read_sock);
												//echo "SEND MSG TO >>>>>>>>>>>>>>>>>>>>> $key\n";
												echo "SEND ".$keyLink." TO ".$data." >>>>>>>>>>>>>>>>>>>>>\n";
											}
											else {
												//TODO
												// close computer connexion
												socket_close($clients[$key]);
												echo "\nWarning: LinkConnection not found. Socket is closed with key:".$clientsInfo[$key][1]."\n";
												unset($clients[$key]);
												unset($clientsInfo[$key]);
											}
											
										}
										/*
										socket_write($read_sock, $keyLink);
										print_r($read_sock);
										//echo "SEND MSG TO >>>>>>>>>>>>>>>>>>>>> $key\n";
										echo "SEND ".$keyLink." TO ".$data." >>>>>>>>>>>>>>>>>>>>>\n";
										*/
									}
									else
									{
										//Data is incorrect
										if (strlen($data)<=20) {
											echo "\r\nData is incorrect, data length is under 20.\r\n";
											socket_close($read_sock);
											unset($clients[$key]);
											unset($clientsInfo[$key]);
										}
										elseif (strlen($data) > 20 && ord($data[21]) != 0) {
											
											$sn = substr($data, 0, 20);
											$canal = ord($data[21]);
											echo "\r\nCanal : {$canal}\r\n";

											//TODO in_array($sn, $this->linkConnection)
											if($canal === 255) {
												echo "\r\nReplace sock 1\r\n";
												echo "\r\nsize of linkConnection : ".sizeof($this->linkConnection)."\r\n";
												//if (array_key_exists($this->linkConnection[$sn][1], $clients)) {

												if (in_array($this->linkConnection[$sn][1], $clients)) {
													$keyCanal = array_search($this->linkConnection[$sn][1], $clients);
													if($keyCanal){
														socket_close($clients[$keyCanal]);
														//$request->setConnect(0, $sn);
														echo "\r\nSocket closed with Sn ".$clientsInfo[$keyCanal][1]." and keyCanal: ".$keyCanal." !\r\n";
														unset($clients[$keyCanal]);
														unset($clientsInfo[$keyCanal]);	
													}
												}
												
												$this->linkConnection[$sn][1] = $read_sock;
												echo 'SEND MSG TO RSHOCK >>>>>>>>>>>>>>>>>>>>> '.$data."\n";
												
												if((socket_write($this->linkConnection[$sn][0], $data)) === false)
												// If connexion is lost with device when on connect interface, close socket
												{
													$key = array_search($this->linkConnection[$sn][0], $clients);
													if($key){
														socket_close($clients[$key]);
														echo "\r\nSocket closed with Sn ".$clientsInfo[$key][1]." and keyFalse: ".$key." !\r\n";
														unset($clients[$key]);
														unset($clientsInfo[$key]);
													}
												}
												
											} 
											else 
											{
												//$key = array_search($this->linkConnection[$sn][0], $clients);
												echo 'SEND MSG TO OTHER >>>>>>>>>>>>>>>>>>>>> '.$data."\n";
												if (isset($this->linkConnection[$sn][1])) {
													//print_r($this->linkConnection[$sn]);
													socket_write($this->linkConnection[$sn][1], $data);
													echo 'SEND MSG TO '.$sn.' >>>>>>>>>>>>>>>>>>>>> '.$data."\n";
													/*
													$key = array_search($this->linkConnection[$sn][0], $clients);
													if($key){
														socket_close($this->linkConnection[$sn][0]);
														unset($clients[$key]);
														unset($clientsInfo[$key]);
														echo 'SOCKET 1 CLOSED'."\n";
													}
													*/
												}
												else {
													echo 'Device is not connected';
													//unset($data);
												}
											}
										}
		
									}
									unset($data);
								}
								else // data sent by unknown device or data is incorrect
								{
									socket_close($read_sock);
									echo "\nWarning: Device is unknown or data is incorrect. Socket is closed with key:".$clientsInfo[$key][1]."\n";
									unset($clientsInfo[$key]);
									unset($clients[$key]);

								}
							}
						}
					}

			}
		}
	}
}