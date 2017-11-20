<?
function get_device_status($sip_device_to_search) {

    $fp = event_socket_create(); // Assume we're using defailt config

    $cmd = "api sofia xmlstatus profile default reg";
    $xml_response = trim(event_socket_request($fp, $cmd));

    if ($xml_response == "Invalid Profile!") { 

    }
    $xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
    $xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
    if (strlen($xml_response) > 101) {
        try {
            $xml = new SimpleXMLElement($xml_response);
        }
        catch(Exception $e) {
            return false;
        }
        $registrations = json_decode(json_encode($xml) , true);
    }

    //normalize the array
    if (is_array($registrations) && !is_array($registrations['registrations']['registration'][0])) {
        $row = $registrations['registrations']['registration'];
        unset($registrations['registrations']['registration']);
        $registrations['registrations']['registration'][0] = $row;
    }

    if (is_array($registrations)) {
        foreach ($registrations['registrations']['registration'] as $row) {
            if ($row['sip-auth-user'] == $sip_device_to_search) {
                return true;
            }
        }
    }
    return false;
}

?>