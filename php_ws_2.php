<?php
error_reporting(E_ALL);
define('HOST_NAME',"localhost"); 
define('PORT',"8090");
$null = NULL;

require_once("class.chathandler.php");
$chatHandler = new ChatHandler();

$PROFILES_ROOT_DIR = "./Profiles/";
$DEFAULT_PROFILE_PATH = $PROFILES_ROOT_DIR . "Default";
$FILE_PATH = "./profiles.txt"; 
$TEMP_FILE_PATH = "./profiles_temp.txt";
$CLIENT_INSTANCES = array();

$socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socketResource, 0, PORT);
socket_listen($socketResource);

function create_profile($url, $profile_id){
	GLOBAL $PROFILES_ROOT_DIR;
	GLOBAL $DEFAULT_PROFILE_PATH;
	GLOBAL $FILE_PATH;
	GLOBAL $TEMP_FILE_PATH;
	$profile_name = strval($profile_id);
	echo $PROFILES_ROOT_DIR;
	$copy_command = "cp -R " . $DEFAULT_PROFILE_PATH . " " . $PROFILES_ROOT_DIR . $profile_name;
	echo $copy_command;
	shell_exec($copy_command);
	$chrome_command = "google-chrome " . $url . " --user-data-dir=". $PROFILES_ROOT_DIR . $profile_name . " --no-default-browser-check --disable-gpu --disable-features=NetworkService &";
	$output_chrome = exec($chrome_command);
}


function open_profile($url, $profile_id){
	GLOBAL $PROFILES_ROOT_DIR;
	GLOBAL $DEFAULT_PROFILE_PATH;
	GLOBAL $FILE_PATH;
	GLOBAL $TEMP_FILE_PATH;

	echo "Open";

	$chrome_command = "google-chrome " . $url . " --user-data-dir=". $PROFILES_ROOT_DIR . $profile_id . " --no-first-run --no-default-browser-check --disable-gpu --disable-features=NetworkService &";
	shell_exec($chrome_command);
}

function update_profile_status($request){
  GLOBAL $CLIENT_INSTANCES, $FILE_PATH, $TEMP_FILE_PATH;
  echo "1";

  $mobile_no = $request[1];
  $profile_id = $request[2];
  $status = $request[3];
  $timestamp = time();

  $update_message = json_encode([$mobile_no, $profile_id, $status, $timestamp]);
  if(array_key_exists('main', $CLIENT_INSTANCES)){
    $CLIENT_INSTANCES['main']->send($update_message);
  }

  if(file_exists($FILE_PATH)){
    $file1 = fopen($FILE_PATH, "r") or die("Unable to open file!");
    $file2 = fopen($TEMP_FILE_PATH, "w+");

    $update = FALSE;
    while(!feof($file1)) {
      $line = fgets($file1);
      $profile_data = json_decode(str_replace("\n", "", $line));
      if($mobile_no == $profile_data[0] && $profile_id == $profile_data[1]){
        $update = TRUE;
        fwrite($file2, $update_message . "\n");
      } else{
        fwrite($file2, $line);
      }
    }

    if($update == FALSE){
      fwrite($file2, $update_message . "\n");
    }

    fclose($file1);
    fclose($file2);

    shell_exec("mv ". $TEMP_FILE_PATH . " " . $FILE_PATH);

  } else{
    $file1 = fopen($FILE_PATH, "w");
    fwrite($file1, $update_message . "\n");
    fclose($file1);
  }
}

function send_users($CH){
  GLOBAL $FILE_PATH;
  $users = array();
  $file = fopen($FILE_PATH, 'r') or die("Unable to open file!");
  while(!feof($file)) {
    $line = fgets($file);
    $profile_data = json_decode(str_replace("\n", "", $line));
    if($profile_data != NULL){
      array_push($users, $profile_data);
    }
  }

  $response = json_encode(array("type" => "get_users", "data"=> $users));
  return $response;
}

$clientSocketArray = array($socketResource);
while (true) {
	$newSocketArray = $clientSocketArray;
	socket_select($newSocketArray, $null, $null, 0, 10);
	
	if (in_array($socketResource, $newSocketArray)) {
		$newSocket = socket_accept($socketResource);
		$clientSocketArray[] = $newSocket;
		
		$header = socket_read($newSocket, 1024);
		$chatHandler->doHandshake($header, $newSocket, HOST_NAME, PORT);
		
		socket_getpeername($newSocket, $client_ip_address);
    $connectionACK = $chatHandler->newConnectionACK($client_ip_address);
    $chatHandler->send($connectionACK);
		
		$newSocketIndex = array_search($socketResource, $newSocketArray);
		unset($newSocketArray[$newSocketIndex]);
	}
	
	foreach ($newSocketArray as $newSocketArrayResource) {	
		while(socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1){
			$socketMessage = $chatHandler->unseal($socketData);

      $request = json_decode($socketMessage);
      // echo $request;
      if($request[0] == 'create_profile'){
        create_profile($request[1], $request[2]);
      }
      elseif($request[0] == 'open_profile'){
        open_profile($request[1], $request[2]);
      }
      elseif($request[0] == 'Presence'){
        update_profile_status($request);
      }
      elseif($request[0] == 'get_users'){
        $checking_url = $request[1];
        $CLIENT_INSTANCES['main'] = $chatHandler;
        $response = send_users($chatHandler);
        $connectionACK2 = $chatHandler->sendMessage($client_ip_address, $response);    
        $chatHandler->send($connectionACK2);
      }
      
			// $chat_box_message = $chatHandler->createChatBoxMessage($messageObj->chat_user, $messageObj->chat_message);
			// $chatHandler->send($chat_box_message);
			break 2;
		}
		
		$socketData = @socket_read($newSocketArrayResource, 1024, PHP_NORMAL_READ);
		if ($socketData === false) { 
			socket_getpeername($newSocketArrayResource, $client_ip_address);
			$connectionACK = $chatHandler->connectionDisconnectACK($client_ip_address);
			$chatHandler->send($connectionACK);
			$newSocketIndex = array_search($newSocketArrayResource, $clientSocketArray);
			unset($clientSocketArray[$newSocketIndex]);			
		}
	}
}
socket_close($socketResource);
?>