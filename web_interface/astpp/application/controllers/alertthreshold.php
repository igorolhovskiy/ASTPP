<?php
###############################################################################
# ASTPP - Open Source VoIP Billing Solution
#
# Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
# Samir Doshi <samir.doshi@inextrix.com>
# ASTPP Version 3.0 and above
# License https://www.gnu.org/licenses/agpl-3.0.html
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
###############################################################################

class Alertthreshold extends CI_Controller {
    function __construct()
    {
        parent::__construct();
        if(!defined( 'CRON' ) )
            exit();
        $this->load->model("db_model");
        $this->load->library("astpp/common");
    }
    function alert_threshold() {
        $where = array("alert_threshold_status" => 1,"deleted" => "0","status"=>"0");
        $query = $this->db_model->getSelect("*", "accounts", $where);
        if($query->num_rows >0){
            $account_data = $query->result_array();
            foreach($account_data as $data_key =>$account_value){
                echo $account_value["balance"], ", ", $account_value["credit_limit"],", ", $account_value["alert_threshold_value"], $account_value["alert_threshold_flag"], PHP_EOL;
                if(($account_value["balance"] + $account_value["credit_limit"]) > $account_value["alert_threshold_value"]){
                    if ($account_value["alert_threshold_flag"] == 0) {
                        $this->common->mail_to_admin("email_alert_threshold", $account_value);
                        $this->db->set('alert_threshold_flag', 1);
                        $this->db->where('id', $account_value["id"]);
                        $this->db->update("accounts");
                    }
                } else {
                    $this->db->set('alert_threshold_flag', 0);
                    $this->db->where('id', $account_value["id"]);
                    $this->db->update("accounts");
                }
            }
        }
        exit;
    }
}