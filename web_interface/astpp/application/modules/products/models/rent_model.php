<?php
/**
* Rent model by Andrey Golubov
*/
class rent_model extends CI_Model
{
    var $table = "rent_products";
    var $type_table = "rent_products_types";
    var $user_table = "accounts";
    var $product_table = "products";
    var $user_field = "user_id";
    var $entity = "id";
    var $delete_at_field = "delete_at";

    function rent_model()
    {
        parent::__construct();
        $this->load->library('session');
    }

    function getCollection($where = array(), $by = false, $order = false, $limit = false, $asQuery = false)
    {
        $this->db
            ->from($this->table)
            ->join($this->user_table, "{$this->user_table}.id = {$this->table}.user_id")
            ->join($this->type_table, "{$this->table}.payment_type = {$this->type_table}.id")
            ->join($this->product_table, "{$this->product_table}.id = {$this->table}.product_id")
            ->select("{$this->type_table}.name AS payment_type_name, {$this->table}.create_at AS rent_create_at, {$this->table}.delete_at AS rent_delete_at, {$this->table}.*, {$this->user_table}.id AS uid, {$this->product_table}.name AS name, {$this->product_table}.delete_at AS product_delete_at, {$this->product_table}.price AS price, {$this->product_table}.description AS description");

        foreach ($where as $key => $value) {
            $this->db->where($key, $value);
        }

        if($by && $order) $this->db->order_by($by, $order);
        if($limit) $this->db->limit($limit[1], $limit[0]);

        if($asQuery) return $this->db->get();

        $rents = $this->db->get()->result_array();

        $result = array();

        foreach ($rents as $rent_key => $rent) {
            $result[$rent_key] = new self();
            foreach ($rent as $k => $v) {
                $result[$rent_key]->{$k} = $v;
            }
        }

        return $result;
    }

    function load($id = false)
    {
        if(!$id) throw new Exception("Fail load rent: ID not change");
        
        $rent = $this->db
                    ->from($this->table)
                    ->join($this->user_table, "{$this->user_table}.id = {$this->table}.user_id")
                    ->join($this->product_table, "{$this->product_table}.id = {$this->table}.product_id")
                    ->select("{$this->table}.create_at AS rent_create_at, {$this->table}.delete_at AS rent_delete_at, {$this->table}.*, {$this->user_table}.id AS uid, {$this->product_table}.name AS name, {$this->product_table}.delete_at AS product_delete_at, {$this->product_table}.price AS price, {$this->product_table}.description AS description")
                    ->where("rent_products.id", $id)
                    ->get()
                    ->result();

        foreach ($rent as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    function setData($data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    function save()
    {
        $data = array(
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'count' => $this->count,
            'payment_type' => $this->payment_type,
            'leftpayments' => $this->leftpayments,
			'last_payment' => '0000-00-00'
        );

        if($this->id) {
            $this->db->where($this->entity, $this->id);
            $this->db->update($this->table, $data);
        } else {
            $this->db->insert($this->table, $data);
            $this->id = $this->db->insert_id();
        }

        return $this;
    }

    function delete($id = FALSE)
    {
        if(!$id) throw new Exception("Delete rent error: id is not change");

        $this->db->where($this->entity, $id);
        $this->db->update($this->table, array($this->delete_at_field => date("Y-m-d H:i:s")));
    }
}