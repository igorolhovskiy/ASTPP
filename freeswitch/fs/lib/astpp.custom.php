<?php
// ##############################################################################
// ASTPP - Open Source VoIP Billing Solution
//
// Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
// Samir Doshi <samir.doshi@inextrix.com>
// ASTPP Version 3.0 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################

// Update user daily limits
/**
 *
 * @param integer $entity_id        	
 */
function update_daily_limits($user_id, $amount, $entity_id, $logger, $db, $config, $dataVariable) {

    // Check if this daily limit is existing
    $data_key = "daily_limit_" . gmdate("Y_m_d");
    $query = "SELECT * FROM fraud_limits_counters WHERE limit_key = '$data_key' AND account_id = $user_id LIMIT 1";
    $limit_set = $db->run ( $query );

    // TOTALLY INCORRECT CASE!
    if (count($limit_set) == 0) {
        $query = "INSERT INTO fraud_limits_counters (account_id, limit_key, limit_value)";
        $query .= " VALUES (";
        $query .= " $user_id, '$data_key','$amount')";
        $logger->log ( "Daily limit create : " . $query );
        $db->run ( $query );
        return;
    }

    $query = "UPDATE fraud_limits_counters SET limit_value = limit_value + $amount";
    $query .= " WHERE account_id=" . $user_id . " AND limit_key = '$data_key'";
    $logger->log ( "Daily limit update : " . $query );
    $db->run ( $query );
}

// Generate CDR string for insert query for customer.
function get_cdr_string($dataVariable, $accountid, $account_type, $actual_duration, $termination_rate, $origination_rate, $provider_cost, $parentid, $debit, $cost, $logger, $db) {
	
	$get_current_time = gmdate("Y-m-d H:i:s"); // progress_media_stamp
	$get_current_microseconds = round(microtime(true)/1000); // progress_mediamsec
	
	$dataVariable ['calltype'] = ($dataVariable ['calltype'] == 'DID-LOCAL' || $dataVariable ['calltype'] == 'SIP-DID' || $dataVariable ['calltype'] == 'OTHER') ? "DID" : $dataVariable ['calltype'];
	// $callerIdNumber = isset($dataVariable['effective_caller_id_number']) && !empty($dataVariable['effective_caller_id_number'])? $dataVariable['effective_caller_id_number'] :$dataVariable['caller_id'];
	$callerIdNumber = ($dataVariable ['calltype'] == "DID") ? $dataVariable ['effective_caller_id_name'] . " <" . $dataVariable ['effective_caller_id_number'] . ">" : $dataVariable ['original_caller_id_name'] . " <" . $dataVariable ['original_caller_id_number'] . ">";

	$dataVariable ['hangup_cause'] = get_q850code($dataVariable, $db);	
	
    //return $cdr_string = "'" . ($dataVariable ['uuid']) . "','" . $accountid . "','" . $account_type . "','" . (urldecode ( $callerIdNumber )) . "','" . ($dataVariable ['effective_destination_number']) . "','" . $actual_duration . "'," . (($termination_rate ['TRUNK']) ? $termination_rate ['TRUNK'] : '0') . "," . (($dataVariable ['sip_via_host']) ? "'" . $dataVariable ['sip_via_host'] . "'" : '""') . "," . (($dataVariable ['sip_contact_host']) ? "'" . $dataVariable ['sip_contact_host'] . "'" : '""') . ",'" . ($dataVariable ['hangup_cause']) . "','" . urldecode ( $dataVariable ['callstart'] ) . "','" . $debit . "','" . $cost . "'," . (($termination_rate ['PROVIDER']) ? $termination_rate ['PROVIDER'] : '0') . ",'" . $origination_rate [$accountid] ['RATEGROUP'] . "','" . $dataVariable ['package_id'] . "','" . ($origination_rate [$accountid] ['CODE']) . "'," . (($origination_rate [$accountid] ['DESTINATION']) ? "'" . htmlentities ( $origination_rate [$accountid] ['DESTINATION'], ENT_COMPAT, 'UTF-8' ) . "'" : "'" . '' . "'") . "," . (($origination_rate [$accountid] ['COST']) ? "'" . $origination_rate [$accountid] ['COST'] . "'" : "'" . '0' . "'") . ",'" . $parentid . "'," . (($origination_rate [$parentid] ['CODE']) ? "'" . $origination_rate [$parentid] ['CODE'] . "'" : "'" . '0' . "'") . "," . (($origination_rate [$parentid] ['DESTINATION']) ? "'" . $origination_rate [$parentid] ['DESTINATION'] . "'" : "'" . '' . "'") . "," . (($origination_rate [$parentid] ['COST']) ? "'" . $origination_rate [$parentid] ['COST'] . "'" : '0') . "," . (($termination_rate ['CODE']) ? "'" . $termination_rate ['CODE'] . "'" : "'" . '' . "'") . "," . (($termination_rate ['DESTINATION']) ? "'" . $termination_rate ['DESTINATION'] . "'" : "'" . '' . "'") . "," . (($termination_rate ['COST']) ? "'" . $termination_rate ['COST'] . "'" : '0') . ",'" . $provider_cost . "'," . (($dataVariable ['call_direction']) ? "'" . $dataVariable ['call_direction'] . "'" : "'internal'") . ",'" . ($dataVariable ['calltype']) . "','" . $dataVariable ['call_request'] . "','" . $origination_rate [$accountid] ['CI'] . "','".$dataVariable ['sip_user']."','".$dataVariable ['origination_call_type']."','" . urldecode ( $dataVariable ['end_stamp'] ) . "'";
    
    $cdr_string	=  "'" . ($dataVariable['uuid']) . "',"; // uniqueid
    $cdr_string .= "'" . $accountid . "',"; //accountid
    $cdr_string .= "'" . $account_type . "',"; // type 
    $cdr_string .= "'" . (urldecode($callerIdNumber )) . "',"; // callerid
    $cdr_string .= "'" . ($dataVariable['effective_destination_number']) . "',"; // callednum
    $cdr_string .= "'" . $actual_duration . "',"; // billseconds
    $cdr_string .= "'" . (($termination_rate ['TRUNK']) ? $termination_rate['TRUNK'] : '0') . "',"; // trunk_id
    $cdr_string .= "'" . (($dataVariable['sip_via_host']) ? $dataVariable['sip_via_host'] : "\"\"") . "',"; // trunkip
    $cdr_string .= "'" . (($dataVariable['sip_received_ip']) ? $dataVariable['sip_received_ip'] : "\"\"") . "',"; // callerip
    $cdr_string .= "'" . ($dataVariable['hangup_cause']) . "',"; // disposition 
    $cdr_string .= "'" . (urldecode($dataVariable ['callstart'])) . "',"; // callstart
    $cdr_string .= "'" . $debit . "',"; // debit
    $cdr_string .= "'" . $cost . "',"; // cost 
    $cdr_string .= "'" . (($termination_rate ['PROVIDER']) ? $termination_rate ['PROVIDER'] : '0') . ","; // provider_id
    $cdr_string .= "'" . $origination_rate[$accountid]['RATEGROUP'] . "',"; // pricelist_id
    $cdr_string .= "'" . $dataVariable['package_id'] . "',"; // package_id
    $cdr_string .= "'" . $origination_rate [$accountid]['CODE'] . "',"; // pattern
    $cdr_string .= "'" . (($origination_rate[$accountid]['DESTINATION']) ? htmlentities ($origination_rate[$accountid]['DESTINATION'], ENT_COMPAT, 'UTF-8')  : "\"\"") . "',"; // notes
    $cdr_string .= "'" . (($origination_rate[$accountid]['COST']) ? $origination_rate[$accountid]['COST'] : "0") . "',"; // rate_cost
    $cdr_string .= "'" . $parentid . "',"; // reseller_id
    $cdr_string .= "'" . (($origination_rate[$parentid]['CODE']) ? $origination_rate[$parentid]['CODE'] : "0") . "',"; // reseller_code
    $cdr_string .= "'" . (($origination_rate[$parentid]['DESTINATION']) ? $origination_rate[$parentid]['DESTINATION'] : "\"\"") . "',"; // reseller_code_destination
    $cdr_string .= "'" . (($origination_rate [$parentid]['COST']) ? $origination_rate[$parentid]['COST'] : "0") . "',"; // reseller_cost
    $cdr_string .= "'" . (($termination_rate['CODE']) ? $termination_rate['CODE'] : "\"\"") . "',"; // provider_code
    $cdr_string .= "'" . (($termination_rate['DESTINATION']) ? $termination_rate['DESTINATION'] : "\"\"") . "',"; // provider_code_destination
    $cdr_string .= "'" . (($termination_rate['COST']) ? $termination_rate['COST'] : "0") . "',"; // provider_cost
    $cdr_string .= "'" . $provider_cost . "',"; // provider_call_cost
    $cdr_string .= "'" . (($dataVariable ['call_direction']) ? ucfirst($dataVariable['call_direction']) : "Internal") . "',"; // call_direction
    $cdr_string .= "'" . $dataVariable['calltype'] . "',"; // calltype
    $cdr_string .= "'" . $dataVariable['call_request'] . "',"; // call_request
    $cdr_string .= "'" . $origination_rate[$accountid]['CI'] . "',"; // country_id
    $cdr_string .= "'" . $dataVariable['sip_user'] . "',"; // sip_user
    $cdr_string .= "'" . $dataVariable['origination_call_type'] . "',"; // ct
    $cdr_string .= "'" . date("Y-m-d H:i:s", (strtotime(date(urldecode($dataVariable['callstart']))) + $actual_duration)) . "'"; // end_stamp

	return $cdr_string;
}

?>