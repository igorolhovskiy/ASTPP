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

require_once(APPPATH . "libraries/mpay24-php/bootstrap.php");
use Mpay24\Mpay24;
use Mpay24\Mpay24Order; //if you are using paymentPage
use Mpay24\Mpay24Config;

class Payment extends MX_Controller {

  function Payment() {
	  parent::__construct();
	  $this->load->helper('template_inheritance');
	  $this->load->library('session');
	  $this->load->library('encrypt');
	  $this->load->helper('form');

  }

  function index(){
	  $account_data = $this->session->userdata("accountinfo");
	  $data["accountid"] = $account_data["id"];
	  $data["accountid"] = $account_data["id"];
	  $data["page_title"] = "Recharge";      
      
	   $system_config = common_model::$global_config['system_config'];
	   if($system_config["paypal_mode"]==0){
		   $data["paypal_url"] = $system_config["paypal_url"];
		   $data["paypal_email_id"] = $system_config["paypal_id"];
	   }else{
		   $data["paypal_url"] = $system_config["paypal_sandbox_url"];
		   $data["paypal_email_id"] = $system_config["paypal_sandbox_id"];
	   }
	   $data["paypal_tax"] = $system_config["paypal_tax"];

	   $data["mpay24_status"] = $system_config["mpay24_status"];

	   $data["from_currency"] = $this->common->get_field_name('currency', 'currency', $account_data["currency_id"]);
	   $data["to_currency"] = Common_model::$global_config['system_config']['base_currency'];
	   $this->load->view("user_payment",$data);
  }

  function mpay24() {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->session->userdata('user_login') == FALSE) {
	  redirect(base_url() . 'user');
	}

    $system_config = common_model::$global_config['system_config'];
	if ($system_config["mpay24_mode"] === '1') {
		$is_test_mode = true;
	} else {
        $is_test_mode = false;
	}

	$config = new Mpay24Config();
	$config->setMerchantID($system_config["mpay24_merchant_id"]);
	$config->setSoapPassword($system_config["mpay24_soap_password"]);
	$config->useTestSystem($is_test_mode);
	$config->setDebug(true);

	$config->setEnableCurlLog(true);
	$config->setLogPath(APPPATH . 'logs');

	$mpay24 = new Mpay24($config);
	$mdxi = new Mpay24Order();

	$amount = $this->input->post('amount');
	$mdxi->Order->Tid = $this->getReceiptId($amount);
	$mdxi->Order->Price = $amount;

	$mdxi->Order->URL->Success      = base_url() . "login/mpay24_success_response/";
	$mdxi->Order->URL->Error        = base_url() . "login/mpay24_error_response/";
	$mdxi->Order->URL->Confirmation = base_url() . "login/mpay24_confirmation_response/";

	$paymentPageURL = $mpay24->paymentPage($mdxi)->getLocation(); // redirect location to the payment page
  	if (!empty($paymentPageURL)) {
        header('Location: '.$paymentPageURL);
	} else {
        echo $mpay24->paymentPage($mdxi)->getStatus() . "<br>";
        $this->session->set_flashdata('astpp_notification', $mpay24->paymentPage($mdxi)->getReturnCode());
        redirect(base_url() . 'user/user/');
	}
  }

  function convert_amount($amount){
	   $amount = $this->common_model->add_calculate_currency($amount,"","",true,false);
	   echo number_format((float)$amount,2);
  }

  function getReceiptId($amount) {
	  $account_data = $this->session->userdata("accountinfo");
	  $reseller_id=$account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : 0;
	  $where="accountid IN ('".$reseller_id."','1')";
	  $this->db->where($where);
	  $this->db->select('*');
	  $this->db->order_by('accountid', 'desc');
	  $this->db->limit(1);
	  $invoiceconf = $this->db->get('invoice_conf');
	  $invoiceconf = (array)$invoiceconf->first_row();
	  $invoice_prefix=$invoiceconf['invoice_prefix'];
	  $last_invoice_ID = $this->getLastInvoiceId();
	  $due_date = gmdate("Y-m-d H:i:s",strtotime(gmdate("Y-m-d H:i:s")." +".$invoiceconf['interval']." days"));
	  $invoice_id=$this->generate_receipt($account_data['id'],$amount,$account_data, $last_invoice_ID + 1 ,$invoice_prefix,$due_date);
	  $date = date('Y-m-d H:i:s');
	  $details_insert=array(
		  'created_date'=>$date,
		  'credit'=>$amount,
		  'debit'=>'-',
		  'accountid'=>$account_data["id"],
		  'reseller_id'=>$account_data['reseller_id'],
		  'invoiceid'=>$invoice_id,
		  'description'=>"Payment Made by mPay24 on date:-".$date,
		  'item_type'=>'PAYMENT'
	  );
	  $this->db->insert("invoice_details", $details_insert);
	  return $invoice_id;
  }

	function generate_receipt($accountid,$amount,$accountinfo,$last_invoice_ID,$invoice_prefix,$due_date){
		$invoice_data = array("accountid"=>$accountid,
				"invoice_prefix" =>$invoice_prefix,
				"invoiceid"=>'0000'.$last_invoice_ID,
				"reseller_id"=>$accountinfo['reseller_id'],
				"invoice_date"=>gmdate("Y-m-d H:i:s"),
				"from_date"=>  gmdate("Y-m-d H:i:s"),
				"to_date"=>gmdate("Y-m-d H:i:s"),
				"due_date"=>$due_date,
				"status"=>1,
				"balance"=>$accountinfo['balance'],
				"amount"=>$amount,
				"type"=>'R',
				"confirm"=>'0');
		$this->db->insert("invoices",$invoice_data);
		$invoiceid = $this->db->insert_id();
		return  $invoiceid;
	}

  protected function getLastInvoiceId() {
	  $this->db->select('invoiceid');
	  $this->db->order_by('id','desc');
	  $this->db->limit(1);
	  $last_invoice_result=(array)$this->db->get('invoices')->first_row();
	  $last_invoice_ID=isset($last_invoice_result['invoiceid'] ) && $last_invoice_result['invoiceid'] > 0 ?$last_invoice_result['invoiceid'] : 1;
	  return $last_invoice_ID;
  }
}
?> 
