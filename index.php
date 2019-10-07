<?php

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$address = "127.0.0.1";
$port = "1222";
GLOBAL $clients;
GLOBAL $client_list;


$PROFILE_ROOT_DIR = './Profiles/';
$DEFAULT_PROFILE_PATH = $PROFILE_ROOT_DIR . 'Default';
$NEW_PROFILE = '1';



$create_cmd = "cp -r " . $DEFAULT_PROFILE_PATH . " ". $PROFILE_ROOT_DIR . $NEW_PROFILE;
shell_exec($create_cmd);


?>
