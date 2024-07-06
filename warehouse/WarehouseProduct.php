<?php

require_once('WarehouseAbstract.php');
require_once('WarehouseContainer.php');

class WarehouseProduct extends WarehouseAbstract
{
    protected array $containers = [];
    protected static $dbFields = [];
    public $inContainerCount = [];
    private $totalCount = 0;
    private static $unfulfilled = [];
    protected static $allObjects = [];


    public static function getTableName()
    {
        return 'warehouse_product';
    }

    protected static function getCustomMethods()
    {
        return [
            'containers' => 'getContainers',
        ];
    }

    protected static function validateField($field, $value)
    {
        switch ($field) {
            case 'name':
                return is_string($value) && strlen($value) <= 255 && !empty($value);
            case 'fnsku':
                return is_string($value) && strlen($value) <= 100 && !empty($value);
            case 'category':
            case 'iwasku':
            case 'serial_number':
                return empty($value) || (is_string($value) && strlen($value) <= 100);
            case 'dimension1':
            case 'dimension2':
            case 'dimension3':
            case 'weight':
                return empty($value) || is_numeric($value);
            default:
                if (in_array($field, static::getDbFields())) {
                    throw new Exception("Field known but not set for validation");                    
                }
                return false;
        }
    }

    protected function canDelete()
    {
        if (empty($this->id)) {
            throw new Exception("Cannot delete an object without an ID");
        }
        if (!empty($this->getContainers())) {
            throw new Exception("Cannot delete a product that is in a container");
        }
        return true;
    }

    public function getContainers()
    {
        if (empty($this->id)) {
            return [];
        }
        if (empty($this->containers)) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT DISTINCT container_id FROM " . WarehouseAbstract::$productJoinTableName . " WHERE product_id = :product_id");
            $stmt->execute(['product_id' => $this->id]);
            $containers = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $containers[] = WarehouseContainer::getById($data['container_id']);
            }
            $this->containers = $containers;
        }
        return $this->containers;
    }

    private function getInContainerCountFromDb($container)
    {
        $stmt = $GLOBALS['pdo']->prepare("SELECT count(*) as count FROM " . WarehouseAbstract::$productJoinTableName . " WHERE product_id = :product_id AND container_id = :container_id");
        $stmt->execute(['product_id' => $this->id, 'container_id' => $container->id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data['count'];
    }

    public function getInContainerCount($container)
    {
        if ($container instanceof WarehouseContainer && !empty($this->id) && !empty($container->id)) {
            if (!isset($this->inContainerCount[$container->id])) {
                $this->inContainerCount[$container->id] = $this->getInContainerCountFromDb($container);
            }
            return $this->inContainerCount[$container->id];
        }
    }

    public function getTotalCount()
    {
        if (empty($this->totalCount)) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT count(*) as count FROM " . WarehouseAbstract::$productJoinTableName . " WHERE product_id = :product_id");
            $stmt->execute(['product_id' => $this->id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->totalCount = $data['count'];
        }
        return $this->totalCount;
    }

    public function getAsArray()
    {
        $retval = parent::getAsArray();
        $retval['total'] = $this->getTotalCount();
        return $retval;
    }

    public static function getUnfulfilledProducts()
    {
        if (empty(static::$unfulfilled)) {
            static::$unfulfilled =[];
            $stmt = $GLOBALS['pdo']->query("SELECT * FROM warehouse_sold WHERE fulfilled = FALSE ORDER BY product_id ASC");
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $product = static::getById($data['product_id']);
                if (!$product) {
                    continue;
                }
                static::$unfulfilled[$data['id']] = $data;
                static::$unfulfilled[$data['id']]['product'] = $product;
            }
        }
        return static::$unfulfilled;
    }
    
    /* ACTION METHODS BELOW */

    public function fulfil($sold_id, $container)
    {
        if (!($container instanceof WarehouseContainer) || empty($container->id)) {
            throw new Exception("Invalid container");
        }
        if (!$this->getInContainerCount($container)) {
            throw new Exception("{$this->fnsku} kodlu ürün {$container->name} isimli raf/kolide bulunamadı");
        }
        if (empty(static::$unfulfilled)) {
            static::getUnfulfilledProducts();
        }
        if (isset(static::$unfulfilled[$sold_id])) {
            if ($this->removeFromContainer($container)) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE warehouse_sold SET fulfilled = TRUE WHERE id = :id");
                if ($stmt->execute(['id' => $sold_id])) {
                    $this->logAction('fulfil', ['sold_id' => $sold_id]);
                    return true;
                }
            } else {
                throw new Exception("{$this->fnsku} kodlu ürünü {$container->name} isimli raf/koliden çıkartırken bir hata oluştu.");
            }
        } else {
            throw new Exception("{$this->fnsku} kodlu ürünle ilgili satış kaydı bulunamadı.");
        }
    }

    public function addSoldItem($description)
    {
        if (empty($description) || !is_string($description)) {
            throw new Exception("Invalid description. Must be a valid string");
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO warehouse_sold (product_id, description) VALUES (:product_id, :description)");
        if ($stmt->execute(['product_id' => $this->id, 'description' => $description])) {
            $this->logAction('addSoldItem', ['description' => $description]);
            return true;
        }
        return false;
    }

    public function placeInContainer($container, $count = 1)
    {
        if ($container instanceof WarehouseContainer && !empty($this->id)) {
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . WarehouseAbstract::$productJoinTableName . " (product_id, container_id) VALUES (:product_id, :container_id)");
            $retval = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($stmt->execute(['product_id' => $this->id, 'container_id' => $container->id])) {
                    $retval++;
                }
            }
            if ($retval) {
                $this->logAction('placeInContainer', ['container_id' => $container->id, 'count' => $retval]);
            }
            return $retval;
        }
        return false;
    }

    public function removeFromContainer($container, $count = 1)
    {
        if ($container instanceof WarehouseContainer && !empty($this->id) && $count>0) {
            $sql = "DELETE FROM " . WarehouseAbstract::$productJoinTableName . " WHERE container_id = :container_id AND product_id = :product_id LIMIT :count";
            $stmt = $GLOBALS['pdo']->prepare($sql);
            $stmt->bindParam(':container_id', $container->id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':count', $count, PDO::PARAM_INT);
            $stmt->execute();
            if ($count = $stmt->rowCount()) {
                $this->logAction('removeFromContainer', ['container_id' => $container->id, 'count' => $count]);
                return $count;
            }
        }
        return 0;
    }

    public function moveToContainer($oldContainer, $newContainer, $count = 1)
    {
        if ($oldContainer instanceof WarehouseContainer && $newContainer instanceof WarehouseContainer && !empty($this->id) && $count>0) {
            $newCount = $this->removeFromContainer($oldContainer, $count);
            if ($newCount && $newCount<=$count) {
                return $this->placeInContainer($newContainer, $newCount);
            }
        }
        return false;
    }

}