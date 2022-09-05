<?php
namespace App\Server;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Server\CommandDetect;
use App\Server\DataResponse;
use App\Server\DbRequest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

require_once dirname(__FILE__, 3).'/configServer/config.php';
require_once dirname(__FILE__, 3).'/configServer/dbConfig.php';

require_once(dirname(__FILE__, 2).'/Server/CommandDetect.php');
require_once(dirname(__FILE__, 2).'/Server/DataResponse.php');
require_once(dirname(__FILE__, 2).'/Server/DbRequest.php');

class TCPServer extends AbstractController
{
	private $timeOut;
	private $linkConnection = [];
	private $time_array = 0;
	private $clients;

	function __construct()
	{
		
	}

	// Create Socket and connect to server
	function createServer()
	{
		$output = new ConsoleOutput();
		$request = new DbRequest;

		set_time_limit(0);
		ob_implicit_flush();

		$msg = str_repeat("\r\n".str_repeat("#", 30)."\r\n", 3)."\r\n==========   SERVER STARTED   ==========\r\n".str_repeat("\r\n".str_repeat("#", 30)."\r\n", 3);
		
		//$output->writeln($msg);
		echo($msg);
		$request->setConnectAll(0);

		$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		// set the option to reuse the port
		socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
		// bind the socket to the address defined in config on port
		if (socket_bind($sock, ADDRESS, PORT) === false) {
			echo "socket_bind() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
			return false;
		}
		// start listen for connections
		if (socket_listen($sock, 5) === false) {
			echo "socket_listen() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
			return false;
		}
		
		$this->clients = array($sock);
		$resultArray = array($this->clients, $sock);
		return $resultArray;
	}

	// Verify if command in data exists in command array
	function dataToTreat($data){
		
		if(isset($data[20]) && !empty($data[20])){
			$cmdRec = $data[20].$data[21];
			if (in_array($cmdRec, cmdSoft)) {
				return true;
			}
			return false;
		}
		
	}

	function disconnectServer($clients, $clientsInfo, $output)
	{
		for($i=1; $i<count($clients); $i++)
		{
			next($clients);
			next($clientsInfo);
			if(isset(current($clientsInfo)[2])){
				// If process takes too much time, close socket ?
				if(current($clientsInfo)[2] < hrtime(true)){
					//echo "\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($this->clients)." disconnected.\n";
					$output->writeln("\r\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($clients)." disconnected.\n");
					$this->writeServerLog("\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($clients)." disconnected.\n");
					$key = key($clients);
					
					socket_close($clients[$key]);
					$output->writeln("\r\nSocket closed !\r\n");
					unset($clients[$key]);	
					unset($clientsInfo[$key]);				
				}
			}	
		}
		//reset($clients);
		return false;
	}

	/**
	 * Write server logs to a log file with date as filename
	 *
	 */
	function writeServerLog(string $logTxt){
		if (!file_exists(LOG_PATH."server/")) {
			mkdir(LOG_PATH."server/", 0777, true);

		}
		$logFile = date("Y-m-d").".txt";
		if (file_exists(LOG_PATH."server/".$logFile) && filesize(LOG_PATH."server/".$logFile) < 200000) {
			$fd = fopen(LOG_PATH."server/".$logFile, "a+");
			if($fd){
				fwrite($fd, $logTxt);
				fclose($fd);
				return $logFile;
			}else{
				echo "fd error";
			}

		}
		else {
			$fd = fopen(LOG_PATH."server/".$logFile, "w");
			if($fd){
				fwrite($fd, $logTxt);
				fclose($fd);
				return $logFile;
			}else{
				echo "fd error";
			}
		}
	}

	function runServer()
	{
		$output = new ConsoleOutput();
		$request = new DbRequest();
		
		$resultArray = $this->createServer();
		$clients = $resultArray[0];
		$sock = $resultArray[1];
		$clientsInfo = array(array("sn unknown","ip unknown",hrtime(true)));
		$this->timeOut = 300000000000;
		
		while (true)
		{
			
			$time_start_while = microtime(true);

			// get a list of all the clients that have data to be read from
			// if there are no clients with data, go to next iteration
			reset($clients);
			reset($clientsInfo);
			//echo current($clientsInfo[0]);
			//TODO Close socket automatically after too much time without receiving data
			//$request->setConnectAll(0);
			// 
			for($i=1; $i<count($clients); $i++)
			{
				//echo current($clientsInfo[0]);
				next($clients);
				next($clientsInfo);
				//echo current($clientsInfo[0]);
				if(isset(current($clientsInfo)[2])){
					// If process takes too much time, close socket ?
					if(current($clientsInfo)[2] < hrtime(true)){
						//echo "\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($this->clients)." disconnected.\n";
						//TODO $output->writeln("\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($clients)." disconnected.\n");
						//$this->writeServerLog("\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($clients)." disconnected.\n");
						$key = key($clients);
						//$this->closeSocket($key, $clients, $clientsInfo);
						
						$request->setConnect(0, current($clientsInfo)[0]);
						//$this->writeServerLog("\nsetConnect 0 ".current($clientsInfo)[0]."\n");
						socket_close($clients[$key]);
						//TODO $output->writeln("\r\nSocket closed !\r\n");
						$this->writeServerLog("\n".date("Y-m-d H:i:s | ")."client ".current($clientsInfo)[0]." ip ".current($clientsInfo)[1]." with key ".key($clients)." disconnected.\n");
						unset($clients[$key]);	
						unset($clientsInfo[$key]);
										
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
				$clients[] = $newsock = socket_accept($sock);
				socket_getpeername($newsock, $ip, $port);
				echo "\r\n".date("Y-m-d H:i:s | ")."New client connected: {$ip} : {$port}\r\n";
				//TODO 
				//$output->writeln("\r\n".date("Y-m-d H:i:s | ")."New client connected: {$ip} : {$port}\r\n");
				//$this->writeServerLog("\r\n".date("Y-m-d H:i:s | ")."New client connected: {$ip} : {$port}\r\n");
				$request = new DbRequest();
				//$request->setConnect(1, $sn="", $ip);
				//$this->writeServerLog("\nsetConnect 1".$ip."\n");
				//$clients[] = $newsock; // put accepted socket in a client array
				// remove the listening socket from the clients-with-data array
				$key = array_search($sock, $read); 
				//$output->writeln("\r\nRead size : ".sizeof($read));
				unset($read[$key]);
				//array_splice($read, $key, 1);
				$key = array_search($newsock, $clients);
				//$output->writeln("\r\nRead size : ".sizeof($read));
				//$output->writeln("\r\nClients size : ".sizeof($clients));
				//$this->writeServerLog("\r\nClients size : ".sizeof($clients)."\r\n");
				$clientsInfo[$key][0] = "sn unknown";
				$clientsInfo[$key][1] = "{$ip} : {$port}";
				$clientsInfo[$key][2] = hrtime(true)+$this->timeOut;
				$clientsInfo[$key][3] = "{$ip}";
				$clientsInfo[$key][4] = "{$port}";
				$clientsInfo[$key][5] = array(); //command history
				$clientsInfo[$key][6] = array(); //time command history
				$clientsInfo[$key][7] = array(); //time while
				$clientsInfo[$key][8] = array(); //time foreach
				$clientsInfo[$key][9] = array(); //dc index
				//echo "\n"."There are ".count($clients)." client(s) connected to the server\n";
				//$sockData = socket_read($newsock, 4096, PHP_BINARY_READ) or die("Could not read input\n");
				//echo "\r\nData : ".$sockData;
			}

			//$output->writeln("\r\n********************* Connected list *****************************\r\n");
			//print_r($clientsInfo);
			// loop through all the clients that have data to read from
			
			foreach ($read as $read_sock)
			{
				$time_start_device = microtime(true);	
				// read until newline or 1024 bytes
				$data = @socket_read($read_sock, 4096, PHP_BINARY_READ);// or die("Could not read input\n");

				$request = new DbRequest();

					//=> If data exists
					$time_start_device = microtime(true);
					if (!empty($data))
					{
						
						reset($clientsInfo);
						//TODO 
						$time_start_step1 = microtime(true);

						//$output->writeln("\r\n********************* Connected list *****************************\r\n");
						echo "\r\n********************* Connected list *****************************\r\n";
						//TODO 
						$this->writeServerLog("\r\n********************* Connected list *****************************\r\n");
						//=> Initiate client info (id, sn, ip, time) and update it at each iteration
						//$request->setConnectAll(0);
						// if sn or ip from db not in connected list
						for($i=1; $i<count($clients); $i++){
							//foreach ($clients as $sock){
							next($clientsInfo);
							//echo "\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")."\r\n";
							//$output->writeln("\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")."\r\n");
							if (isset(current($clientsInfo)[1]) && isset(current($clientsInfo)[0])) {
								//TODO 
								//$output->writeln("\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")." | Time : ".end(current($clientsInfo)[7])." | Cmd : ".end(current($clientsInfo)[5])."\r\n");
								echo "\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")." | Time : ".end(current($clientsInfo)[7])." | Cmd : ".end(current($clientsInfo)[5])."\r\n";
								//TODO 
								//$this->writeServerLog("\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".current($clientsInfo)[1]." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")."\r\n");
								
							}
							/*
							else {
								//TODO 
								$output->writeln("\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".$ip." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")."\r\n");
								//TODO $this->writeServerLog("\r\n".$i." | SN : ".current($clientsInfo)[0]." | IP : ".$ip." | \r\nTime : ".date("H:i:s")." | Date : ".date("Y-m-d")."\r\n");
								if (array_key_exists(current($clientsInfo)[0], $this->linkConnection)) {
									echo 'Client already exists !';
								}
							}
							*/
						}
						
						//$this->writeServerLog("\r\nThere are ".count($clients)."clients connected.\r\n");
						//=> if no commands are returned from data device
						$time_end_step1 = microtime(true);
						$execution_time_step1 = ($time_end_step1 - $time_start_step1)*1000;
						$this->writeServerLog("\r\nTotal Execution Step1: ".$execution_time_step1." Milliseconds\r\n");
						// TODO uncomment
						if(!$this->dataToTreat($data)) //=> msg to Device
						{	
							//$clientsInfo[$key][0] = "Computer ".$i;
							//echo "\r\n{$clientsInfo[$key][1]} send {$data} to {} with SN ".$clientsInfo[$key][0]."\r\n";
							//echo "\r\n{$ip}:{$port} send {$data} to IP ".$clientsInfo[$key][1]."\r\n";
							//TODO Device send data to computer
							echo "\r\n{$clientsInfo[$key][0]} send {$data} to computer.\r\n";

							//echo "\r\n{$ip} send {$data} from {$read_sock} to ?? with SN ".$clientsInfo[$key][0]."\r\n";
							//echo "\r\nData length : ".strlen($data)."\r\n"; // check data length
							if (strlen($data)<20) {
								//TODO $output->writeln("\r\nData : ".bin2hex($data)."\r\n");
							}

							// check data is a device & contains serial number

							// TODO delete $data1 from if, replace with $data
							//$this->linkConnection[$data][1] = $read_sock;
							if(($data[0] == 'W') && (strlen($data) == 20)) 
							{
								//$output->writeln(strlen($data1));
								$key = 0;
								$sn = substr($data, 0, 20);
								//TODO find if $this->linkConnection exists & what to do if $this->linkConnection is not defined?
								//TODO change key 0 --> 1 if needed
								//$this->linkConnection[$data][0] = $read_sock;
								if(isset($this->linkConnection)){
									//TODO old version
									//echo $this->linkConnection;
									$key = array_key_exists($data, $this->linkConnection);
									echo 'Add link connection >>>>>>>>>>>>>>>>>>>>> '.$key." !!!!!!!\n";
									if($key){
										var_dump($this->linkConnection[$data][1]);
										if(isset($this->linkConnection[$data][1]) && !empty($this->linkConnection[$data][1])){
											
											$key1 = array_search($this->linkConnection[$data][1], $clients);
											
											if($key1){
												echo "Socket close !!!!!!!\n";
												//$this->writeServerLog("\r\nSocket close :".$data."\r\n");
												$request->setConnect(0, $data);
												//$this->writeServerLog("\nsetConnect 0 ".$data."\n");
												socket_close($clients[$key1]);
												//TODO $this->writeServerLog("\n".date("Y-m-d H:i:s | ")."client ".$clientsInfo[$key1][0]." ip ".$clientsInfo[$key1][3]." with key ".key($clients)." disconnected.\n");
												unset($clients[$key1]);
												unset($clientsInfo[$key1]);	
											}
										}
										//var_dump($read_sock);
										//var_dump($clientsInfo[$key][1]);
										$this->linkConnection[$data][1] = $read_sock;
										
									}
									
								}
								//$dataResponse->writeLog("SEND MSG TO $sn >>>>>>>>>>>>>>>>>>>>> $key\n");
								//$task->writeLog($key);

								echo "SEND MSG TO >>>>>>>>>>>>>>>>>>>>> $key\n";
								//$key=0;

								socket_write($read_sock, $key);
								//socket_write($read_sock, $data);
							}
							else
							{
								//if((strlen($data) > 20))
								$sn = substr($data, 0, 20);
								//$deviceType = hexdec($data[3].$data[4]);
								//$dataToSend = substr($data, 20);
								//echo "\nsock == {$sock} - readSock = {$read_sock}\n";
								//TODO initial canal value:
								$canal = ord($data[21]); // TODO what is canal?
								$output->writeln("\r\nCanal : {$canal}\r\n");
								//$this->linkConnection[$sn][0] = $read_sock;
								if($canal === 255){
									$output->writeln("Replace sock 1\n");
									$output->writeln("size of linkConnection : ".sizeof($this->linkConnection));
									//$key = array_search($this->linkConnection[$sn][0], $clients);
									$key = array_search($this->linkConnection[$sn][1], $clients);
									if($key){
										socket_close($clients[$key]);
										var_dump($clients[$key]);
										//var_dump(current($clientsInfo)[0]);
										//$request->setConnect(0, current($clientsInfo)[0]);
										$request->setConnect(0, $sn);
										$this->writeServerLog("\n".date("Y-m-d H:i:s | ")."client ".$clientsInfo[$key][0]." ip ".$clientsInfo[$key][3]." with key ".key($clients)." disconnected.\n");
										$output->writeln("\r\nSocket closed !\r\n");
										//array_splice($clients, $key, 1);
										//array_splice($clientsInfo, $key, 1);
										unset($clients[$key]);
										unset($clientsInfo[$key]);	
									}

									$this->linkConnection[$sn][1] = $read_sock;
									//var_dump($this->linkConnection);
									//$dataResponse->writeLog('SEND MSG TO RSHOCK >>>>>>>>>>>>>>>>>>>>> '.$data."\n");
									//$task->writeLog($data);
									//echo 'SEND MSG TO RSHOCK >>>>>>>>>>>>>>>>>>>>> '.$data."\n";
									$output->writeln('SEND MSG TO RSHOCK >>>>>>>>>>>>>>>>>>>>> '.$data."\n");

									
									if(FALSE === socket_write($this->linkConnection[$sn][0], $data))
									{
										$key = array_search($this->linkConnection[$sn][0], $clients);
										if($key){
											socket_close($clients[$key]);
											//$request->setConnect(0, current($clientsInfo)[0]);
											$request->setConnect(0, $sn);
											//array_splice($clients, $key, 1);
											var_dump($clientsInfo[$key]);
											unset($clients[$key]);
											$this->writeServerLog("\n".date("Y-m-d H:i:s | ")."client ".$clientsInfo[$key][0]." ip ".$clientsInfo[$key][3]." with key ".key($clients)." disconnected.\n");
											$output->writeln("\r\nSocket closed !\r\n");
										}
									}
									
								} 
								else 
								{
									//$dataResponse->writeLog('SEND MSG TO OTHER >>>>>>>>>>>>>>>>>>>>> '.$data."\n");
									//$dataResponse->writeLog($data);
									//echo 'SEND MSG TO OTHER >>>>>>>>>>>>>>>>>>>>> '.$data."\n";
									$output->writeln('SEND MSG TO OTHER >>>>>>>>>>>>>>>>>>>>> '.$data."\n");
									//var_dump($this->linkConnection);
									//socket_write($this->linkConnection[$sn][0], $data);
									if (isset($this->linkConnection[$sn][1])) {
										socket_write($this->linkConnection[$sn][1], $data);
									}
									
								}
								//var_dump($this->linkConnection);
							}
							unset($data);
						}
						// if commands are returned from device data
						else
						{ // => msg from device

							$key = array_search($read_sock, $clients);			
							$clientsInfo[$key][2] = hrtime(true)+$this->timeOut;
							// TODO timer
							//$time_start5 = microtime(true);
							//echo "\r\n".date("Y-m-d H:i:s | ")."Msg received with IP: {$ip} | SN: ".$clientsInfo[$key][0]." | \r\n ".$key." | Command : {$data[20]}{$data[21]} | RX : ".$data."\r\n"; //{$data}
							//TODO $output->writeln("\r\nSN: ".$clientsInfo[$key][0]." | Msg received with IP: {$clientsInfo[$key][3]}:{$clientsInfo[$key][4]} | \r\n".date("Y-m-d H:i:s")." | "."Command : {$data[20]}{$data[21]} |\r\nRX : ".$data."\r\n");
							//$this->writeServerLog("\r\nSN: ".$clientsInfo[$key][0]." | Msg received with IP: {$clientsInfo[$key][3]}:{$port} | \r\n".date("Y-m-d H:i:s")." | "."Command : {$data[20]}{$data[21]} |\r\nRX : ".$data."\r\n");
							//$this->writeServerLog("\r\nThere are ".count($clients)."clients connected.\r\n");

							//$time_end = microtime(true);
							//$execution_time = ($time_end - $time_start);
							//echo "\r\nTotal Execution Time after command: ".($execution_time*1000)." Milliseconds\r\n";
							if(substr($data, 0, 1) == 'W'){ // Verify that data comes from a device (all devices start with W)
								$time_start_socket = microtime(true);
								$task = new CommandDetect();
								$sn = substr($data, 0, 20);
								$deviceType = hexdec($data[3].$data[4]);
								
								$clientsInfo[$key][0] = $sn; // Show serial number in terminal

								$dataResponse = new DataResponse();
								$deviceCommand = $data[20].$data[21];
								$time_start_command = microtime(true);

								//$response = $task->start($data, $clientsInfo[$key][3]);
								$responseArray = $task->start($data, $clientsInfo[$key][3]);
								$response = $responseArray[1];
								

								//register command in array
								$clientsInfo[$key][5][] = $deviceCommand;


								$time_end_command = microtime(true);
								$execution_time_command = ($time_end_command - $time_start_command)*1000;
								//everytime one device send command, stop time
								/*
								$time_end_period = microtime(true);
								$execution_period_command = ($time_end_period - $time_start_device)*1000;
								*/
								//$last_index = sizeof($clientsInfo[$key][5])-2; //gives index of last element
								$this->writeServerLog("\r\n".$deviceCommand.": Total Execution Time Command: ".$execution_time_command." Milliseconds\r\n");
								$clientsInfo[$key][6][] = $execution_time_command;
								//$clientsInfo[$key][7][] = $execution_period_command;
								//TODO
								/*
								if (isset($clientsInfo[$key][5][$last_index]) && $clientsInfo[$key][5][$last_index] == $deviceCommand) {
									echo "\r\n".$deviceCommand.": Total Execution Time Command: ".$execution_time_command." Milliseconds\r\n";
									
									echo "\r\n".$last_index."\r\n";
									echo "\r\n".$clientsInfo[$key][5][$last_index]."\r\n";
									$clientsInfo[$key][6][] = $execution_time_command + $clientsInfo[$key][6][$last_index];
								}
								else {
									echo "\r\n".$deviceCommand.": Total Execution Time Command: ".$execution_time_command." Milliseconds\r\n";
									$clientsInfo[$key][6][] = $execution_time_command;
								}
								*/

								//$this->writeServerLog("\r\n".current($clientsInfo)[0]." : ".$deviceCommand.": Execution Time Command: ".$execution_time_command." Milliseconds\r\n");
								
								// STEP 2 AFTER COMMAND DETECT
								if ($execution_time_command > 100) {
									$dataResponse->writeCommandLog($sn, $deviceType, "\r\nTime Alert: Command takes more than 100 ms:".$execution_time_command."\r\n");
								}
								/*
								if ($deviceCommand == "DC") {
									//echo "\r\nTotal Execution Time Command: ".$execution_time_command." Milliseconds\r\n";
									$this->time_array += $execution_time_command;
								}
								//print_r($time_array);
								if ($this->time_array != 0) {
									$dataResponse->writeCommandLog($sn, $deviceType, "\r\n".$this->time_array."\r\n");
								}
								*/
								/*
								if (!isset($total_execution_time)) {
									$total_execution_time = 0;
								}
								while ($deviceCommand == "DC" || $deviceCommand == "FD") {
									$total_execution_time += $execution_time_command;
									$dataResponse->writeCommandLog($sn, $deviceType, "\r\nTotal Execution Time : ".$total_execution_time." Milliseconds\r\n");
								}
								*/

								$affResponse = bin2hex($response);
								$dataResponse->writeCommandLog($sn, $deviceType, "\r\n".date("Y-m-d H:i:s | ")."Msg send : ".$affResponse." \n| SN : ".$clientsInfo[$key][0]."\n| Command : ".$deviceCommand." from server\r\n");
								//$output->writeln("\r\nSN : ".$clientsInfo[$key][0]."| Msg send : ".strlen($affResponse)."\r\n".date("Y-m-d H:i:s | ")."Command : ".$deviceCommand." from server");
								echo "\r\nSN : ".$clientsInfo[$key][0]."| Msg send : ".strlen($affResponse)."\r\n".date("Y-m-d H:i:s | ")."Command : ".$deviceCommand." from server";
								//echo "\r\nTotal Execution Time: ".($execution_time*1000)." Milliseconds\r\n";
								//$output->writeln("\r\nTotal Execution Time: ".($execution_time*1000)." Milliseconds\r\n");

								if (($deviceCommand === 'F9') || ($deviceCommand === 'FA') || ($deviceCommand === 'FE') || ($deviceCommand === 'DE')){
									
									if(isset($this->linkConnection[$clientsInfo[$key][0]]) && !empty($this->linkConnection[$clientsInfo[$key][0]][0])){

										for ($i=1; $i < count($clientsInfo); $i++) {
											if( isset($clientsInfo[$i][0]) && $clientsInfo[$i][0] == $clientsInfo[$key][0])
											{
												
												if($this->linkConnection[$clientsInfo[$key][0]][0]!=$clients[$i] && $i!=$key)
												{
	
<<<<<<< HEAD
													$output->writeln("socket is closed :");
=======
													//$output->writeln($clientsInfo[$key][0]);
													//$output->writeln(" key i:".$i);
													//$output->writeln(" key :".$key);
													//$output->writeln("socket is closed :");
													echo "socket is closed :";
>>>>>>> 2727cab1cfcf9155b2fccf110656fc3f9f74a583
													$key2del = array_search($this->linkConnection[$clientsInfo[$key][0]][0], $clients);

													$request->setConnect(0, $clientsInfo[$key][0]);
													socket_close($clients[$i]);
													unset($clients[$i]);
													unset($clientsInfo[$i]);
	
													
													
												}
											}
											

											
										}
										$this->linkConnection[$sn][0] = $read_sock;
									}else{
										$this->linkConnection[$sn][0] = $read_sock;
									}
								}
								if ($responseArray != False) {
									if (array_key_exists(0, $responseArray)) {
										$indexToGet = $responseArray[0];
										//register dc index in array
										if (!in_array($indexToGet, $clientsInfo[$key][9])) {
											socket_write($this->linkConnection[$sn][0], $response);
											$clientsInfo[$key][9][] = $indexToGet;
										}
									}
									else {
										socket_write($this->linkConnection[$sn][0], $response);
									}
								}
								else {
									$this->writeServerLog("\r\nResponse is empty! Please check that your device can connect to the server!\r\n");
								}

								$time_end_socket = microtime(true);
								$execution_time_socket = ($time_end_socket - $time_start_socket);
								//$dataResponse->writeCommandLog($sn, $deviceType, "\r\nTotal Execution Time 5: ".($execution_time5*1000)." Milliseconds\r\n");
								echo "\r\nTotal Execution Time Socket: ".($execution_time_socket*1000)." Milliseconds\r\n";
								unset($data);
								
							}
							else
							{
								$key = array_search($read_sock, $clients);
								if($key){
									//socket_close($clients[$this->key1]);
									socket_close($clients[$key]);
									//$output->writeln("\r\nSocket closed ! Data doesn't come from a device !\r\n");
									echo "\r\nSocket closed ! Data doesn't come from a device !\r\n";
									unset($clients[$this->key1]);
									unset($clientsInfo[$this->key1]);
								}				
							}
							
						}
					}
					$time_end_device = microtime(true);
					$execution_time_device = ($time_end_device - $time_start_device)*1000;
					//echo "\r\nTotal Execution Time Device: ".($execution_time_device)." Milliseconds\r\n";
					$this->writeServerLog("\r\nTotal Execution Time after Connected: ".($execution_time_device)." Milliseconds\r\n");
					//$clientsInfo[$key][8][] = $execution_time_device;

			} 
			$time_end_while = microtime(true);
			$execution_time_while = ($time_end_while - $time_start_while)*1000;
			//echo "\r\nTotal Execution Time of while iter: ".($execution_time_while)." Milliseconds\r\n";
			//$this->writeServerLog("\r\nTotal Execution Time of while iter: ".($execution_time_while)." Milliseconds\r\n");
			//$clientsInfo[$key][7][] = $execution_time_while;
			
			// end of reading foreach
		}
		// close the listening socket
		socket_close($sock);
		//$output->writeln("\r\nSocket closed ! Something is false.\r\n");
		echo "\r\nSocket closed ! Something is false.\r\n";
	}
}