<?php

//Include file
include ("lib/fusion.eventsocket.php");

$fp = event_socket_create(); // Assume we're using defailt config

$cmd = "api sofia xmlstatus profile default reg";
$xml_response = trim(event_socket_request($fp, $cmd));

if ($xml_response == "Invalid Profile!") { 

}
$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
try {
    $xml = new SimpleXMLElement($xml_response);
}
catch(Exception $e) {
    echo $e->getMessage();
    exit;
}

print($xml);

?>
