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

class GenerateInvoice extends MX_Controller {

    public static $global_config;

    function __construct() {
        parent::__construct();
        $this->load->model("db_model");
        $this->load->library("astpp/common");
        $this->load->library('html2pdf');
        ini_set("memory_limit", "2048M");
        ini_set("max_execution_time", "259200");
        $this->get_system_config();
    }

    function get_system_config() {
        $query = $this->db->get("system");
        $config = array();
        $result = $query->result_array();
        foreach ($result as $row) {
            $config[$row['name']] = $row['value'];
        }
        self::$global_config['system_config'] = $config;
    }

    function getInvoiceData() {
		log_message('error','GENERATE INVOICE: Starting');
        $where = array("posttoexternal" => 1, "deleted" => "0", "status" => "0");
        $query = $this->db_model->getSelect("*", "accounts", $where);
        if ($query->num_rows > 0) {
            $account_data = $query->result_array();
            foreach ($account_data as $data_key => $account_value) {
				log_message('error','GENERATE INVOICE: '.'Processing row: '.json_encode($account_value));
                $end_date = gmdate("Y-m-d")." 23:59:59";
                $account_value['sweep_id'] = (int)$account_value['sweep_id'];
                switch ($account_value['sweep_id']) {
                    case 0:
                        $start_date = $this->validate_invoice_date($account_value);
                        if (strtotime($start_date) >= strtotime(gmdate("Y-m-d H:i:s"))) {
                            $start_date = gmdate("Y-m-d H:i:s");
                        }
                        $end_date = gmdate("Y-m-d 23:59:59", strtotime($start_date));
                        $yesterday = gmdate("Y-m-d 23:59:59", strtotime(gmdate("Y-m-d H:i:s")." - 1 days"));
						log_message('error','GENERATE INVOICE: '."start_date = $start_date, end_date = $end_date, yesterday = $yesterday");
                        if (strtotime($end_date) <= strtotime($yesterday)) {
							log_message('error','GENERATE INVOICE: '.'go to Generate_Daily_invoice');
							$this->Generate_Daily_invoice($account_value, $start_date, $end_date);
						}
                        break;
                    case 2:
                        if (date("d") == $account_value['invoice_day']) {
                            $start_date = $this->validate_invoice_date($account_value);
                            if (strtotime($start_date) >= strtotime(gmdate("Y-m-d H:i:s"))) {
                                $start_date = gmdate("Y-m-d H:i:s");
                            }
                            //$end_date = gmdate("Y-m-d 23:59:59", strtotime($start_date." + 1 month"));
                            // set end date to day before generate invoice
                            $end_date = gmdate("Y-m-d 23:59:59", strtotime(gmdate("Y-m-d H:i:s")." - 1 days"));
							log_message('error','GENERATE INVOICE: '."start_date = $start_date, end_date = $end_date");
							log_message('error','GENERATE INVOICE: '.'go to Generate_Monthly_invoice');
                            $this->Generate_Monthly_invoice($account_value, $start_date, $end_date);
                        }
                        break;
                }
            }
            $screen_path = getcwd()."/cron";
            $screen_filename = "Email_Broadcast_".strtotime('now');
            $command = "cd ".$screen_path." && /usr/bin/screen -d -m -S  $screen_filename php cron.php BroadcastEmail";
            exec($command);
        }
    }

    function validate_invoice_date($account_value) {
        $last_invoice_date = $this->common->get_invoice_date("to_date", $account_value["id"], $account_value['reseller_id'], "to_date");
        $last_invoice_date = ($last_invoice_date) ? $last_invoice_date : $account_value['creation'];
        $last_invoice_date = gmdate("Y-m-d H:i:s", strtotime("+1 Second", strtotime($last_invoice_date)));
        return $last_invoice_date;
    }

    /**
     * @param string $start_date
     * @param string $end_date
     */
    function Generate_Daily_invoice($account_value, $start_date, $end_date) {
        //  echo "INVOICE SCRIPT-------start date :".$start_date."-------end date....".$end_date;

        require_once('updateBalance.php');
        $updateBalance = new updateBalance();
        $updateBalance->process_subscriptions($account_value, $start_date, $end_date, TRUE);
        $updateBalance->process_DID_charges($account_value, $start_date, $end_date, TRUE);
        $this->process_invoice($account_value, $start_date, $end_date);
    }

    /**
     * @param string $start_date
     * @param string $end_date
     */
    function Generate_Monthly_invoice($account_value, $start_date, $end_date) {
        require_once('updateBalance.php');
        $updateBalance = new updateBalance();
        $updateBalance->process_subscriptions($account_value, $start_date, $end_date, TRUE);
        $updateBalance->process_DID_charges($account_value, $start_date, $end_date, TRUE); 
        $this->process_invoice($account_value, $start_date, $end_date);
    }

    function process_invoice($accountdata, $start_date, $end_date) {
        //Get Invoice configuration using single query instead of multiple queries.
        $invoice_conf = array();
        $reseller_id = ($accountdata['reseller_id'] == 0) ? 1 : $accountdata['reseller_id'];
        $where = "accountid IN ('".$reseller_id."','1')";
        $this->db->select('*');
        $this->db->where($where);
        $this->db->order_by('accountid', 'desc');
        $this->db->limit(1);
        $invoice_conf = $this->db->get('invoice_conf');
        $invoice_conf = (array)$invoice_conf->first_row();
        /*******************************************************/
        $last_invoice_ID = $this->common->get_invoice_date("invoiceid", "", $accountdata['reseller_id']);
        if ($last_invoice_ID && $last_invoice_ID > 0) {
            $last_invoice_ID = ($last_invoice_ID + 1);
        } else {
            $last_invoice_ID = $invoice_conf['invoice_start_from'];
        }
        $last_invoice_ID = str_pad($last_invoice_ID, (strlen($last_invoice_ID) + 4), '0', STR_PAD_LEFT);
        $invoice_sub_total = $this->count_invoice_data($accountdata, $start_date, $end_date);
        if ($invoice_sub_total > 0) {
            $invoiceid = $this->create_invoice($accountdata, $start_date, $end_date, $last_invoice_ID, $invoice_conf['invoice_prefix'], $invoice_conf);
            $this->update_cdrs_data($accountdata['id'], $invoiceid, $start_date, $end_date);
            $sort_order = $this->common_model->apply_invoice_taxes($invoiceid, $accountdata, $start_date);
            $invoice_total = $this->set_invoice_total($invoiceid, $accountdata['id']);
            $this->download_invoice($invoiceid, $accountdata, $invoice_conf);
        } else {
            $last_invoice_ID = '0000000';
            $invoice_type = 'zero';
            $invoiceid = $this->create_invoice($accountdata, $start_date, $end_date, $last_invoice_ID, $invoice_conf['invoice_prefix'], $invoice_conf, $invoice_type);
			$this->update_cdrs_data($accountdata['id'], $invoiceid, $start_date, $end_date);
            $sort_order = $this->common_model->apply_invoice_taxes($invoiceid, $accountdata, $start_date);
            $invoice_total = $this->set_invoice_total($invoiceid, $accountdata['id']);
        }
    }

    function count_invoice_data($account, $start_date = "", $end_date = "") {
        $cdr_query = "";
        $inv_data_query = "";
        $cdr_query = "select calltype,sum(debit) as debit, sum(billseconds) as billseconds from cdrs where accountid = ".$account['id'];
        $cdr_query .= " AND callstart >='".$start_date."' AND callstart <= '".$end_date."' AND invoiceid=0 group by calltype";
//echo $cdr_query; 
        $cdr_data = $this->db->query($cdr_query);
        if ($cdr_data->num_rows > 0) {
            $cdr_data = $cdr_data->result_array();
            //echo '<pre>'; print_r($cdr_data); exit;
            foreach ($cdr_data as $cdrvalue) {
                $cdrvalue['debit'] = round($cdrvalue['debit'], self::$global_config['system_config']['decimalpoints']);
                if ($cdrvalue['calltype'] === 'STANDARD' || $cdrvalue['calltype'] === 'PACKAGE+') {
                    $description = 'Gesprächsgebühren '.$start_date." - ".$end_date;
                } else {
                    $description = $cdrvalue['calltype']." CALLS for the period (".$start_date." to ".$end_date.")";
                }
                $tempArr = array("accountid" => $account['id'], "reseller_id" => $account['reseller_id'], "item_id" => "0",
                    "description" => $description, "debit" => $cdrvalue['debit'], "count" => $cdrvalue['billseconds'], "item_type" => $cdrvalue['calltype'], "created_date" => $end_date);
                $this->db->insert("invoice_details", $tempArr);
            }
        }

        $this->calc_products($account, $start_date, $end_date);

        $inv_data_query = "select count(id) as count,sum(debit) as debit,sum(credit) as credit from invoice_details where accountid=".$account['id']." AND created_date >='".$start_date."' AND created_date <= '".$end_date."'  AND invoiceid=0 AND item_type != 'FREECALL'";
//echo $inv_data_query;         
        $invoice_data = $this->db->query($inv_data_query);
        if ($invoice_data->num_rows > 0) {
            $invoice_data = $invoice_data->result_array();
            foreach ($invoice_data as $data_value) {
                if ($data_value['count'] > 0) {
                    $sub_total = ($data_value['debit'] - $data_value['credit']);
                    $sub_total = round($sub_total, self::$global_config['system_config']['decimalpoints']);
                    return $sub_total;
                }
            }
        }
        return "0";
    }

    // Calculate product for account
    function calc_products($account, $start_date, $end_date) {
		$this->db->select('products.price');
		$this->db->select('products.name');
		$this->db->select('rent.count');
		$this->db->select('rent.payments');
		$this->db->select('rent.leftpayments');
		$this->db->select('rent.payment_type');
		$this->db->select('rent.id');
		$this->db->from('rent_products as rent');
		$this->db->join('products', 'products.id = rent.product_id', 'left');
		$this->db->where('rent.delete_at IS NULL');
		$this->db->where('products.delete_at IS NULL');
		$this->db->where('rent.user_id', $account['id']);
//		$this->db->where("DATE(rent.last_payment) <=", date('Y-m-d', strtotime('- 1 month')));

		$product_data = $this->db->get();

		$product_data = $product_data->result_array();
		if ($product_data) {
			foreach ($product_data as $product) {

				$update = array(
					'last_payment' => date('Y-m-d H:i:s'),
					'payments' => $product['payments'] + 1
				);

				if ($product['payment_type'] == 2) {
					if ($product['leftpayments']) {
						$update['leftpayments'] = $product['leftpayments'] - 1;

						if ($update['leftpayments'] == 0) {
							$this->db->where('id', $product['id']);
							$this->db->update('rent_products', array(
								'delete_at' => date('Y-m-d H:i:s')
							));
						}
					}
				}

				$this->db->where('id', $product['id']);
				$this->db->update("rent_products", $update);

				$formatted_start_date = date('Y-m-d', strtotime($start_date));
				$formatted_end_date = date('Y-m-d', strtotime($end_date));

				$this->db->insert("invoice_details", array(
					"accountid" => $account['id'],
					"reseller_id" => $account['reseller_id'],
					"item_id" => "0",
					"description" => "{$product['name']}",
					"debit" => round($product['price'], self::$global_config['system_config']['decimal_points']) * $product['count'],
					"item_type" => "PRODUCT",
					"count" => $product['count'],
					"created_date" => $end_date
				));

				if ($product['payment_type'] == 1) {
					$this->db->where('id', $product['id']);
					$this->db->update('rent_products', array(
						'delete_at' => date('Y-m-d H:i:s')
					));
				}
			}
		}
    }

    //Change Order of arguements
    function update_cdrs_data($accountid, $invoiceid, $start_date = "", $end_date = "") {
        $inv_data_query = "update invoice_details SET invoiceid = '".$invoiceid."' where accountid=".$accountid;
        $inv_data_query .= " AND created_date >='".$start_date."' AND created_date <= '".$end_date."'  AND invoiceid=0 AND item_type !='PAYMENT'";
        $this->db->query($inv_data_query);
        return true;
    }

    /**
     * @param string $last_invoice_ID
     */
    function create_invoice($account, $from_date, $to_date, $last_invoice_ID, $INVprefix, $invoiceconf, $invoice_type = '') {
        //$due_date = gmdate("Y-m-d H:i:s",strtotime($to_date." +".$invoiceconf['interval']." days"));
        if ($invoiceconf['interval'] > 0) {
            $due_date = gmdate("Y-m-d H:i:s", strtotime(gmdate("Y-m-d H:i:s")." +".$invoiceconf['interval']." days"));
        } else {
            $due_date = gmdate("Y-m-d H:i:s", strtotime(gmdate("Y-m-d H:i:s")." +7 days"));
        }
        // echo "due daye-------".$due_date.'----------'.$to_date.'------------> Invoice interval'.$invoiceconf['interval']; 
        $balance = ($account['credit_limit'] - $account['balance']);
        $automatic_flag = self::$global_config['system_config']['automatic_invoice'];
    if ($automatic_flag == 1) {
	        $invoice_data = array("accountid" => $account['id'], "invoice_prefix" => $INVprefix, "invoiceid" => $last_invoice_ID, "reseller_id" =>
            $account['reseller_id'], "invoice_date" => gmdate("Y-m-d H:i:s"), "from_date" => $from_date, "to_date" => $to_date, "due_date" => $due_date, "status" => 1, "amount" => "0.00", "balance" => $balance);
	} else {
	        $invoice_data = array("accountid" => $account['id'], "invoice_prefix" => $INVprefix, "invoiceid" => $last_invoice_ID, "reseller_id" =>
            $account['reseller_id'], "invoice_date" => gmdate("Y-m-d H:i:s"), "from_date" => $from_date, "to_date" => $to_date, "due_date" => $due_date, "status" => 1, "amount" => "0.00", "balance" => $balance, "confirm" => 1);
	}
	if ($invoice_type === 'zero') {
		$invoice_data['confirm'] = 0;
		$invoice_data['generate_type'] = 2;
    }
        // echo "<pre>"; print_r($invoice_data); exit;
        $this->db->insert("invoices", $invoice_data);
        $invoiceid = $this->db->insert_id();
	if ($automatic_flag == 0 && $invoice_type !== 'zero') {
            $this->download_invoice($invoiceid, $account, $invoiceconf);
			$this->download_cdr($invoiceid, $account, $invoiceconf);
	}
        return $invoiceid;
    }

    function set_invoice_total($invoiceid, $accountid) {
        $query = $this->db_model->getSelect("SUM(debit) as total", "invoice_details", array("invoiceid" => $invoiceid, "item_type <>" => "FREECALL"));
        $query = $query->result_array();
        $sub_total = $query["0"]["total"];
        $updateArr = array("amount" => $sub_total);
        $this->db->where(array("id" => $invoiceid));
        $this->db->update("invoices", $updateArr);

        // Disable setting of balance in 0
        /*
        $updateArr = array("balance" => "0.00");
        $this->db->where(array("id" => $accountid));
        $this->db->update("accounts", $updateArr);
        */

        // Set to zero counters for account
		$this->db->where(array('accountid' => $accountid));
		$this->db->delete('counters');

        return true;
    }

    function download_invoice($invoiceid, $accountdata, $invoice_conf) {
        $invoicedata = $this->db_model->getSelect("*", "invoices", array("id" => $invoiceid));
        $invoicedata = $invoicedata->result_array();
        $invoicedata = $invoicedata[0];
        $FilePath = FCPATH."invoices/".$accountdata["id"].'/'.$invoicedata['invoice_prefix']."".$invoicedata['invoiceid']."_invoice.pdf";
        $Filenm = $invoicedata['invoice_prefix']."_".$invoicedata['invoiceid']."_invoice.pdf";
//        $this->common->get_invoice_template($invoicedata, $accountdata, false);
		$this->load->module("invoices/templates");
		$this->templates->get_invoice_template($invoicedata,$accountdata, false);
        if ($invoice_conf['invoice_notification']) {
            $this->send_email_notification($FilePath, $Filenm, $accountdata, $invoice_conf, $invoicedata);
        }
    }

    /**
     * @param string $FilePath
     * @param string $Filenm
     */
    function send_email_notification($FilePath, $Filenm, $AccountData, $invoice_conf, $invData, $template_name = 'email_new_invoice') {
        $TemplateData = array();
        $where = array('name' => $template_name);
        $EmailTemplate = $this->db_model->getSelect("*", "default_templates", $where);
        foreach ($EmailTemplate->result_array() as $TemplateVal) {
            $TemplateData = $TemplateVal;
            $TemplateData['subject'] = str_replace('#NAME#', $AccountData['first_name']." ".$AccountData['last_name'], $TemplateData['subject']);
            $TemplateData['subject'] = str_replace('#INVOICE_NUMBER#', $invData['invoice_prefix'].$invData['invoiceid'], $TemplateData['subject']);
            $TemplateData['template'] = str_replace('#NAME#', $AccountData['first_name']." ".$AccountData['last_name'], $TemplateData['template']);
            $TemplateData['template'] = str_replace('#INVOICE_NUMBER#', $invData['invoice_prefix'].$invData['invoiceid'], $TemplateData['template']);
            $TemplateData['template'] = str_replace('#AMOUNT#', $invData['amount'], $TemplateData['template']);

            $TemplateData['template'] = str_replace("#COMPANY_EMAIL#", $invoice_conf['emailaddress'], $TemplateData['template']);
            $TemplateData['template'] = str_replace("#COMPANY_NAME#", $invoice_conf['company_name'], $TemplateData['template']);
            $TemplateData['template'] = str_replace("#COMPANY_WEBSITE#", $invoice_conf['website'], $TemplateData['template']);
            $TemplateData['template'] = str_replace("#INVOICE_DATE#", $invData['invoice_date'], $TemplateData['template']);
            $TemplateData['template'] = str_replace("#DUE_DATE#", $invData['due_date'], $TemplateData['template']);
        }
        $dir_path = getcwd()."/attachments/";
        $path = $dir_path.$Filenm;
        $command = "cp ".$FilePath." ".$path;
        exec($command);
        $email_array = array('accountid' => $AccountData['id'],
            'subject' => $TemplateData['subject'],
            'body' => $TemplateData['template'],
            'from' => $invoice_conf['emailaddress'],
            'to' => $AccountData['email'],
            'status' => "1",
            'attachment' => $Filenm,
            'template' => '');
        //echo "<pre>"; print_r($TemplateData); exit;
        $this->db->insert("mail_details", $email_array);
    }

	function download_cdr($invoiceid, $accountdata, $invoice_conf) {
		$invoicedata = $this->db_model->getSelect("*", "invoices", array("id" => $invoiceid));
		$invoicedata = $invoicedata->result_array();
		$invoicedata = $invoicedata[0];
		$currency_id=$accountdata['currency_id'];
		$currency=$this->common->get_field_name('currency', 'currency', $currency_id);
		$this->db->select('count(*) as count,sum(billseconds) as billseconds,sum(debit) as total_debit,sum(cost) as total_cost,group_concat(distinct(pricelist_id)) as pricelist_ids,group_concat(distinct(trunk_id)) as trunk_ids,group_concat(distinct(accountid)) as accounts_ids');
		$this->db->where(array(
			'accountid' => $accountdata['id'],
			'callstart >= ' => $invoicedata['from_date'],
			'callstart <=' => $invoicedata['to_date']
		));
		$this->db->where_in('type', array('0','3'));
		$count_res = $this->db->get('cdrs');
		$count_all = (array) $count_res->first_row();
		if ($count_all['count'] > 0) {
			//Initialization of Rategroup and Trunk Array
			$pricelist_arr = array();
			$trunk_arr = array();
			$account_arr=array();
			$this->db->select('callstart,callerid,callednum,pattern,notes,billseconds,disposition,debit,cost,accountid,pricelist_id,calltype,is_recording,trunk_id,uniqueid');
			$query = $this->db->get('cdrs');

			//Get Decimal points,system currency and user currency.
			$currency_info = $this->common->get_currency_info($accountdata);
			$show_seconds = 'minutes';
			$where = "id IN (" . $count_all['pricelist_ids'] . ")";
			$this->db->where($where);
			$this->db->select('id,name');
			$pricelist_res = $this->db->get('pricelists');
			$pricelist_res = $pricelist_res->result_array();
			foreach ($pricelist_res as $value) {
				$pricelist_arr[$value['id']] = $value['name'];
			}
			$where = "id IN (" . $count_all['accounts_ids'] . ")";
			$this->db->where($where);
			$this->db->select('id,number,first_name,last_name');
			$account_res = $this->db->get('accounts');
			foreach ($account_res->result_array() as $value) {
				$account_arr[$value['id']] =$value['first_name'] . " " . $value['last_name'] . ' (' . $value['number'] . ')';
			}

			if($accountdata['type'] !=1){
				$customer_array[] = array("Date", "CallerID", "Called Number", "Code", "Destination", "Duration", "Debit($currency)", "Cost($currency)", "Disposition", "Account", "Trunk", "Rate Group", "Call Type");
				$where = "id IN (" . $count_all['trunk_ids'] . ")";
				$this->db->where($where);
				$this->db->select('id,name');
				$trunk_res = $this->db->get('trunks');
				$trunk_res = $trunk_res->result_array();
				foreach ($trunk_res as $value) {
					$trunk_arr[$value['id']] = $value['name'];
				}
				foreach ($query->result_array() as $value) {
					$duration = ($show_seconds == 'minutes') ? ($value['billseconds'] > 0 ) ?
						floor($value['billseconds'] / 60) . ":" . sprintf('%02d', $value['billseconds'] % 60) : "00:00"  : $value['billseconds'];
					$account=isset($account_arr[$value['accountid']]) ? $account_arr[$value['accountid']] : 'Anonymous';
					$customer_array[] = array(
						$this->common->convert_GMT_to('', '', $value['callstart']),
						$value['callerid'],
						$value['callednum'],
						filter_var($value['pattern'], FILTER_SANITIZE_NUMBER_INT),
						$value['notes'],
						$duration,
						$this->common->calculate_currency_manually($currency_info, $value['debit'],false,false),
						$this->common->calculate_currency_manually($currency_info, $value['cost'],false,false),
						$value['disposition'],
						$account,
						isset($trunk_arr[$value['trunk_id']]) ? $trunk_arr[$value['trunk_id']] : '',
						isset($pricelist_arr[$value['pricelist_id']]) ? $pricelist_arr[$value['pricelist_id']] : '',
						$value['calltype'],
					);
				}
				$duration = ($show_seconds == 'minutes') ? ($count_all['billseconds'] > 0 ) ?
					floor($count_all['billseconds'] / 60) . ":" . sprintf('%02d', $count_all['billseconds'] % 60) : "00:00"  : $count_all['billseconds'];
				$customer_array[] = array("Grand Total",
					"",
					"",
					"",
					"",
					$duration,
					$this->common->calculate_currency_manually($currency_info, $count_all['total_debit'],false,false),
					$this->common->calculate_currency_manually($currency_info, $count_all['total_cost'],false,false),
					"",
					"",
					"",
					"",
					"",
					"",
					"",
					"",
				);
			}else{
				$customer_array[] = array("Date", "CallerID", "Called Number", "Code", "Destination", "Duration", "Debit($currency)", "Cost($currency)", "Disposition", "Account","Rate Group", "Call Type");
				foreach ($query->result_array() as $value) {
					$duration = ($show_seconds == 'minutes') ? ($value['billseconds'] > 0 ) ?
						floor($value['billseconds'] / 60) . ":" . sprintf('%02d', $value['billseconds'] % 60) : "00:00"  : $value['billseconds'];
					$account=isset($account_arr[$value['accountid']]) ? $account_arr[$value['accountid']] : 'Anonymous';
					$customer_array[] = array(
						$this->common->convert_GMT_to('', '', $value['callstart']),
						$value['callerid'],
						$value['callednum'],
						filter_var($value['pattern'], FILTER_SANITIZE_NUMBER_INT),
						$value['notes'],
						$duration,
						$this->common->calculate_currency_manually($currency_info, $value['debit'],false,false),
						$this->common->calculate_currency_manually($currency_info, $value['cost'],false,false),
						$value['disposition'],
						$account,
						isset($pricelist_arr[$value['pricelist_id']]) ? $pricelist_arr[$value['pricelist_id']] : '',
						$value['calltype'],
					);
				}
				$duration = ($show_seconds == 'minutes') ? ($count_all['billseconds'] > 0 ) ?
					floor($count_all['billseconds'] / 60) . ":" . sprintf('%02d', $count_all['billseconds'] % 60) : "00:00"  : $count_all['billseconds'];
				$customer_array[] = array("Grand Total",
					"",
					"",
					"",
					"",
					$duration,
					$this->common->calculate_currency_manually($currency_info, $count_all['total_debit'],false,false),
					$this->common->calculate_currency_manually($currency_info, $count_all['total_cost'],false,false),
					"",
					"",
					"",
					"",
					"",
					"",
					"",
				);
			}
		}
		$this->load->helper('csv');
		if(isset($customer_array)){
			$csv_data = array_to_csv($customer_array);
		} else{
			$customer_array[] = array("Date", "CallerID", "Called Number", "Code", "Destination", "Duration", "Debit($currency)", "Cost($currency)", "Disposition", "Account","Rate Group", "Call Type");
			$csv_data = array_to_csv($customer_array);
		}
		$filename = $invoicedata['invoice_prefix'].$invoicedata['invoiceid'] . '_CDR'. '.csv';
		$filepath = FCPATH."invoices/".$accountdata["id"].'/'.$filename;
		file_put_contents ( $filepath, $csv_data);
		if ($invoice_conf['invoice_notification']) {
			$this->send_email_notification($filepath, $filename, $accountdata, $invoice_conf, $invoicedata, 'email_detail_cdr');
		}
	}

}

?>




