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

    public static function getSoldProducts($fulfilled = false)
    {
        return self::getSoldItems(item_type: 'WarehouseProduct', fulfilled: $fulfilled);
    }

    public static function getSoldContainers($fulfilled = false)
    {
        return self::getSoldItems(item_type: 'WarehouseContainer', fulfilled: $fulfilled);
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
        error_log('************ Fulfil called, beginning debug trace ***********');
        if ($this->fulfilled_at || $this->deleted_at) {
            error_log('fulfil: Sold item already fulfilled or deleted');
            return false;
        }
        if (is_object($object)) {
            error_log('fulfil: Object is object');
            if (!$object->checkCompatibility($this->object)) {
                error_log("fulfil: Object is not compatible: " . get_class($object) . " vs " . get_class($this->object));
                throw new Exception("fulfil: Object is not compatible");
            }
            $this->object = $object;
        }
        if (!$this->object) {
            throw new Exception("fulfil: Object is required");
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
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . self::$soldItemsTableName . " SET fulfilled_at = NOW(), item_id = :item_id WHERE id = :id AND deleted_at IS NULL");
        if ($stmt->execute(['id' => $this->id, 'item_id' => $this->object->id])) {
            WarehouseLogger::logAction('fulfilSoldItem', ['sold_id' => $this->id], $this->object);
            WarehouseAbstract::clearAllCache();
            return true;
        }
        return false;
    }

    public function delete()
    {
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . self::$soldItemsTableName . " SET deleted_at = NOW() WHERE id = :id");
        if ($stmt->execute(['id' => $this->id])) {
            WarehouseLogger::logAction('deleteSoldItem', ['sold_id' => $this->id], $this->object);
            return true;
        }
        return false;
    }

    public function update($description)
    {
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . self::$soldItemsTableName . " SET description = :description WHERE id = :id");
        if ($stmt->execute(['id' => $this->id, 'description' => $description])) {
            WarehouseLogger::logAction('updateSoldItem', ['sold_id' => $this->id, 'old_description' => $this->description, 'new_description'=>$description], $this->object);
            return true;
        }
        return false;
    }

}