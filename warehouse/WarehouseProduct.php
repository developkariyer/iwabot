<?php

use Exception;
use PDO;

require_once('WarehouseAbstract.php');
require_once('WarehouseContainer.php');

class WarehouseProduct extends WarehouseAbstract
{
    protected array $containers = [];
    protected static $dbFields = [];
    public $inContainerCount = [];
    private $totalCount = 0;

    public static function getTableName()
    {
        return 'warehouse_product';
    }

    protected function getCustomMethods()
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

    private function getContainers()
    {
        if (empty($this->id)) {
            return [];
        }
        if (empty($this->containers)) {
            $stmt = $GLOBALS['db']->prepare("SELECT container_id FROM " . WarehouseAbstract::$productJoinTableName . " WHERE product_id = :product_id");
            $stmt->execute(['product_id' => $this->id]);
            $containers = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $containers[] = WarehouseContainer::getById($data['container_id']);
            }
            $this->containers = $containers;
        }
        return $this->containers;
    }

    public static function addNew($data)
    {
        $product = new WarehouseProduct(null, $data);
        if ($product->save()) {
            $product->logAction('addNew', $data);
            return $product;
        }
        return null;
    }

    public function placeInContainer($container, $count = 1)
    {
        if ($container instanceof WarehouseContainer && !empty($this->id)) {
            $stmt = $GLOBALS['db']->prepare("INSERT INTO " . WarehouseAbstract::$productJoinTableName . " (product_id, container_id) VALUES (:product_id, :container_id)");
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
            $stmt = $GLOBALS['db']->prepare($sql);
            $stmt->bindParam(':container_id', $container->id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':count', $count, PDO::PARAM_INT);
            $stmt->execute();
            if ($count = $stmt->rowCount()) {
                $this->logAction('removeFromContainer', ['container_id' => $container->id, 'count' => $count]);
                return $count;
            }
        }
        return false;
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

    private function getInContainerCountFromDb($container)
    {
        $stmt = $GLOBALS['db']->prepare("SELECT count(*) as count FROM " . WarehouseAbstract::$productJoinTableName . " WHERE product_id = :product_id AND container_id = :container_id");
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
            $stmt = $GLOBALS['db']->prepare("SELECT count(*) as count FROM " . WarehouseAbstract::$productJoinTableName . " WHERE product_id = :product_id");
            $stmt->execute(['product_id' => $this->id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->totalCount = $data['count'];
        }
        return $this->totalCount;
    }
}