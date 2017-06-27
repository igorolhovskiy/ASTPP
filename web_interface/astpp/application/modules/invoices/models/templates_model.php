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
class Templates_model extends CI_Model {

	function Templates_model() {
		parent::__construct();
        error_reporting(E_ALL);
	}

    function gettemplate_list($flag = "", $start, $limit = "") {
        $this->db_model->build_search('template_search');
        if ($flag) {
            $query = $this->db_model->select("invoice_templates.*", "invoice_templates", "", "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("invoice_templates.*", "invoice_templates", "");
        }

        return $query;
    }

    function edit_template($data, $id) {
        unset($data["action"]);
        $data["last_modified_date"] = date("Y-m-d H:i:s");
        $this->db->where("id", $id);
        $this->db->update("invoice_templates", $data);
    }

    function add_template($data)
    {
        unset($data["action"]);
        unset($data['id']);
        $data["last_modified_date"] = date("Y-m-d H:i:s");
        $this->db->insert('invoice_templates', $data);
        return true;
    }

    function delete($id) {
        $this->db->where("id", $id);
        $this->db->delete("invoice_templates");
        return true;
    }

    function help_variables() {
        $variables = array();
        $variables[] = array(
            'name' => 'logo',
            'query' => 'assets/images/logo_3.png',
            'comment' => 'variable of image'
        );
        $variables[] = array(
            'name' => 'strip',
            'query' => 'assets/images/bg_strip.png',
            'comment' => 'variable of image'
        );
        $variables[] = array(
            'name' => 'invoicedata',
            'query' => 'select * from invoices where id = {$invoiceid}',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'accounts',
            'query' => 'select * from accounts where id = {$accountid}',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'accountinfo',
            'query' => '',
            'comment' => 'Get data from accounts for current user id'
        );
        $variables[] = array(
            'name' => 'invoice_conf',
            'query' => 'select * from invoice_conf where accountid =  {$account_info.id}',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'currency',
            'query' => 'select currensy from currency where id = {$accountinfo.currency_id}',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'from_date',
            'query' => 'date("Y-m-d", strtotime({$invoicedata.from_date}))',
            'comment' => ''
        );
        $variables[] = array(
			'name' => 'to_date',
			'query' => 'date("Y-m-d", strtotime({$invoicedata.to_date}))',
			'comment' => ''
		);
		$variables[] = array(
			'name' => 'invoice_date',
			'query' => 'date("Y-m-d", strtotime({$invoicedata.invoice_date}))',
			'comment' => ''
		);
        $variables[] = array(
            'name' => 'due_date',
            'query' => 'new DateTime()',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'today',
            'query' => 'date("Y-m-d", strtotime({$invoicedata.due_date}))',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'invoice_details',
            'query' => 'select * from invoice_details where id = {$invoicedata.id} and item_type <> \'TAX\'',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'total_sum',
            'query' => '',
            'comment' => 'Sum debet from invoice_details with currency'
        );
        $variables[] = array(
            'name' => 'invoice_details_tax',
            'query' => 'select * from invoice_details where id = {$invoicedata.id} and item_type = \'TAX\'',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'total_vat',
            'query' => '',
            'comment' => 'Sum debet from invoice_details_tax with currency'
        );
        $variables[] = array(
            'name' => 'sub_total',
            'query' => '{$total_sum} + {$total_vat}',
            'comment' => ''
        );
        $variables[] = array(
            'name' => 'group_call',
            'query' => 'select cdrs.*, routes.group_calls_id, group_calls.name as group_calls_name, SUM(cdrs.billseconds) as total_seconds, SUM(cdrs.debit) as total_debit from cdrs join routes on routes.pattern = cdrs.pattern join group_calls on group_calls.id = routes.group_calls_id where cdrs.callstart > $from and cdrs.callstart < $to and cdrs.pricelist_id > 0 and cdrs.billseconds > 0 and cdrs.accountid = $user_id and routes.group_calls_id > 0 group by routes.group_calls_id',
            'comment' => ''
        );
		$variables[] = array(
			'name' => 'destination_group_call',
			'query' => 'select routes.comment, COUNT(*) AS count_calls,, SUM(cdrs.billseconds) as total_seconds, SUM(cdrs.debit) as total_debit from cdrs join routes on routes.pattern = cdrs.pattern AND routes.pricelist_id = cdrs.pricelist_id  where cdrs.callstart > $from and cdrs.callstart < $to and cdrs.pricelist_id > 0 and cdrs.billseconds > 0 and cdrs.accountid = $user_id group by routes.comment',
			'comment' => ''
		);
        $vars_query = $this->db_model->getSelect("*", "invoice_template_vars",'');
        $variables = array_merge($variables, $vars_query->result_array());
        return $variables;
    }

	function get_destination_group_calls_cdrs($user_id, $from, $to) {
		$this->db->select('routes.comment as destination');
		$this->db->select('count(*) as count_calls');
		$this->db->select('SUM(cdrs.billseconds) as total_seconds');
		$this->db->select('SUM(cdrs.debit) as total_debit');

		$this->db->from('cdrs');

		$this->db->join('routes', 'routes.pattern = cdrs.pattern AND routes.pricelist_id = cdrs.pricelist_id');

		$this->db->where('cdrs.callstart >', $from);
		$this->db->where('cdrs.callstart <', $to);
		$this->db->where('cdrs.pricelist_id >', 0);
		$this->db->where('cdrs.billseconds >', 0);
		$this->db->where('cdrs.accountid', $user_id);

		$this->db->group_by('routes.comment');

		return $this->db->get()->result_array();
	}
}
