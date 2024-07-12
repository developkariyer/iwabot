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
        return WarehouseAbstract::$containerTableName;
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
                    throw new Exception("Field $field known but no validation rule set");
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
            throw new Exception("Cannot delete container {$this->id} with children");
        }
        if (!empty($this->getProducts(noCache: true))) {
            throw new Exception("Cannot delete container {$this->id} with products: ".json_encode($this->getProducts(noCache:true)));
        }
        return true;
    }

    public function getChildren($noCache = false)
    {
        if (empty($this->id)) {
            return [];
        }
        if ($noCache) {
            $this->children = [];
        }
        if (empty($this->children)) {
            $cache = unserialize(static::getCache("Container{$this->id}Children"));
            if (!$noCache && is_array($cache)) {
                $this->children = $cache;
            } else {
                $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE deleted_at IS NULL AND parent_id = ? ORDER BY name ASC");
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

    public function getProducts($noCache = false)
    {
        if ($noCache) {
            $this->products = [];
        }
        if (empty($this->products)) {
            $cache = unserialize(static::getCache("Container{$this->id}Products"));
            if (!$noCache && is_array($cache)) {
                $rows = $cache;
            } else {
                $sql = "
                    SELECT wp.id, wp.name, wp.fnsku, count(*) as product_count 
                    FROM ".WarehouseAbstract::$productJoinTableName." wsp
                    JOIN ".WarehouseProduct::getTableName()." wp ON wsp.product_id = wp.id
                    WHERE wsp.container_id = :container_id AND wsp.deleted_at IS NULL
                    GROUP BY wp.id, wp.name, wp.fnsku
                    ORDER BY wp.name ASC";
                $stmt = $GLOBALS["pdo"]->prepare($sql);
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

    public function getTotalCount()
    {
        if (empty($this->totalCount)) {
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
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE deleted_at IS NULL AND type = :type ";
        $params = ['type' => $type];
        if (is_null($parent_id)) {
            $sql .= " AND parent_id IS NULL";
        } else {
            if (is_numeric($parent_id) && $parent_id>0) {
                $sql .= " AND parent_id = :parent_id";
                $params['parent_id'] = $parent_id;
            }
        }
        $sql .= " ORDER BY name ASC";
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

    public static function getEmptyContainers($ajax = false)
    {
        $containers = static::getAll();
        $emptyContainers = [];
        foreach ($containers as $container) {
            if (!$container->getChildren() && !$container->getProducts()) {
                $emptyContainers[] = $container;
            }
        }
        if (!$ajax) {
            return $emptyContainers;
        }
        $icon = [
            'Gemi' => '🚢', //\u{1F6A2}
            'Raf' => '🗄️', // \u{1F5C4}
            'Koli' => '📦', //\u{1F4E6}
        ];
        $html = '<div>';
        foreach ($emptyContainers as $container) {
            $html .= '<span class="badge bg-secondary me-2 mb-1" style="display:inline;">';
            $html .= "{$icon[$container->type]} {$container->name}</li>";
            $html .= '</span>';
        }
        $html .= '</div>';
        return $html;
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

    public function findSimilarIds()
    {
        $sql = "SELECT container_id FROM ".WarehouseAbstract::$containerSignatureTableName." WHERE signature = (SELECT signature FROM ".WarehouseAbstract::$containerSignatureTableName." WHERE container_id = :container_id) AND container_id <> :container_id";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute(['container_id' => $this->id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findSimilar()
    {
        $cache = unserialize(static::getCache("findSimilar{$this->id}"));
        if (is_array($cache)) {
            return $cache;
        }
        $containers = [];
        $ids = $this->findSimilarIds();
        foreach ($ids as $container_id) {
            $containers[] = static::getById($container_id);
        }
        static::setCache("findSimilar{$this->id}", serialize($containers));
        return $containers;
    }

    /* ACTION METHODS BELOW */

    public function checkCompatibility($object)
    {
        error_log("checkCompatibility for $object->id");
        if (!$object instanceof self) {
            error_log("checkCompatibility: Not a WarehouseContainer object");
            return false;
        }
        if ($this->type !== $object->type) {
            error_log("checkCompatibility: Different container types");
            return false;
        }
        if ($this->id != $object->id && !in_array($object->id, $this->findSimilarIds())) {
            error_log("checkCompatibility: Not a similar container");
            return false;
        }
        return true;
    }

    public function fulfil($sold_id)
    {
        $soldItem = WarehouseSold::getById($sold_id);
        if (!$soldItem) {
            throw new Exception("{$sold_id} kodlu satış kaydı bulunamadı.");
        }
        $soldItem->fulfil($this);
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
                WarehouseLogger::logAction('setParent', ['old_parent_id' => $oldParentId, 'new_parent_id' => $this->parent_id], $this);
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
