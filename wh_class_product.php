<?php

/**
 * Class representing a product stock item.
 */
class StockProduct extends AbstractStock
{
    protected static $tableName = 'wh_product';
    private $shelvesArray = [];

    /**
     * Get the list of transient fields.
     *
     * @return array The list of transient fields.
     */
    protected function getTransientFields()
    {
        return ['shelfCount', 'totalStock'];
    }
    
    /**
     * Validate a field value.
     *
     * @param string $field The field name.
     * @param mixed $value The field value.
     * @return bool True if valid, false otherwise.
     */
    protected function validateField($field, $value)
    {
        switch ($field) {
            case 'name':
                return is_string($value) && strlen($value) <= 255;
            case 'category':
            case 'fnsku':
                return is_string($value) && strlen($value) <= 100;
            case 'dimension1':
            case 'dimension2':
            case 'dimension3':
            case 'weight':
                return is_numeric($value);
            default:
                return false;
        }
    }

    /**
     * Get a product by FNSKU.
     *
     * @param string $fnsku The FNSKU.
     * @param PDO $db The database connection.
     * @return static|null The product instance or null if not found.
     */
    public static function getByFnsku($fnsku, $db)
    {
        $stmt = $db->prepare("SELECT * FROM " . static::$tableName . " WHERE fnsku = :fnsku");
        $stmt->execute(['fnsku' => $fnsku]);
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new static($data['id'], $db);
            $instance->cachedData = $data;
            return $instance;
        } else {
            return null;
        }
    }

    /**
     * Get the shelves containing this product.
     *
     * @return array The list of shelves.
     */
    public function getShelves()
    {
        if (!empty($this->shelvesArray)) {
            return $this->shelvesArray;
        }
        $stmt = $this->db->prepare("
            SELECT s.*, sp.shelf_id, COUNT(sp.shelf_id) as shelf_count
            FROM wh_shelf_product sp
            JOIN wh_shelf s ON s.id = sp.shelf_id
            WHERE sp.product_id = :product_id
            GROUP BY sp.shelf_id
        ");
        $stmt->execute(['product_id' => $this->id]);
        $shelves = [];
        while ($shelfData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $shelf = new StockShelf($shelfData['id'], $this->db, $shelfData['parent_id']);
            $shelf->cachedData = $shelfData;
            $shelf->count = $shelfData['shelf_count'];
            $shelves[$shelfData['shelf_id']] = $shelf;
        }
        $this->shelvesArray = $shelves;
        return $shelves;
    }

    /**
     * Get the total count of this product across all shelves.
     *
     * @return int The total count.
     */
    public function getTotalStock()
    {
        if ($this->totalStock) {
            return $this->totalStock;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM wh_shelf_product WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $this->id]);
        $this->totalStock = $stmt->fetchColumn();
        return $this->totalStock;
    }

    /**
     * Put product on a shelf.
     * 
     * @param int $shelfId The shelf ID.
     */
    public function putOnShelf(StockShelf $shelf, $count = 1)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO wh_shelf_product (product_id, shelf_id) VALUES (:product_id, :shelf_id)");
            for ($i = 0; $i < $count; $i++) {
                $stmt->execute(['product_id' => $this->id, 'shelf_id' => $shelf->id]);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        $this->shelvesArray = [];
    }

    /**
     * Remove product from a shelf.
     * 
     * @param int $shelfId The shelf ID.
     * @return bool True if successful, false otherwise.
     */
    public function removeFromShelf(StockShelf $shelf): bool
    {
        if (isset($this->shelves[$shelf->id])) {
            $stmt = $this->db->prepare("DELETE FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id");
            $this->shelvesArray = [];
            return $stmt->execute(['product_id' => $this->id, 'shelf_id' => $shelf->id]);
        }
        return false;
    }

    /**
     * Move product from one shelf to another
     * 
     * @param int $fromShelfId The source shelf ID.
     * @param int $toShelfId The destination shelf ID.
     */
    public function moveBetweenShelves(StockShelf $fromShelf, StockShelf $toShelf)
    {
        if ($this->removeFromShelf($fromShelf)) {
            $this->putOnShelf($toShelf);
        }
    }

}
