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
class Templates extends MX_Controller {

    function Templates() {
        parent::__construct();
        $this->load->helper('template_inheritance');
        $this->load->library('session');
        $this->load->library("templates_form");
        $this->load->library('astpp/form');
        $this->load->model('templates_model');
        $this->load->dbutil();

        if (!defined('CRON') && $this->session->userdata('user_login') == FALSE)
            redirect(base_url() . '/astpp/login');
    }

    function help() {
        $data['page_title'] = 'Help template';
        $data['variables'] = $this->templates_model->help_variables();
        $this->load->view('help_template', $data);
    }

	function template_list() {
        $data['username'] = $this->session->userdata('user_name');
        $data['page_title'] = 'Invoice Templates';
        $data['search_flag'] = true;
        $this->session->set_userdata('advance_search', 0);
        $data['grid_fields'] = $this->templates_form->build_template_list();
        $data["grid_buttons"] = $this->templates_form->build_grid_buttons();
        $data['form_search'] = $this->form->build_serach_form($this->templates_form->get_template_search_form());
        $this->load->view('view_template_list', $data);
    }

    function template_list_json() {
        $json_data = array();
        $count_all = $this->templates_model->gettemplate_list(false, "", "");
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data = $paging_data["json_paging"];

        $query = $this->templates_model->gettemplate_list(true, $paging_data["paging"]["start"], $paging_data["paging"]["page_no"]);
        $grid_fields = json_decode($this->templates_form->build_template_list());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);

        echo json_encode($json_data);
    }

    function delete($id) {
        $templatename = $this->common->get_field_name('name','invoice_templates', $id);
        $using_query = $this->db_model->getSelect("count(*) as count", "accounts", array('invoice_template_id' => $id));
        $using_count = $using_query->row()->count;
        if ($id == 1 || $using_count > 0) {
            $this->session->set_flashdata('astpp_notification', $templatename. ' Template is using. Could not remove template!');
        } else {
            $this->templates_model->delete($id);
            $this->session->set_flashdata('astpp_notification', $templatename. ' Template removed successfully!');
        }
        redirect(base_url() . 'invoices/templates/template_list/');
    }

    function edit($edit_id = '') {
        $data['page_title'] = 'Edit Invoice template';
        $where = array('id' => $edit_id);
        $template = $this->db_model->getSelect("*", "invoice_templates", $where);
        foreach ($template->result_array() as $key => $value) {
            $edit_data = $value;
        }
        $data['form'] = $this->form->build_form($this->templates_form->get_template_form_fields(), $edit_data);
        $this->load->view('view_template_add_edit', $data);
    }

    function add() {
        $data['page_title'] = 'Add Invoice template';
        $where = array('id' => 1);
        $template = $this->db_model->getSelect("head_template, page1_template, page2_template, footer_template",
            "invoice_templates", $where);
        foreach ($template->result_array() as $key => $value) {
            $edit_data = $value;
        }
        $data['form'] = $this->form->build_form($this->templates_form->get_template_form_fields(), $edit_data);
        $this->load->view('view_template_add_edit', $data);
    }

    function template_save() {
        $data = $this->input->post();
        $data['form'] = $this->form->build_form($this->templates_form->get_template_form_fields(), $data);
        if ($data['id'] != '') {
            $data['page_title'] = 'Edit Template';
            if ($this->form_validation->run() == FALSE) {
                $data['validation_errors'] = validation_errors();
            } else {
                unset($data['form']);
                unset($data['page_title']);
                $this->templates_model->edit_template($data, $data['id']);
                $this->session->set_flashdata('astpp_errormsg', 'Template updated successfully!');
                redirect(base_url() . 'invoices/templates/template_list/');
                exit;
            }
        } else {
            $data['page_title'] = 'Termination Details';
            if ($this->form_validation->run() == FALSE) {
                $data['validation_errors'] = validation_errors();
            } else {
                unset($data['form']);
                unset($data['page_title']);
                $this->templates_model->add_template($data);
                $this->session->set_flashdata('astpp_errormsg', 'Template added successfully!');
                redirect(base_url() . 'invoices/templates/template_list/');
                exit;
            }
        }
        $this->load->view('view_template_add_edit', $data);
    }

    function preview_pdf() {
        $template = $this->input->post();
        $template_data = array();
        $template_data['logo'] = 'assets/images/logo_3.png';
        $template_data['strip'] = 'assets/images/bg_strip.png';

        $head_content = $this->update_template($template['head_template'], $template_data, true);
        $first_page_content = $this->update_template($template['page1_template'], $template_data, true);

		$second_page_content = $this->update_template($template['page2_template'], $template_data, true);
        $footer_content = $this->update_template($template['footer_template'], $template_data, true);
        $this->prepare_pdf($head_content, $first_page_content, $second_page_content, $footer_content);
        $content_pdf = $this->html2pdf->Output('', true);
//            header('Content-type: application/pdf');
        $response = (object)array(
            "success" => true,
            "contentPdf" => base64_encode($content_pdf)
        );
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    function select_template_data($invoicedata, $accountdata) {
        $template_data = array();
        $template_data['logo'] = 'assets/images/logo_3.png';
        $template_data['strip'] = 'assets/images/bg_strip.png';
        $template_data['invoices'] = $invoicedata;

        $template_data['invoiceid'] = $invoicedata['id'];
        $template_data['accountid'] = $accountdata['id'];

        $login_info = $this->session->userdata('accountinfo');
        $template_data['accountinfo'] = $login_info;

        $invoice_config = $this->db_model->getSelect("*", "invoice_conf", array(
            'accountid' => $login_info['id']
        ));
        $invoice_config = $invoice_config->result_array();
        $invoice_config_res = $invoice_config[0];
        $template_data['invoice_conf'] = $invoice_config_res;

        $accountdata["currency_id"] = $this->common->get_field_name('currency', 'currency', $accountdata["currency_id"]);
        $accountdata["country"]     = $this->common->get_field_name('country', 'countrycode', $accountdata["country_id"]);
        $data["to_currency"]        = Common_model::$global_config['system_config']['base_currency'];
        if ($login_info['type'] == -1) {
            $currency = $data["to_currency"];
        } elseif ($login_info['type'] == 1) {
            $accountdata["currency_id"] = $this->common->get_field_name('currency', 'currency', $login_info["currency_id"]);
            $currency                   = $accountdata["currency_id"];
        } else {
            $currency = $accountdata["currency_id"];
        }
        $template_data['currency'] = $currency;
        $template_data['accounts'] = $accountdata;

        $decimal_amount = Common_model::$global_config['system_config']['decimal_points'];

        $fromdate            = strtotime($invoicedata['from_date']);
        $from_date           = date("Y-m-d", $fromdate);
        $template_data['from_date'] = $from_date;
        $todate              = strtotime($invoicedata['to_date']);
        $to_date             = date("Y-m-d", $todate);
        $template_data['to_date'] = $to_date;
        $duedate             = strtotime($invoicedata['due_date']);
        $due_date            = date("Y-m-d", $duedate);
		$template_data['due_date'] = $due_date;
		$invoicedate             = strtotime($invoicedata['invoice_date']);
		$invoice_date            = date("Y-m-d", $invoicedate);
        $template_data['invoice_date'] = $invoice_date;
        $today               = new DateTime();
        $template_data['today'] = $today;

        /*** manual invoice **/
        $this->db->where('item_type <>', 'INVPAY');
        $invoice_details = $this->db_model->getSelect('*', 'invoice_details', array(
            "invoiceid" => $invoicedata['id'],
            'item_type <>' => 'TAX',
			'debit <> ' => '0'
        ));
        $invoice_details = $invoice_details->result_array();
        $total_sum       = 0;
        foreach ($invoice_details as &$charge_res) {
            if ($charge_res['item_type'] == 'DIDCHRG' || $charge_res['item_type'] == 'SUBCHRG' || $charge_res['item_type'] == 'manual_inv' || $charge_res['item_type'] == 'PRODUCT') {
                if ($charge_res['item_type'] == 'PRODUCT') {
                    $charge_res['item_type'] = 'Product Invoice';
                }
                if ($charge_res['item_type'] == 'manual_inv') {
                    $charge_res['item_type'] = 'Manual Invoice';
                }
                if ($charge_res['item_type'] == 'DIDCHRG') {
                    $charge_res['item_type'] = 'DID Charge';
                }
                if ($charge_res['item_type'] == 'SUBCHRG') {
                    $charge_res['item_type'] = 'Subscription Charge';
                }
            }
            $charge_res['debit'] = $this->common->currency_decimal($this->common_model->calculate_currency($charge_res['debit']));
            $total_sum += $charge_res['debit'];
        }
        unset($charge_res);
        $total_sum   = $this->common->currency_decimal($this->common_model->calculate_currency($total_sum));

        $template_data['invoice_details'] = $invoice_details;
        $template_data['total_sum'] = $total_sum;

        $invoice_tax = $this->db_model->getSelect('*', 'invoice_details', array(
            "invoiceid" => $invoicedata['id'],
            'item_type ' => 'TAX'
        ));
        $invoice_tax = $invoice_tax->result_array();
        $total_vat = 0;
        foreach ($invoice_tax as &$charge_res) {
            $total_vat += $charge_res['debit'];
            $charge_res['total_vat'] = $this->common->currency_decimal($this->common_model->calculate_currency($total_vat));
        }
        unset($charge_res);

        $total_vat = $this->common->currency_decimal($this->common_model->calculate_currency($total_vat));
        $sub_total = $total_sum + $total_vat;
        $template_data['invoice_details_tax'] = $invoice_tax;
        $template_data['total_vat'] = $total_vat;
        $template_data['sub_total'] = $this->common->currency_decimal($sub_total);

        $sec_from_date = (int) strtotime($from_date);
        $sec_today = (int) strtotime($today->format('Y-m-d'));
        if ((int) $invoicedata['generate_type'] == 0 && $sec_from_date < $sec_today) {
            $group_calls_to_date = "{$to_date} 23:59:59";
            $group_calls = $this->common->get_user_group_calls_cdrs($accountdata['id'], $from_date, $group_calls_to_date);
            $cdrs_without_group = $this->common->get_user_cdrs_without_group($accountdata['id'], $from_date, $group_calls_to_date);

            if ($group_calls) {
                $i = 1;
                $count_secs = 0;
                $count_amount = 0;
                foreach ($group_calls as $key => &$value) {
                    $count_secs += $value['total_seconds'];
                    $count_amount += $value['total_debit'];
                    $value['total_seconds'] = $this->common->convert_sec_to_minsec($value['total_seconds']);
                    $value['total_debit'] = $this->common->currency_decimal($value['total_debit']);
                    $value['num'] = $i;
                    $i++;
                }

                if ($cdrs_without_group) {
                    $without_group_secs = $cdrs_without_group->total_seconds - $count_secs;
                    $without_group_amount = $cdrs_without_group->total_debit - $count_amount;
                    if ($without_group_secs) {
                        $group_calls[] = array(
                            'total_seconds' => $this->common->convert_sec_to_minsec($without_group_secs),
                            'group_calls_name' => _('No zone'),
                            'total_debit' => $this->common->currency_decimal($without_group_amount),
                            'num' => $i
                        );
                    }
                }
                $template_data['group_calls'] = $group_calls;
            }

        }

        $destination_group_calls = $this->templates_model->get_destination_group_calls_cdrs($accountdata['id'], $invoicedata['from_date'], $invoicedata['to_date']);
        $i = 1;
        foreach ($destination_group_calls as &$group_row) {
			$group_row['total_seconds'] = $this->common->convert_sec_to_minsec($group_row['total_seconds']);
			$group_row['total_debit'] = $this->common->currency_decimal($group_row['total_debit']);
			$group_row['num'] = $i;
			$i++;
		}
		$template_data['destination_group_calls'] = $destination_group_calls;
		unset($group_row);

        // Try to add user custom variables
        $vars_query = $this->db_model->getSelect("*", "invoice_template_vars",'');
        $vars_res = $vars_query->result_array();
        foreach ($vars_res as $vars) {
            $query = $this->update_template($vars['query'], $template_data);
            $res_query = $this->db->query($query);
            if ($res_query) {
            	$count_row = $res_query->num_rows();
            	if ($count_row > 1) {
					$template_data[$vars['name']] = $res_query->result_array();
				} else {
					$row_query = $res_query->row_array();
					if (count(array_keys($row_query)) === 1) {
						$template_data[$vars['name']] = array_keys($row_query)[0];
					} else {
						$template_data[$vars['name']] = array($row_query);
					}
				}
            }
        }
        return $template_data;
    }

    function update_template($templ, $template_data, $return_original_if_not_found = false) {
        $pattern = '/\{\$([^\s{}$]+)\}/iUs';
        while (preg_match($pattern, $templ) > 0) {
            $templ = preg_replace_callback($pattern, function ($matches) use (&$template_data, $return_original_if_not_found) {
                $var_parts = explode('.', $matches[1]);
                $repl_str = '';
                if ($return_original_if_not_found) {
                    $repl_str = $matches[1];
                }
                // parse index of array
                $keys = array($var_parts[0]);
                if (preg_match('/(.*)\[(\d+)\]/', $var_parts[0], $index_matches)) {
                    $keys = array($index_matches[1], $index_matches[2]);
                }

                if (count($var_parts) === 2) {
                    $keys[] = $var_parts[1];
                }
                $item = &$template_data;
                $is_found = false;
                foreach ($keys as $key) {
                    if (array_key_exists($key, $item)) {
                        $item = &$item[$key];
                        $is_found = true;
                    } else {
                        $is_found = false;
                        break;
                    }
                }
                if ($is_found) {
                    $repl_str = $item;
                }
                return $repl_str;
            }, $templ);
        }
        return $templ;
    }


    function get_invoice_template($invoicedata, $accountdata, $flag) {
        $template_id = $accountdata['invoice_template_id'];
        $template_query = $this->db_model->getSelect("*", "invoice_templates", array(
            'id' => $template_id
        ));
        $template_res = $template_query->result_array();

        if (!empty($template_res[0])) {
            $template = $template_res[0];

            $template_data = $this->select_template_data($invoicedata, $accountdata);

            // parsing tbody with foreach attributes and update templates
            $print_parts_template = function($template_name) use (&$template, &$template_data) {
				$templ = preg_replace_callback('/(<table.*>\s*<thead.*>.*<\/thead>.*)<tbody(.*)foreach="\$(\S+)"(.*)>(.*)<\/tbody>\s*<\/table>/iUsm',
					function($matches) use (&$template_data){
						$item_name = $matches[3];
						$tbody = '';
						if (!empty($template_data[$item_name])) {
							$tbody = "<tbody".$matches[2].$matches[4].">";
							foreach($template_data[$item_name] as $i => $item) {
								$template_data["i"] = $i;
								$tbody .= $this->update_template($matches[5], $template_data);
							}
							$tbody = $matches[1].$tbody."</tbody></table>";
						}
						return $tbody;
					}, $template[$template_name]);
				$templ = $this->update_template($templ, $template_data);
                return $templ;
            };


			$head_content = $print_parts_template('head_template');
            $first_page_content = $print_parts_template('page1_template');
			$second_page_content = $print_parts_template('page2_template');
            $footer_content = $print_parts_template('footer_template');
            $this->prepare_pdf($head_content, $first_page_content, $second_page_content, $footer_content);

//            $content_pdf = $this->html2pdf->Output('', true);
//            header('Content-type: application/pdf');
//            echo $content_pdf;
//            exit();

            if ($flag == 'TRUE') {
                $download_path = $invoicedata['invoice_prefix'] . $invoicedata['invoiceid'] . ".pdf";
                $this->html2pdf->Output($download_path, "D");
            } else {
                $current_dir = getcwd() . "/invoices/";
                $dir_name    = $accountdata["id"];
                if (!is_dir($current_dir . $dir_name)) {
                    mkdir($current_dir . $dir_name, 0777, true);
                    chmod($current_dir . $dir_name, 0777);
                }
                $invoice_path  = $this->config->item('invoices_path');
                $download_path = $invoice_path . $accountdata["id"] . '/' . $invoicedata['invoice_prefix'] . $invoicedata['invoiceid'] . "_invoice.pdf";
                $this->html2pdf->Output($download_path, "F");
            }
        }
    }

    function prepare_pdf($head_content, $first_page_content, $second_page_content, $footer_content) {
        ob_start();
        $this->load->library('/html2pdf/html2pdf');
        $this->html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(
            '0',
            '0',
            '0',
            '0'
        ));

        $this->html2pdf->pdf->SetDisplayMode('fullpage');

        echo "<page backtop=\"40mm\" backbottom=\"30mm\" backleft=\"0\" backright=\"0\" style=\"font-size:10pt; margin:0; padding:0;\" >";
        echo "<style></style>";
		echo '<page_header>';
        echo $head_content;
        echo '</page_header>';
		echo '<page_footer style="width: 100%;">';
		echo $footer_content;
		echo '</page_footer>';
		echo $first_page_content;
		echo '</page>';

//        echo '<page_header>'.
//            '<table class="page_header">'.
//            '<tr>'.
//            '    <td style="width: 100%;height:20px; text-align: left;">'.
//            '    </td>'.
//            '</tr>'.
//            '</table>'.
//            '</page_header>'.
//            '</page>';

        if (!empty($second_page_content)) {
			echo "<page backtop=\"30mm\" backbottom=\"30mm\" backleft=\"0\" backright=\"0\" style=\"font-size:10pt; margin:0; padding:0;\" >";
			echo "<style></style>";
			echo '<page_header>';
			echo $head_content;
			echo '</page_header>';
			echo '<page_footer style="width: 100%;">';
			echo $footer_content;
			echo '</page_footer>';
			echo $second_page_content;
			echo '</page>';
		}
        $content = ob_get_clean();
        ob_clean();
        // echo $content; exit();
        $this->html2pdf->pdf->SetDisplayMode('fullpage');
        $this->html2pdf->writeHTML($content);
        $this->html2pdf->pdf->SetMyFooter(false, false, false, false);
    }
}

?>