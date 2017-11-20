<?php

//Include file
include ("lib/fusion.eventsocket.php");
include ("lib/astpp.sipstatus.php");

if (isset($_POST['username'])) {
    $sip_device_to_search = $_POST['username'];
    if (get_device_status($sip_device_to_search)) {
        $result = array('success' => true, 'state' => 1);
    } else {
        $result = array('success' => true, 'state' => 0);
    }
} else {
    $result = array('success' => false);
}

echo json_encode($result);

?>
