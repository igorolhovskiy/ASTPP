<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
* Product grids by Andrey Golubov
*/
class products_grids
{
    var $CI = NULL;

    function __construct()
    {
        $this->CI = & get_instance();
    }

    function gridProductsList()
    {
        $account_info = $accountinfo = $this->CI->session->userdata('accountinfo');

        $grid_field_arr = json_encode(array(
            array("<input type='checkbox' name='chkAll' class='ace checkall'/><label class='lbl'></label>", "30", "", "", "", "", "", "false","center"),
            array(gettext("Id"), "30", "id", "", "", "", "EDITABLE", "true", "left"),
            array(gettext("Name"), "100", "name", "", "", "","","true","center"),
            array(gettext("Description"), "500", "description", "", "", "","","true","left"),
            array(gettext("Price"), "100", "price", "", "", "","","true","center"),
            array(gettext("Create At"), "100", "create_at", "", "", "", "true", "center"),
            array(gettext("Action"), "140", "", "", "", array(
                /*"CALLERID" => array("url" => "products?create=", "mode" => "popup"),*/
                "EDIT" => array("url" => "products?edit=", "mode" => "single"),
                "DELETE" => array("url" => "products?delete=", "mode" => "single")))
        ));
        return $grid_field_arr;
    }

    function gridProductsButtons()
    {
        $logintype = $this->CI->session->userdata('userlevel_logintype');

        $buttons_json = json_encode(array(
            array(gettext("Create product"), "btn btn-line-warning btn", "fa fa-plus-circle fa-lg", "button_action", "/products?create=new"),
            array(gettext("Delete"), "btn btn-line-danger", "fa fa-times-circle fa-lg", "button_action", "/products?delete_change=true")
        ));
        return $buttons_json;
    }

    function gridProductForm($data = FALSE)
    {
        $form = array();

        $id = null;
        $name = NULL;
        $description = NULL;
        $price = NULL;

        if($data) extract($data);

        $form["forms"] = array(base_url() . "products?save=true", array("id" => "product_from", "name" => "product_from", "method" => "POST"));
        $title = (!$data) ? "New Product" : "Edit Product";
        $form[$title] = array(
            array("", 'HIDDEN', array('name' => 'id', 'id' => 'id', 'value' => $id, 'size' => '15', 'maxlength' => '255', 'class' => "text field medium"), '', 'tOOL TIP', ''),
            array(gettext('Name'), 'INPUT', array('name' => 'name', 'id' => 'name', 'value' => $name, 'size' => '15', 'maxlength' => '255', 'class' => "text field medium"), 'trim|required|xss_clean', 'tOOL TIP', 'Please Enter product name'),
            array(gettext('Description'), 'TEXTAREA', array('name' => 'description', 'value' => $description, 'id' => 'description', 'cols' => '85', 'class' => "text field medium"), 'trim|xss_clean', 'tOOL TIP', ''),
            array(gettext('Price'), 'INPUT', array('name' => 'price', 'id' => 'price', 'value' => $price, 'size' => '15', 'maxlength' => '255', 'class' => "text field medium"), 'trim|required|xss_clean', 'tOOL TIP', 'Please Enter product price'),
        );

        $form['button_cancel'] = array('name' => 'action', 'content' => gettext('Cancel'), 'value' => 'cancel', 'type' => 'button', 'class' => 'btn btn-line-sky margin-x-10', 'onclick' => 'return redirect_page(\'/products/\')');
        $form['button_save'] = array('name' => 'action', 'content' => gettext('Save'), 'value' => 'save', 'type' => 'submit', 'class' => 'btn btn-line-parrot');

        return $form;
    }
}