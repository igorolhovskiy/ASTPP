<?php
/**
* Product controller by Andrey Golubov
*/
class Products extends MX_Controller
{
    var $pageTitle = "Products";

    function Products()
    {
        $this->load->model('product_model');
        $this->load->library('products_grids');
        $this->load->library('session');
        $this->load->library('astpp/form');
    }

    function index()
    {
        $delete = $this->input->get('delete');
        $delete_change = $this->input->get('delete_change');
        $create = $this->input->get('create');
        $edit = $this->input->get('edit');
        $save   = $this->input->get('save');
        $rp     = $this->input->get('rp');
        $page   = $this->input->get('page');

        if(($rp && !empty($rp))|| ($page && !empty($page))) $this->list_json();
        if($delete && !empty($delete)) $this->delete(intval($delete));
        if($delete_change && !empty($delete_change)) $this->delete();
        if($create && !empty($create)) $this->create();
        if($save && !empty($save)) $this->save();
        if($edit && !empty($edit)) $this->edit($edit);

        $grid_fields = $this->products_grids->gridProductsList();
        $grid_buttons = $this->products_grids->gridProductsButtons();

        $data = array(
            "title" => $this->pageTitle,
            "grid_fields" => $grid_fields,
            "grid_buttons" => $grid_buttons
        );

        $this->load->view("product_list", $data);
    }

    function list_json() 
    {
        header('Content-Type: application/json');

        $json_data = array();

        $products = $this->product_model->getCollection(array('delete_at' => NULL), 'id', 'desc');
        $productsCount = count($products);

        $paging_data = $this->form->load_grid_config($productsCount, $_GET['rp'], $_GET['page']);

        $json_data = $paging_data["json_paging"];
        $limit = array($paging_data["paging"]["start"], $paging_data["paging"]["page_no"]);

        $query = $this->product_model->getCollection(array('delete_at' => NULL), 'id', 'desc', $limit, true);

        $grid_fields = json_decode($this->products_grids->gridProductsList());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);

        exit(json_encode($json_data));
    }

    function create()
    {
        $data = array();
        $grid_form = $this->products_grids->gridProductForm();
        $data["form"] = $this->form->build_form($grid_form, '');

        $render = $this->load->view("product_form", $data, TRUE);
        exit($render);
    }

    function delete($id = FALSE)
    {
        $postData = $this->input->post('selected_ids');

        if($postData && !empty($postData)) {
            $selected_products = explode(",", str_replace('\'', '', $this->input->post('selected_ids')));
        }

        if($id) {

            $this->product_model->load($id)->delete();

        } elseif($selected_products && !empty($selected_products)) {

            foreach ($selected_products as $key => $value) {
                $this->product_model->load(intval($value))->delete();
            } 
        }

        echo 1;die();
    }

    function save()
    {
        $data = $this->input->post();
        $data['price'] = floatval($data['price']);

        if(isset($data["id"]) && !empty($data["id"]))  {
            $this->product_model
                ->load($id)
                ->setData($data)
                ->save();
        } else {
            $id = $this->product_model
                    ->setData($data)
                    ->save();
        }
        redirect(base_url() . "products");
    }

    function edit($id)
    {
        $data = array();
        $products = $this->product_model->load($id)->getData();
        $grid_form = $this->products_grids->gridProductForm($products);
        $data["form"] = $this->form->build_form($grid_form, '');

        $render = $this->load->view("product_form", $data, TRUE);
        exit($render);
    }
}