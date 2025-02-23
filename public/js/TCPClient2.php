<?php
//namespace

require_once dirname(__FILE__, 3).'/configServer/config.php';

/* Prod Address */
//$address = "51.91.18.215";
//$service_port = 5006;
$socket;

/**
 * Create socket and connect to socket
 * @return Socket|bool|resource
 */
function connectToServer(){
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        //echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        return false;
    }
    else {
        if (!(socket_connect($socket, ADDRESS, PORT))) {
            //echo "socket_connect() failed.\nReason:  " . socket_strerror(socket_last_error($socket)) . "\n";
            return false;
            
        }
        else {
            return $socket;
        }
    }
}

/**
 * Get 1 or 0 from server
 * @param Socket|bool|resource $socket
 * @return int
 */
function getMsgConnectFromServer($socket){
    $buf = 'This is my buffer.';
    if ($socket != false and ($bytes = socket_recv($socket, $buf, 1, MSG_WAITALL)) != false) {
        socket_close($socket);
        return 1;
    } else {
        //echo "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
        socket_close($socket);
        return 0;
    }    
}

/**
 * Get data from server & close socket
 * @param Socket|bool|resource $socket
 * 
 */
function getMsgFromServer($socket){
    $buf = '';
    if (false !== ($bytes = socket_recv($socket, $buf, 240, MSG_WAITALL))) {
        //echo("Read $bytes bytes from socket_recv(). Closing socket...");
        socket_close($socket);
        
    } else {
        //echo "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
        socket_close($socket);
        //return false;
    }
    return $buf;
    
}

function getResponse($msgToSend) {
    $socket = connectToServer();
    socket_write($socket, $msgToSend);
    $response = getMsgFromServer($socket);
    return $response;
}

/**
 * setFooter
 * @param string $data
 * @return string
 */
function setFooter($data){
	$tramSize = 0;
	for($parse = 0; $parse < strlen($data); $parse++){
		$tramSize = $tramSize + (hexdec(bin2hex($data[$parse])) * (($parse%255)+1));
    }
    $result = chr($tramSize);
    return $result;

}

/**
 * Summary of decodeData
 * @param null|string $data
 * @return array
 */
function decodeData($data){
    for($parse = 4; $parse < 18; $parse +=2){
			$diag[DATA_BX_LABEL[$parse]] = '';
			if(isset(DATA_MESURE_PRE[$parse])){
				$diag[DATA_BX_LABEL[$parse]] = DATA_MESURE_PRE[$parse];
			}
			if(DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])] !== NULL){
					$diag[DATA_BX_LABEL[$parse]] .= DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])];
			}else{
					$diag[DATA_BX_LABEL[$parse]] .= hexdec($data[$parse])*16+hexdec($data[$parse + 1]);
			}
			if(isset(DATA_MESURE_SUF[$parse])){
				$diag[DATA_BX_LABEL[$parse]] .= DATA_MESURE_SUF[$parse];
			}
    }
    
    for($parse = 18; $parse < 62; $parse += 4){
        $diag[DATA_BX_LABEL[$parse]] = '';
        if(isset(DATA_MESURE_PRE[$parse])){
            $diag[DATA_BX_LABEL[$parse]] = DATA_MESURE_PRE[$parse];
        }
		if(isset(DATA_BX_LABEL[$parse])){
			if ((isset(DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])]))
				&& (DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])] !== NULL)){
					$diag[DATA_BX_LABEL[$parse]] .= DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])];
			}else{
					$diag[DATA_BX_LABEL[$parse]] .= hexdec($data[$parse])*16+hexdec($data[$parse + 1]);
					$diag[DATA_BX_LABEL[$parse]] .= '.';
					$diag[DATA_BX_LABEL[$parse]] .= hexdec($data[$parse+2])*16+hexdec($data[$parse + 3]);
			}
		}
        if(isset(DATA_MESURE_SUF[$parse])){
            $diag[DATA_BX_LABEL[$parse]] .= DATA_MESURE_SUF[$parse];
        }
    }

    for($parse = 62; $parse < 66; $parse += 2){
        $diag[DATA_BX_LABEL[$parse]] = '';
        if(isset(DATA_MESURE_PRE[$parse])){
            $diag[DATA_BX_LABEL[$parse]] = DATA_MESURE_PRE[$parse];
        }
		if(DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])] !== NULL){
				$diag[DATA_BX_LABEL[$parse]] .= DATA_BX_VALUE[DATA_BX_LABEL[$parse]][hexdec($data[$parse])+hexdec($data[$parse + 1])];
		}else{
				$diag[DATA_BX_LABEL[$parse]] .= hexdec($data[$parse])*16+hexdec($data[$parse + 1]);
		}
        if(isset(DATA_MESURE_SUF[$parse])){
            $diag[DATA_BX_LABEL[$parse]] .= DATA_MESURE_SUF[$parse];
        }
    }
    
    return $diag;
}

function setMsg($cmd,$datasize, $trackZone0, $trackZone1, $tagTouch0, $tagTouch1, $sn, $page, $cmdString = ""){
    $msg = $sn;
	if(isset($cmdString) && !empty($cmdString)){
		$datasize = $datasize + strlen($cmdString) + 1;
		$data = chr(intval(170)).chr(intval(255)).chr(0).chr($cmd).chr(intval($datasize/256)).chr(intval($datasize%256)).chr($trackZone0).chr($trackZone1).chr($tagTouch0).chr($tagTouch1).chr($page).$cmdString.chr(0);
	}else{
		$data = chr(intval(170)).chr(intval(255)).chr(0).chr($cmd).chr(intval($datasize/256)).chr(intval($datasize%256)).chr($trackZone0).chr($trackZone1).chr($tagTouch0).chr($tagTouch1).chr($page);
	}
    $msg .= $data.setFooter($data);
        
    return $msg;
}

if(isset($_POST)){
    switch ($_POST['action']) {
        case 'connect':
            //echo 'connect';
            if (($socket = connectToServer()) != null) {
                //$socket = connectToServer();
                //echo 'connect !';
                //echo $_POST['sn'];
                //sendMsgToServer($_POST['sn'], $socket);
                //print_r($socket);
                socket_write($socket, $_POST['sn']);
                //echo 'msg sent !';
                $isConnect = getMsgConnectFromServer($socket);
                //echo 'msg get !';
                echo $isConnect;
                //closeCnx();
                //break;
            }
            else {
                echo "else";
            }
            
            break;
        case 'disconnect':
            /*
            if (($socket = connectToServer()) != null) {
                //$request->setConnect(0, $_POST['sn']);
			    //closeCnx();
                socket_close($socket);
            }
            */
			break;
        case 'buttonTouch':
            $trackZone = 0;
            if(($_POST['cmd'] == 'tecaIntensity')){
                if($_POST['tagTouch1'] == 10)
                    $add = 0;
                else {
                    $add = 0.5;
                }
                $trackZone = ($_POST['tagTouch1'] + $add) * 255/10;
                $msgToSend = setMsg(COMMAND['touch'], 5, $trackZone, 0, 0, 12, $_POST['sn']);
            }else if(($_POST['cmd'] == 'timerSlide')){
                $trackZone = ((($_POST['tagTouch1'] + 2.5)/5) - 1) * 255/12;
                echo $_POST['tagTouch1']." trackzone = ".$trackZone;
                $msgToSend = setMsg(COMMAND['touch'], 5, $trackZone, 0, 0, 13, $_POST['sn']);
            }else{
                $msgToSend = setMsg(COMMAND['touch'], 5, $trackZone, 0, 0, DATA_DEVICE[$_POST['cmd']][$_POST['tagTouch1']], $_POST['sn'], $_POST['page']);
            }
            $socket = connectToServer();
            //sendMsgToServer($msgToSend, $socket);
            socket_write($socket, $msgToSend);
            $response = getMsgFromServer($socket);
            $diag = decodeData($response);
            echo json_encode($diag);
            break;
        case 'periodictyConnect':
            $socket = connectToServer();
            $response = getMsgFromServer($socket);
            $diag = decodeData($response);
            echo json_encode($diag);
            break;
        case 'test':
            //echo $_POST['sn'];
            $msgToSend = setMsg(0, 5, 0, 0, 0, 0, $_POST['sn'], $_POST['page']);
            //if (($socket = connectToServer()) != null) {
                try {
                    $socket = connectToServer();
                    //echo 'test connect !';
                    //sendMsgToServer($msgToSend, $socket);
                    if ($socket != null && $socket != false) {
                        socket_write($socket, $msgToSend);
                        $response = getMsgFromServer($socket);
                        $diag[] = substr($response, 20);
                        echo json_encode($diag);
                    }
                    break;
                } catch (error) {
                    break;
                    // expected output: ReferenceError: nonExistentFunction is not defined
                    // Note - error messages will vary depending on browser
                }

            //}
            //break;
		case 'touchTest':
			$tagTouch0 = $_POST['tagTouch']/256;
			$tagTouch1 = $_POST['tagTouch']%256;
			$trackTouch0 = $_POST['trackerTouch']/256;
			$trackTouch1 = $_POST['trackerTouch']%256;
            $msgToSend = setMsg(1, 5, $trackTouch0, $trackTouch1, $tagTouch0, $tagTouch1, $_POST['sn'], $_POST['page']);
            $socket = connectToServer();
            //sendMsgToServer($msgToSend, $socket);
            socket_write($socket, $msgToSend);
            $response = getMsgFromServer($socket);
			$diag[] = substr($response, 20);
            echo json_encode($diag);
			break;
		case 'cmdTest':
            $msgToSend = setMsg(2, 5, 0, 0, 0, 0, $_POST['sn'], $_POST['page'], $_POST['tagTouch1']);
            $socket = connectToServer();
            //sendMsgToServer($msgToSend, $socket);
            socket_write($socket, $msgToSend);
            $response = getMsgFromServer($socket);
			$diag[] = substr($response, 20);
            echo json_encode($diag);
			break;
		case 'pageTest':
			$GLOBALS['page'] = $_POST['tagTouch1'];
			break;
        default:
            break;
    }
}