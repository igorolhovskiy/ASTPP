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
class Login extends MX_Controller {

	function Login() {
		parent::__construct();
		$this->load->helper('form');
		$this->load->library('astpp/permission');
		$this->load->library('encrypt');        
		$this->load->model('Auth_model');
		$this->load->model('db_model');
	}
    
	function set_lang_global($post=false){ 
	if(!is_array($post)){
		$str=trim($post);
		$new_arr[$str]=$str;
		$post=$new_arr;
	}
	if(isset($post['fr_FR'])){
	 $language=$post['fr_FR'];
	 $this->session->set_userdata('user_language', $language);
	}
	if(isset($post['es_ES'])){
	 $language=$post['es_ES'];
	 $this->session->set_userdata('user_language', $language);         
	}       
	if(isset($post['en_EN'])){
	 $language=$post['en_EN'];
	 $this->session->unset_userdata('user_language',$language);
	} 
	$this->locale->set_lang();
	return true;
	}

	function index() {
		if ($this->session->userdata('user_login') == FALSE) {
			if (!empty($_POST) && trim($_POST['username']) != '' && trim($_POST['password']) != '') {
			$_POST['password']=$this->common->encode($_POST['password']);
				$user_valid = $this->Auth_model->verify_login($_POST['username'], $_POST['password']);

				if ($user_valid == 1) {
					$this->session->set_userdata('user_login', TRUE);
			$where = "number = '".$this->db->escape_str($_POST['username'])."' OR email = '".$this->db->escape_str($_POST['username'])."'";
					$result = $this->db_model->getSelect("*", "accounts",$where);
					$result = $result->result_array();
					$result = $result[0];
					 $logintype=$result['type']== -1 ? 2: $result['type'];
					$this->session->set_userdata('logintype', $logintype);
					$this->session->set_userdata('userlevel_logintype', $result['type']);
					$this->session->set_userdata('username', $_POST['username']);
					$this->session->set_userdata('accountinfo', $result);
		/*
		*
		* Purpose : Display logo based on domain name
		*
		*/
		$this->db->select("*");
		if ($result['type'] == '2' || $result['type'] == '-1') {
			$this->db->where(array("accountid"=>$result["id"]));
		} else if ($result['type'] == '0') {
			if ($result['reseller_id'] == 0) {
				$this->db->where(array("accountid"=>"1"));
			} else {
				$this->db->where(array("accountid"=>$result["reseller_id"]));
			}
		} else if ($result['type'] == '1') {
			if ($result['reseller_id'] == 0) {
				$result_invoice = $this->common->get_field_name('id', 'invoice_conf', array("accountid" => $result['id']));
				
				if ($result_invoice) {
					$this->db->where(array("accountid"=>$result["id"]));
				} else {
					$this->db->where(array("accountid"=>"1"));
				}
				
			} else {
				$result_invoice = $this->common->get_field_name('id', 'invoice_conf', array("accountid" => $result['reseller_id']));
				if ($result_invoice) {
					$this->db->where(array("accountid"=>$result["reseller_id"]));
				} else {
					$this->db->where(array("accountid"=>"1"));
				}
			}
		} else {
			$this->db->where(array("accountid"=>"1"));
		}
		$res = $this->db->get("invoice_conf");
		$logo_arr = $res->result();
	$data['user_logo'] = (isset($logo_arr[0]->logo) && $logo_arr[0]->logo != "") ? $logo_arr[0]->accountid."_".$logo_arr[0]->logo : "1_consertis_small.jpg";
		$data['user_header'] = (isset($logo_arr[0]->website_title) && $logo_arr[0]->website_title != "") ? $logo_arr[0]->website_title : "ASTPP - Open Source Voip Billing Solution";
		$data['user_footer'] = (isset($logo_arr[0]->website_footer) && $logo_arr[0]->website_footer != "") ? $logo_arr[0]->website_footer : "Inextrix Technologies Pvt. Ltd All Rights Reserved.";
		$this->session->set_userdata('user_logo', $data['user_logo']);
		$this->session->set_userdata('user_header', $data['user_header']);
		$this->session->set_userdata('user_footer', $data['user_footer']);
		//                      echo $data['user_header'];exit;
		/***************************************************************************************/
					if ($result['type'] == 0 || $result['type'] == 1) {
						$menu_list = $this->permission->get_module_access($result['type']);
						$this->session->set_userdata('mode_cur', 'user');
						if($result['type'] == 1){
							redirect(base_url() . 'dashboard/');
						}else{
							redirect(base_url() . 'user/user/');
						}
					} else {
						$menu_list = $this->permission->get_module_access($result['type']);
						$this->session->set_userdata('mode_cur', 'admin');
						redirect(base_url() . 'dashboard/');
					}
				} else {
		   
						$data['astpp_notification'] = "Login Failed! Try Again..";
				}
			}
	/*
	* Purpose : Display logo based on domain name
	*/
	$this->db->select("*");
	$this->db->where(array("domain"=>$_SERVER["HTTP_HOST"]));
	$res = $this->db->get("invoice_conf");
	$logo_arr = $res->result();
	$data['user_logo'] = (isset($logo_arr[0]->logo) && $logo_arr[0]->logo != "") ? $logo_arr[0]->accountid."_".$logo_arr[0]->logo : "1_consertis_small.jpg";
	$data['website_header'] = (isset($logo_arr[0]->website_title) && $logo_arr[0]->website_title != "") ? $logo_arr[0]->website_title : "ASTPP - Open Source Voip Billing Solution";
	$data['website_footer'] = (isset($logo_arr[0]->website_footer) && $logo_arr[0]->website_footer != "") ? $logo_arr[0]->website_footer : "Inextrix Technologies Pvt. Ltd All Rights Reserved.";
	$this->session->set_userdata('user_logo', $data['user_logo']);
	$this->session->set_userdata('user_header', $data['website_header']);
	$this->session->set_userdata('user_footer', $data['website_footer']);
	//              echo $data['user_logo'];exit;
	/***************************************************************************************/
			$this->session->set_userdata('user_login', FALSE);
			$data['app_name'] = 'ASTPP - Open Source Billing Solution';
			$this->load->view('view_login', $data);
		}else {
	/*
	*
	* Purpose : Display logo based on domain name
	*
	*/
		  $this->db->select("*");
		  $this->db->where(array("domain"=>$_SERVER["HTTP_HOST"]));
		  $res = $this->db->get("invoice_conf");
		  $logo_arr = $res->result();
//print_r( $logo_arr );exit;

	$data['user_logo'] = (isset($logo_arr[0]->logo) && $logo_arr[0]->logo != "") ? $logo_arr[0]->accountid."_".$logo_arr[0]->logo : "1_consertis_small.jpg";
		 $data['user_header'] = (isset($logo_arr[0]->website_title) && $logo_arr[0]->website_title != "") ? $logo_arr[0]->website_title : "ASTPP - Open Source Voip Billing Solution";
 $data['user_footer'] = (isset($logo_arr[0]->website_footer) && $logo_arr[0]->website_footer != "") ? $logo_arr[0]->website_footer : "Inextrix Technologies Pvt. Ltd All Rights Reserved.";
		
		$this->session->set_userdata('user_logo', $data['user_logo']);
		 $this->session->set_userdata('user_header', $data['user_header']);
		$this->session->set_userdata('user_footer', $data['user_footer']);
	//              echo $data['user_logo'];exit;
/***************************************************************************************/
		if ($this->session->userdata('logintype') == '2') {
		redirect(base_url() . 'dashboard/');
		} else {
		redirect(base_url().'user/user/');
		}
		}
	}

	function logout() {
		$this->session->sess_destroy();
		redirect(base_url());
	}
  function paypal_response(){
	  if(count($_POST)>0)
	  {
		$response_arr=$_POST;
		$fp=fopen("/var/log/astpp/astpp_payment.log","w+");
		$date = date("Y-m-d H:i:s");
		fwrite($fp,"====================".$date."===============================\n");
		foreach($response_arr as $key => $value){	  
			fwrite($fp,$key.":::>".$value."\n");
		}
		$payment_check = $this->db_model->countQuery("txn_id", "payments", array("txn_id" => $response_arr['txn_id']));
		if( ($response_arr["payment_status"] == "Pending" || $response_arr["payment_status"] == "Complete" || $response_arr["payment_status"] == "Completed" ) && $payment_check == 0){

			$paypal_tax = (array)$this->db->get_where("system", array("name" => "paypal_tax","group_title"=>"paypal"))->first_row();
			$paypal_tax =$paypal_tax['value'];
			$balance_amt = $actual_amount = $response_arr["custom"];
			$paypal_fee = (array)$this->db->get_where("system", array("name" => "paypal_fee","group_title"=>"paypal"))->first_row();
			$paypal_fee = $paypal_fee['value'];
			$paypalfee = ($paypal_fee == 0)?'0':$response_arr["mc_gross"];
			$account_data = (array)$this->db->get_where("accounts", array("id" => $response_arr["item_number"]))->first_row();
			$currency = (array)$this->db->get_where('currency', array("id"=>$account_data["currency_id"]))->first_row();
			$date = date('Y-m-d H:i:s');
			$payment_trans_array = array("accountid"=>$response_arr["item_number"],
							"amount"=>$response_arr["payment_gross"],
									"tax"=>"1",
									"payment_method"=>"Paypal",
									"actual_amount"=>$actual_amount,
									"paypal_fee"=>$paypalfee,
							"user_currency"=>$currency["currency"],
							"currency_rate"=>$currency["currencyrate"],
							"transaction_details"=>json_encode($response_arr),
							"date"=>$date);
			 $paymentid=$this->db->insert('payment_transaction',$payment_trans_array);
			 $parent_id =$account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : '-1';
		 $payment_arr = array("accountid"=> $response_arr["item_number"],
		 		"payment_mode"=>"1","credit"=>$balance_amt,
				"type"=>"PAYPAL",
				"payment_by"=>$parent_id,
				"notes"=>"Payment Made by Paypal on date:-".$date,
				"paypalid"=>$paymentid,
				"txn_id"=>$response_arr["txn_id"],
				'payment_date'=>gmdate('Y-m-d H:i:s',strtotime($response_arr['payment_date'])));
		 $this->db->insert('payments', $payment_arr);
		 $this->db->select('invoiceid');
		 $this->db->order_by('id','desc');
		 $this->db->limit(1);
 		 $last_invoice_result=(array)$this->db->get('invoices')->first_row();
 		 $last_invoice_ID=isset($last_invoice_result['invoiceid'] ) && $last_invoice_result['invoiceid'] > 0 ?$last_invoice_result['invoiceid'] : 1;
 		 $reseller_id=$account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : 0;
		 $where="accountid IN ('".$reseller_id."','1')";
		 $this->db->where($where);
		 $this->db->select('*');
		 $this->db->order_by('accountid', 'desc');
		 $this->db->limit(1);
			 $invoiceconf = $this->db->get('invoice_conf');
			 $invoiceconf = (array)$invoiceconf->first_row();
		 $invoice_prefix=$invoiceconf['invoice_prefix'];

		 $due_date = gmdate("Y-m-d H:i:s",strtotime(gmdate("Y-m-d H:i:s")." +".$invoiceconf['interval']." days"));  
		 $invoice_id=$this->generate_receipt($account_data['id'],$balance_amt,$account_data,$last_invoice_ID+1,$invoice_prefix,$due_date);
		 $details_insert=array(
			'created_date'=>$date,
			'credit'=>$balance_amt,
			'debit'=>'-',
			'accountid'=>$account_data["id"],
			'reseller_id'=>$account_data['reseller_id'],
			'invoiceid'=>$invoice_id,
			'description'=>"Payment Made by Paypal on date:-".$date,
			'item_type'=>'PAYMENT',
	  			'before_balance'=>$account_data['balance'],
			'after_balance'=>$account_data['balance']+$balance_amt,
			);
		$this->db->insert("invoice_details", $details_insert); 
		$this->db_model->update_balance($balance_amt,$account_data["id"],"credit");            
		if($parent_id > 0){
			  $reseller_ids=$this->common->get_parent_info($parent_id,0);
			  $reseller_ids=rtrim($reseller_ids,",");
			  $reseller_arr=explode(",",$reseller_ids);
			  if(!empty($reseller_arr)){
			foreach($reseller_arr as $key=>$reseller_id){
			  $account_data = (array)$this->db->get_where("accounts", array("id" => $reseller_id))->first_row();
			  $this->db->select('invoiceid');
				  $this->db->order_by('id','desc');
				  $this->db->limit(1);
 					  $last_invoice_result=(array)$this->db->get('invoices')->first_row();
 					  $last_invoice_ID=$last_invoice_result['invoiceid'];
 		 		  $reseller_id=$account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : 0;
					  $where="accountid IN ('".$reseller_id."','1')";
					  $this->db->where($where);
				  $this->db->select('*');
				  $this->db->order_by('accountid', 'desc');
				  $this->db->limit(1);
				  $invoiceconf = $this->db->get('invoice_conf');
				  $invoiceconf = (array)$invoiceconf->first_row();
					  $invoice_prefix=$invoiceconf['invoice_prefix'];
		 		  $due_date = gmdate("Y-m-d H:i:s",strtotime(gmdate("Y-m-d H:i:s")." +".$invoiceconf['interval']." days"));
		 		  $invoice_id=$this->generate_receipt($account_data['id'],$balance_amt,$account_data,$last_invoice_ID+1,$invoice_prefix,$due_date);
			  $parent_id=$account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : -1;
			  $payment_arr = array("accountid"=> $account_data["id"],
		 					"payment_mode"=>"1",
		 					"credit"=>$balance_amt,
							"type"=>"PAYPAL",
							"payment_by"=>$parent_id,
							"notes"=>"Your account has been credited due to your customer account recharge done by paypal",
							"paypalid"=>$paymentid,
							"txn_id"=>$response_arr["txn_id"],
							'payment_date'=>gmdate('Y-m-d H:i:s',strtotime($response_arr['payment_date'])));
	 			  $this->db->insert('payments', $payment_arr);
			  $details_insert=array(
				'created_date'=>$date,
				'credit'=>$balance_amt,
				'debit'=>'-',
				'accountid'=>$account_data['id'],
				'reseller_id'=>$parent_id,
				'invoiceid'=>$invoice_id,
				'description'=>"Your account has been credited due to your customer account recharge done by paypal",
				'item_type'=>'PAYMENT',
		  	        'before_balance'=>$account_data['balance'],
				'after_balance'=>$account_data['balance']+$balance_amt,
			   );
		          $this->db->insert("invoice_details", $details_insert); 
	          	  $this->db_model->update_balance($balance_amt,$account_data["id"],"credit");  			         
			}
		      }
		}
		redirect(base_url() . 'user/user/');
        }
      }         
	redirect(base_url() . 'user/user/');
    }

    /**
     * @param integer $last_invoice_ID
     * @param string $due_date
     */
    function generate_receipt($accountid,$amount,$accountinfo,$last_invoice_ID,$invoice_prefix,$due_date){
		$invoice_data = array("accountid"=>$accountid,"invoice_prefix" =>$invoice_prefix,"invoiceid"=>'0000'.$last_invoice_ID,"reseller_id"=>$accountinfo['reseller_id'],"invoice_date"=>gmdate("Y-m-d H:i:s"),"from_date"=>  gmdate("Y-m-d H:i:s"),"to_date"=>gmdate("Y-m-d H:i:s"),"due_date"=>$due_date,"status"=>1,"balance"=>$accountinfo['balance'],"amount"=>$amount,"type"=>'R',"confirm"=>'1');            
        $this->db->insert("invoices",$invoice_data);
        $invoiceid = $this->db->insert_id();    
        return  $invoiceid;  
    }
    
    function get_language_text(){
   // echo '<pre>'; print_r($_POST); exit;
      echo gettext($_POST['display']); 
 	}  
    
}

?>
