<?php

//Include file
include ("lib/fusion.eventsocket.php");

$fp = event_socket_create(); // Assume we're using defailt config

$cmd = "show registrations";

$responce = event_socket_request($cmd);

print($responce);

?>
