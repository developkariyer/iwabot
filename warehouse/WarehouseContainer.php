<?php

require_once('WarehouseAbstract.php');
require_once('WarehouseProduct.php');

class WarehouseContainer extends WarehouseAbstract
{
    protected array $children = [];
    protected array $products = [];
    protected $parent = null;
    protected static $dbFields = [];
    protected static $warehouses = [];
    protected static $parentContainers = [];
    private $totalCount = 0;
    private static $unfulfilled = [];
    protected static $allObjects = [];

    public static function getTableName()
    {
        return 'warehouse_container';
    }

    protected static function getCustomMethods()
    {
        return [
            'children' => 'getChildren',
            'products' => 'getProducts',
            'parent' => 'getParent',
        ];
    }

    protected static function validateField($field, $value)
    {
        switch ($field) {
            case 'name':
                return is_string($value) && strlen($value) <= 255;
            case 'type':
                return in_array($value, ['Gemi', 'Raf', 'Koli']);
            case 'parent_id':
                return is_null($value) || is_numeric($value);
            case 'warehouse':
                return is_null($value) || ( is_string($value) && strlen($value) <= 100 );
            default:
                if (in_array($field, static::getDbFields())) {
                    throw new Exception("Field known but no validation rule set");
                }
                return false;
        }
    }

    protected function canDelete()
    {
        if (empty($this->id)) {
            throw new Exception("Cannot delete an object without an ID");
        }
        if (!empty($this->getChildren())) {
            throw new Exception("Cannot delete a container with children");
        }
        if (!empty($this->getProducts())) {
            throw new Exception("Cannot delete a container with products");
        }
        return true;
    }

    protected function getChildren()
    {
        if (empty($this->id)) {
            return [];
        }
        if (empty($this->children)) {
            $cache = unserialize(static::getCache("Container{$this->id}Children"));
            if (is_array($cache)) {
                $this->children = $cache;
            } else {
                $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE parent_id = ?");
                $stmt->execute([$this->id]);
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $instance = static::getInstance($data['id']);
                    if (!$instance) {
                        $instance = new self($data['id'], $data);
                    }
                    $this->children[] = $instance;
                }
                static::setCache("Container{$this->id}Children", serialize($this->children));
            }
        }
        return $this->children;
    }

    protected function getProducts()
    {
        if (empty($this->products)) {
            $cache = unserialize(static::getCache("Container{$this->id}Products"));
            if (is_array($cache)) {
                $rows = $cache;
            } else {
                $sql = "
                    SELECT wp.id, wp.name, wp.fnsku, count(*) as product_count 
                    FROM ".WarehouseAbstract::$productJoinTableName." wsp
                    JOIN ".WarehouseProduct::getTableName()." wp ON wsp.product_id = wp.id
                    WHERE wsp.container_id = :container_id
                    GROUP BY wp.id, wp.name, wp.fnsku";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':container_id', $this->id, PDO::PARAM_INT);
                $stmt->execute();
                $this->totalCount = 0;
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                static::setCache("Container{$this->id}Products",serialize($rows));
            }

            foreach ($rows as $row) {
                $instance = WarehouseProduct::getInstance($row['id']);
                if (!$instance) {
                    $instance = new WarehouseProduct($row['id'], $row);
                }
                $instance->inContainerCount[$this->id] = $row['product_count'];
                $this->totalCount += $row['product_count'];
                $this->products[] = $instance;
            }
        }
        return $this->products;
    }

    protected function getTotalCount()
    {
        if (empty($this->totalCount)) {
            error_log("Cache Candidate: getTotalCount");
            $this->getProducts();
        }
        return $this->totalCount;
    }

    protected function getParent()
    {
        if (is_null($this->parent_id)) {
            return null;
        }
        if (!$this->parent) {
            $this->parent = static::getById($this->parent_id);
        }
        return $this->parent;
    }

    public static function getWarehouses()
    {
        if (empty(static::$warehouses)) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT DISTINCT warehouse FROM " . static::getTableName() . " ORDER BY warehouse ASC");
            $stmt->execute();
            static::$warehouses = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                static::$warehouses[] = $data['warehouse'];
            }
        }
        return static::$warehouses;
    }

    public static function getContainers($type = 'Raf', $parent_id = -1, $warehouse = null)
    {
        if (!static::validateField('type', $type)) {
            throw new Exception("Invalid container type: $type");
        }
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE type = :type";
        $params = ['type' => $type];
        if (is_null($parent_id)) {
            $sql .= " AND parent_id IS NULL";
        } else {
            if (is_numeric($parent_id) && $parent_id>0) {
                $sql .= " AND parent_id = :parent_id";
                $params['parent_id'] = $parent_id;
            }
        }/*TODO warehouse fix
        if (is_null($warehouse)) {
            $sql .= " AND warehouse IS NULL";
        } else {
            if (static::validateField('warehouse', $warehouse)) {
                $sql .= " AND warehouse = :warehouse";
                $params['warehouse'] = $warehouse;
            }
        }*/
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute($params);
        $containers = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = static::getInstance($data['id']);
            if (!$instance) {
                $instance = new self($data['id'], $data);
            }
            $containers[] = $instance;
        }
        return $containers;
    }

    public function getAsArray()
    {
        $retval = parent::getAsArray();
        $retval['children'] = [];
        foreach ($this->getChildren() as $child) {
            $retval['children'][] = $child->getAsArray();
        }
        $retval['products'] = [];
        foreach ($this->getProducts() as $product) {
            $productAsArray = $product->getAsArray();
            $productAsArray['stock'] = $product->inContainerCount[$this->id];
            $retval['products'][] = $productAsArray;
        }
        return $retval;
    }

    public static function getUnfulfilledBoxes()
    {
        if (empty(static::$unfulfilled)) {
            $cache = unserialize(static::getCache("getUnfulfilledBoxes"));
            if (is_array($cache)) {
                static::$unfulfilled = $cache;
                error_log("Cache Hit: getUnfulfilledBoxes");
            } else {
                static::$unfulfilled =[];
                $stmt = $GLOBALS['pdo']->query("SELECT * FROM warehouse_sold WHERE fulfilled = FALSE AND sold_type = 'WarehouseContainer' ORDER BY product_id ASC");
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $container = static::getById($data['product_id']);
                    if (!$container) {
                        continue;
                    }
                    static::$unfulfilled[$data['id']] = $data;
                    static::$unfulfilled[$data['id']]['container'] = $container;
                }
                error_log("Cache Miss: getUnfulfilledBoxes");
                static::setCache("getUnfulfilledBoxes", serialize(static::$unfulfilled));
            }
        }
        return static::$unfulfilled;
    }
    
    /* ACTION METHODS BELOW */

    public function fulfil($sold_id)
    {
        if (empty(static::$unfulfilled)) {
            static::getUnfulfilledBoxes();
        }
        if (isset(static::$unfulfilled[$sold_id])) {
            while (count($this->getProducts()) > 0) {
                $product = $this->getProducts()[0];
                $product->removeFromContainer($this, $product->getInContainerCount($this));
            }
            if ($this->delete()) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE warehouse_sold SET fulfilled = TRUE WHERE id = :id");
                if ($stmt->execute(['id' => $sold_id])) {
                    $this->logAction('fulfil_box', ['sold_id' => $sold_id]);
                    $this->clearAllCache();
                    return true;
                }
            } else {
                throw new Exception("{$this->fnsku} kodlu koli çıkartılırken bir hata oluştu.");
            }
        } else {
            throw new Exception("{$this->fnsku} kodlu koli ilgili satış kaydı bulunamadı.");
        }
    }

    public function addSoldBox($description)
    {
        if (empty($description) || !is_string($description)) {
            throw new Exception("addSoldBox: Açıklama boş olamaz");
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO warehouse_sold (product_id, sold_type, description) VALUES (:product_id, 'WarehouseContainer', :description)");
        if ($stmt->execute(['product_id' => $this->id, 'description' => $description])) {
            $this->logAction('addSoldBox', ['description' => $description]);
            $this->clearAllCache();
            return true;
        }
        return false;
    }

    public function setParent($newParent)
    {
        if ($newParent instanceof self) {
            $oldParentId = $this->parent_id;
            if ($this->getParent()) {
                $this->parent->children = [];
            }
            $this->parent_id = $newParent->id;
            $this->parent = $newParent;
            $newParent->children = [];
            if ($this->save()) {
                $this->logAction('setParent', ['old_parent_id' => $oldParentId, 'new_parent_id' => $this->parent_id]);
                return true;
            }
            return false;
        }
        throw new Exception("Üst raf WarehouseContainer sınıfından olmalı");
    }

    public static function addNew($data)
    {
        if (!empty($data['parent_id'])) {
            $parent = static::getById($data['parent_id']);
            if (!$parent) {
                throw new Exception("Verilen üst raf tanınmıyor: ".$data['parent_id']);
            }
            if ($parent->type !== 'Raf') {
                throw new Exception("Verilen üst birim Raf tipinde olmalı");
            }
        }
        return parent::addNew($data);
    }

}
