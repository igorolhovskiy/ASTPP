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
    $data_key = "daily_limit_" + gmdate("Y_m_d");
    $query = "SELECT * FROM fraud_limits WHERE limit_key = '$data_key' AND account_id = $user_id LIMIT 1";
    $limit_set = $db->run ( $query );

    // TOTALLY INCORRECT CASE!
    if (count($limit_set) == 0) {
        $query = "INSERT INTO fraud_limits (account_id, limit_key, limit_value)";
        $query .= " VALUES(";
        $query .= " $user_id, '$data_key','$amount')";
        $logger->log ( "Daily limit create : " . $query );
        $db->run ( $query );
        return;
    }


    $query = "UPDATE fraud_limits SET limit_value = CAST(CAST(limit_value AS SIGNED) + $amount AS VARCHAR)";
    $query .= " WHERE account_id=" . $user_id . " AND limit_key = '$data_key'";
    $logger->log ( "Daily limit update : " . $query );
    $db->run ( $query );
}

?>
