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
        return WarehouseAbstract::$productTableName;
    }

    protected static function getCustomMethods()
    {
        return [
            'containers' => 'getContainers',
        ];
    }

    public static function getAllCategorized()
    {
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE deleted_at IS NULL ORDER BY category, name");
        $stmt->execute();
        $products = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($products[$data['category']])) {
                $products[$data['category']] = [];
            }
            $products[$data['category']][] = new static($data['id'], $data);
        }
        return $products;
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
            $cache = unserialize(static::getCache("Product{$this->id}Containers"));
            if (is_array($cache)) {
                $this->containers = $cache;
            } else {
                $stmt = $GLOBALS['pdo']->prepare("SELECT DISTINCT container_id FROM " . WarehouseAbstract::$productJoinTableName . " WHERE deleted_at IS NULL AND product_id = :product_id");
                $stmt->execute(['product_id' => $this->id]);
                $containers = [];
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $container = WarehouseContainer::getById($data['container_id']);
                    if ($container->parent && $container->parent->type === 'Gemi') {
                        continue;
                    }
                    $containers[] = $container;
                }
                $this->containers = $containers;
                static::setCache("Product{$this->id}Containers", serialize($this->containers));
            }
        }
        return $this->containers;
    }

    private function getInContainerCountFromDb($container)
    {
        $stmt = $GLOBALS['pdo']->prepare("SELECT count(*) as count FROM " . WarehouseAbstract::$productJoinTableName . " WHERE deleted_at IS NULL AND product_id = :product_id AND container_id = :container_id");
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
            $stmt = $GLOBALS['pdo']->prepare("SELECT container_id, count(*) as count FROM " . WarehouseAbstract::$productJoinTableName . " WHERE deleted_at IS NULL AND product_id = :product_id GROUP BY container_id"); 
            $stmt->execute(['product_id' => $this->id]);
            $totalCount = 0;
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $container = WarehouseContainer::getById($data['container_id']);
                if ($container) {
                    if ($container->parent && $container->parent->type === 'Gemi') {
                        continue;
                    }
                    $totalCount += $data['count'];
                }
            }
            $this->totalCount = $totalCount;
            $category = $GLOBALS['pdo']->quote($this->category);
            $GLOBALS['pdo']->query("INSERT INTO warehouse_product_totals (product_id, category, total_count) 
                    VALUES ({$this->id}, $category, $totalCount) ON DUPLICATE KEY 
                    UPDATE category=$category, total_count = $totalCount ");
        }
        return $this->totalCount;
    }

    public function getAsArray()
    {
        $retval = parent::getAsArray();
        $retval['total'] = $this->getTotalCount();
        return $retval;
    }

    public function checkCompatibility($object)
    {
        if ($object instanceof WarehouseProduct) {
            return true;
        }
        return false;
    }


    /* ACTION METHODS BELOW */

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
                WarehouseLogger::logAction('placeInContainer', ['container_id' => $container->id, 'count' => $retval], $this);
                static::clearAllCache();
            }
            return $retval;
        }
        return false;
    }

    public function removeFromContainer($container, $count = 1, $noCheck = false)
    {
        if ($container instanceof WarehouseContainer && !empty($this->id) && $count>0) {
            if (!$noCheck && $container->type === 'Koli') {
                addMessage('Koli içerisinden ürün çıkartılamaz. Önce koliyi boşaltın!', 'alert-danger');
                return 0;
            }
            $sql = "UPDATE " . WarehouseAbstract::$productJoinTableName . " SET deleted_at = NOW() WHERE deleted_at IS NULL AND container_id = :container_id AND product_id = :product_id LIMIT $count";
            $stmt = $GLOBALS['pdo']->prepare($sql);
            $stmt->bindParam(':container_id', $container->id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            if ($count = $stmt->rowCount()) {
                WarehouseLogger::logAction('removeFromContainer', ['container_id' => $container->id, 'count' => $count], $this);
                static::clearAllCache();
                return $count;
            }
        }
        return 0;
    }

    public function moveToContainer($oldContainer, $newContainer, $count = 1, $noCheck = false)
    {
        error_log("Moving $count items from {$oldContainer->name} to {$newContainer->name}");
        if ($oldContainer instanceof WarehouseContainer && $newContainer instanceof WarehouseContainer && !empty($this->id) && $count>0) {
            $newCount = $this->removeFromContainer($oldContainer, $count, $noCheck);
            error_log("  Removed $newCount items from {$oldContainer->name}");
            if ($newCount && $newCount<=$count) {
                if ($this->placeInContainer($newContainer, $newCount)) {
                    error_log("  Placed $newCount items in {$newContainer->name}");
                    return true;
                }
            }
        }
        return false;
    }

}