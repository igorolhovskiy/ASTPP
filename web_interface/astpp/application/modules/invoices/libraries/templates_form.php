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

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Templates_form {

    function get_template_form_fields() {

        $form['forms'] = array(base_url() . 'invoices/templates/template_save/', array("template_form", "name" => "template_form"));
        $form['Invoice Template'] = array(
            array('', 'HIDDEN', array('name' => 'id'), '', '', '', ''),
            array('Name', 'INPUT', array('name' => 'name', 'size' => '20', 'class' => "text field medium"), 'trim|required|xss_clean', 'tOOL TIP', ''),
            array('Subject', 'INPUT', array('name' => 'subject', 'size' => '20',  'class' => "text field medium"), 'trim|required|xss_clean', 'tOOL TIP', ''),
            array('Head', 'TEXTAREA', array('name' => 'head_template', 'id' => 'head_template', 'size' => '10',  'class' => "textarea medium"), 'trim|required', 'tOOL TIP', ''),
			array('Page 1', 'TEXTAREA', array('name' => 'page1_template', 'id' => 'page1_template', 'size' => '10',  'class' => "textarea medium"), 'trim|required', 'tOOL TIP', ''),
			array('Page 2', 'TEXTAREA', array('name' => 'page2_template', 'id' => 'page2_template', 'size' => '10',  'class' => "textarea medium"), 'trim|required', 'tOOL TIP', ''),
            array('Footer', 'TEXTAREA', array('name' => 'footer_template', 'id' => 'footer_template', 'size' => '10',  'class' => "textarea medium"), 'trim|required', 'tOOL TIP', ''),
        );

        $form['button_cancel'] = array('name' => 'action', 'content' => 'Cancel', 'value' => 'cancel', 'type' => 'button', 'class' => 'btn btn-line-sky margin-x-10', 'onclick' => 'return redirect_page(\'invoices/templates/template_list/\')');
        $form['button_save'] = array('name' => 'action', 'content' => 'Save', 'value' => 'save', 'type' => 'submit', 'class' => 'btn btn-line-parrot');

        return $form;
    }

    function get_template_search_form() {
        $form['forms'] = array("", array('id' => "template_search"));
        $form['Search'] = array(

            array(' Names', 'INPUT', array('name' => 'name[name]', '', 'size' => '20', 'class' => "text field "), '', 'tOOL TIP', '1', 'name[name-string]', '', '', '', 'search_string_type', ''),
            array('Subject', 'INPUT', array('name' => 'subject[subject]', '', 'size' => '20', 'class' => "text field "), '', 'tOOL TIP', '1', 'subject[subject-string]', '', '', '', 'search_string_type', ''),array('', 'HIDDEN', 'ajax_search', '1', '', '', ''),
            array('', 'HIDDEN', 'advance_search', '1', '', '', '')
        );
        $form['button_search'] = array('name' => 'action', 'id' => "template_search_btn", 'content' => 'Search', 'value' => 'save', 'type' => 'button', 'class' => 'btn btn-line-parrot pull-right');
        $form['button_reset'] = array('name' => 'action', 'id' => "id_reset", 'content' => 'Clear', 'value' => 'cancel', 'type' => 'reset', 'class' => 'btn btn-line-sky pull-right margin-x-10');
        return $form;
    }

	function build_grid_buttons() {
        $buttons_json = json_encode(array(array("Create template","btn btn-line-warning btn","fa fa-plus-circle fa-lg", "button_action", "invoices/templates/add/")
        ));
        return $buttons_json;
	}

	function build_template_list() {
		$grid_field_arr = json_encode(array(
			array("Name", "300", "name", "", "", "","","true","center"),
			array("Subject", "500", "subject", "", "", "","","true","center"),
			array("Action", "75", "", "", "",
                array(
                    "EDIT" => array("url" => "invoices/templates/edit/", "mode" => "single"),
                    "DELETE" => array("url" => "invoices/templates/delete/", "mode" => "single")
                )
            )
		));

		return $grid_field_arr;
	}
}

?>