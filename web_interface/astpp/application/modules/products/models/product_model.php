<?php
/**
* Product model by Andrey Golubov 2016-12-25
*/
class product_model extends CI_Model
{
    // Product table name
    var $table = 'products';

    // Id table field name
    var $entity = 'id';

    // Delete table field name
    var $delete_field = 'delete_at';

    // Product id (int)
    var $id = NULL;

    // Product name (stirng 255)
    var $name = NULL;

    // Product description (text)
    var $description = NULL;

    // Product price (double/decimal)
    var $price = NULL;

    // Create at (datetime)
    var $create_at = NULL;

    // Delete at (datetime)
    var $delete_at = NULL;

    function product_model()
    {
        parent::__construct();
    }

    /**
    * Load product 
    * @var id (int)
    *
    * @return product_model
    */
    function load($id)
    {
        $product = $this->db
            ->from("products")
            ->select("*")
            ->where("id", $id)
            ->get()
            ->row_array();

        foreach ($product as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
    * Load product collection
    * @var array data
    * @var string by - order field
    * @var string order - order type
    *
    * @return array objets {product_model}
    */
    function getCollection($data, $by = false, $order = false, $limit = false, $query = false)
    {
        $products = $this->db
            ->from($this->table)
            ->select("*");

        foreach ($data as $key => $value) {
            $products->where($key, $value);
        }

        if($by && $order) $products->order_by($by, $order);

        if($limit) $products->limit($limit[1], $limit[0]);

        if($query) return $products->get();

        $products = $products->get()->result();

        $result = array();

        foreach ($products as $product_key => $product) {
            $tmp = new self();
            foreach ($product as $key => $value) {
                $tmp->{$key} = $value;
            }
            $result[] = $tmp;
        }

        return $result;
    }

    /**
    * Get product id
    *
    * @return int
    */
    function getId()
    {
        return $this->id;
    }

    /**
    * Get product name
    *
    * @return string
    */
    function getName()
    {
        return $this->name;
    }

    /**
    * Get product description
    *
    * @return string
    */
    function getDesc()
    {
        return $this->description;
    }

    /**
    * Get product price
    *
    * @return float
    */
    function getPrice()
    {
        return floatval($this->price);
    }

    /**
    * Get product data as array
    *
    * @return array
    */
    function getData()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price
        );
    }

    /**
    * Set product name
    * @var string name
    *
    * @return object product_model
    */
    function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
    * Set product description
    * @var string description
    *
    * @return object product_model
    */
    function setDesc($desc)
    {
        $this->description = $desc;
        return $this;
    }

    /**
    * Set product price
    * @var float price
    *
    * @return object product_model
    */
    function setPrice($price)
    {
        $type = gettype($price);
        if ($type !== "integer" || $type !== "double") throw new Exception("Error set product price: invalid type");
        
        $this->price = $price;
        return $this;
    }

    /**
    * Set product data
    * @var array data (["id" => 1, "name" => "product", "description" => "Simple product", "price" => 12.33])
    * 
    * @return object product_model
    */
    function setData($data)
    {
        $product_vars = get_class_vars(get_class($this));

        foreach ($data as $key => $value)
        {
            if (!array_key_exists($key, $product_vars)) continue;

            $this->{$key} = $value;
        }

        return $this;
    }

    /**
    * Save product data
    * 
    * @return object product_model
    */
    function save()
    {
        $data = array(
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price
        );

        if(!$this->name) throw new Exception("Fail save product: invalid type product name");
        if(!$this->price) throw new Exception("Fail save product: invalid type product price");
        
        if($this->id) {
            $this->db->where($this->entity, $this->id);
            $this->db->update($this->table, $data);
        } else {
            $this->db->insert($this->table, $data);
            $this->id = $this->db->insert_id();
        }

        return $this;
    }

    /**
    * Delete product from site
    * Set delete at field in table
    *
    * @return object product_model
    */
    function delete()
    {
        if(!$this->id) throw new Exception("Fail delete product: product id is not change");

        $this->db->where($this->entity, $this->id);
        $this->db->update($this->table, array($this->delete_field => date("Y-m-d H:i:s")));

        return $this;
    }

    /**
    * Trash product form database
    * 
    * @return null
    */
    function trash()
    {
        if(!$this->id) throw new Exception("Fail delete product: product id is not change");

        $this->db->where($this->entity, $this->id);
        $this->db->delete($this->table);
        
        return NULL;
    }
}