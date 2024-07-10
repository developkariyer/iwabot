<?php

class WarehouseSold
{
    public $id = null;
    public $item_id = null;
    public $item_type = null;
    public $description = null;
    public $created_at = null;
    public $fulfilled_at = null;
    public $object = null;
    public $updated_at = null;
    public $deleted_at = null;

    public static $soldItemsTableName = 'warehouse_sold';

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->item_id = $data['item_id'];
        $this->item_type = $data['item_type'];
        $this->description = $data['description'];
        $this->created_at = $data['created_at'];
        $this->fulfilled_at = $data['fulfilled_at'];
        $this->updated_at = $data['updated_at'];
        $this->deleted_at = $data['deleted_at'];
        $this->object = $this->item_type::getById($this->item_id);
    }

    public static function getById($id)
    {
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . self::$soldItemsTableName . " WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return new WarehouseSold($data);
        }
        return null;
    }

    public static function getSoldItems($item_type = null, $fulfilled = false)
    {
        $sql = "SELECT * FROM " . self::$soldItemsTableName . " WHERE deleted_at IS NULL ";
        $sql .= $fulfilled ? "" : " AND fulfilled_at IS NULL";
        $sql .= $item_type ? " AND item_type = :item_type" : "";
        $sql .= " ORDER BY created_at DESC";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        if ($item_type) {
            $stmt->execute(['item_type' => $item_type]);
        } else {
            $stmt->execute();
        }
        $soldItems = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($soldItem = new WarehouseSold($data)) {
                $soldItems[] = $soldItem;
            }
        }
        return $soldItems;
    }

    public static function getSoldProducts()
    {
        return self::getSoldItems('WarehouseProduct');
    }

    public static function getSoldContainers()
    {
        return self::getSoldItems('WarehouseContainer');
    }

    public static function addNewSoldItem($object, $description)
    {
        if (!is_object($object) || !is_string($description)) {
            return false;
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . self::$soldItemsTableName . " (item_id, item_type, description) VALUES (:item_id, :item_type, :description)");
        if ($stmt->execute(['item_id' => $object->id, 'item_type' => get_class($object), 'description' => $description])) {
            $id = $GLOBALS['pdo']->lastInsertId();
            WarehouseLogger::logAction('addSoldItem', ['sold_id' => $id, 'description' => $description], $object);
            WarehouseAbstract::clearAllCache();
            return self::getById($id);
        }
        return null;
    }

    public function fulfil($object = null, $container = null)
    {
        if ($this->fulfilled_at || $this->deleted_at) {
            return false;
        }
        if (is_object($object)) {
            if (!$object->checkCompatibility($this->object)) {
                throw new Exception("fulfil: Object is not compatible");
            }
            $fulfil_id = $object->id;
            $this->object = $object;
        } else {
            $fulfil_id = $this->item_id;
        }
        if ($this->item_type === 'WarehouseProduct') {
            if (!is_object($container)) {
                throw new Exception("fulfil: Container is required for product");
            }
            if (!$this->object->getInContainerCount($container)) {
                throw new Exception("fulfil: Product not found in container");
            }
            if (!$this->object->removeFromContainer($container)) {
                throw new Exception("fulfil: Product could not be removed from container");
            }
        }
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . self::$soldItemsTableName . " SET fulfilled_at = NOW(), item_id = :item_id WHERE id = :id");
        return $stmt->execute(['id' => $this->id, 'item_id' => $fulfil_id]);
    }

    public function delete()
    {
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . self::$soldItemsTableName . " SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    public function update($description)
    {
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . self::$soldItemsTableName . " SET description = :description WHERE id = :id");
        return $stmt->execute(['id' => $this->id, 'description' => $description]);
    }

}