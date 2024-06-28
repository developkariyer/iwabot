<?php

/**
 * Class representing a shelf in the warehouse.
 */
class StockShelf extends AbstractStock
{
    protected static $tableName = 'wh_shelf';
    private $productsArray = [];
    private $childrenArray = [];

    /**
     * Get the list of transient fields.
     *
     * @return array The list of transient fields.
     */
    protected function getTransientFields()
    {
        return ['parent', 'children', 'products'];
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
            case 'location':
                return is_string($value) && strlen($value) <= 255;
            case 'type':
                return in_array($value, ['Raf', 'Koli (Açılmış)', 'Koli (Kapalı)']);
            case 'parent_id':
                return is_numeric($value) || is_null($value);
            default:
                return false;
        }
    }

    /**
     * Get a list of shelves of a specific type.
     *
     * @param PDO $db The database connection.
     * @param string|null $type The type of shelves to retrieve.
     * @return array The list of shelves.
     */
    public static function allShelves($db, $topLevel = true)
    {
        $shelves = [];
        if ($topLevel) {
            $stmt = $db->prepare("SELECT * FROM " . static::$tableName . " WHERE parent_id IS NULL ORDER BY name ASC");
        } else {
            $stmt = $db->prepare("SELECT * FROM " . static::$tableName . " ORDER BY type DESC, name ASC");
        }
        $stmt->execute();
        if ($s = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($s as $shelf) {
                $parentId = $shelf['parent_id'] ?? null;
                $shelves[$shelf['id']] = new static($shelf['id'], $db, $parentId);
                $shelves[$shelf['id']]->cachedData = $shelf;
            }
        }
        return $shelves;
    }
    
    /**
     * Get the child shelves of this shelf
     */
    public function getChildren()
    {
        error_log("Getting children of shelf " . $this->id . " with db " . (empty($this->db)? "empty" : "not empty"));
        if (!empty($this->childrenArray)) {
            return $this->childrenArray;
        }
        $stmt = $this->db->prepare("SELECT * FROM " . static::$tableName . " WHERE parent_id = :parent_id ORDER BY name ASC");
        $stmt->execute(['parent_id' => $this->id]);
        $shelves = [];
        while ($shelfData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $shelf = new StockShelf($shelfData['id'], $this->db, $this->id);
            $shelf->cachedData = $shelfData;
            $shelves[$shelfData['id']] = $shelf;
        }
        $this->childrenArray = $shelves;
        return $shelves;
    }

    /**
     * Get the products stored on this shelf.
     *
     * @return array The list of products.
     */
    public function getProducts()
    {
        if (!empty($this->productsArray)) {
            return $this->productsArray;
        }

        $stmt = $this->db->prepare("
            SELECT p.*, sp.product_id, COUNT(sp.product_id) as shelf_count
            FROM wh_shelf_product sp
            JOIN wh_product p ON p.id = sp.product_id
            WHERE sp.shelf_id = :shelf_id
            GROUP BY sp.product_id
        ");
        $stmt->execute(['shelf_id' => $this->id]);
        $products = [];
        while ($productData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new StockProduct($productData['id'], $this->db);
            $product->cachedData = $productData;
            $product->shelfCount = $productData['shelf_count'];
            $products[$productData['product_id']] = $product;
        }
        $this->productsArray = $products;
        return $products;
    }

    /**
    * Put product on this shelf.
    *
    * @param StockProduct $product The product to put on the shelf.
    * @param int $count The count of the product to put on the shelf.
    */
    public function putProduct(StockProduct $product, $count = 1)
    {
        return $product->putOnShelf($this, $count);
    }

    /**
     * Remove product from this shelf
     * 
     * @param StockProduct $product The product to remove from the shelf.
     */
    public function removeProduct(StockProduct $product)
    {
        return $product->removeFromShelf($this);
    }

    /**
     * Move product to another shelf.
     * 
     * @param StockProduct $product The product to move.
     * @param StockShelf $shelf The shelf to move the product to.
     */
    public function moveToAnotherShelf(StockProduct $product, StockShelf $shelf)
    {
        return $product->moveBetweenShelves($this, $shelf);
    }

    /**
     * Move product to this shelf.
     * 
     * @param StockProduct $product The product to move.
     * @param StockShelf $shelf The shelf to move the product from.
     */
    public function moveToThisShelf(StockProduct $product, StockShelf $shelf)
    {
        return $product->moveBetweenShelves($shelf, $this);
    }

    public static function newShelf($db, $name, $type, $parentId = null)
    {
        if (($type === 'Raf' && $parentId) || ($type !== 'Raf' && !$parentId)) {
            return null;
        }

        if ($type !== 'Raf') {
            if (!$parentId) {
                return null;
            }
            $parent = static::getById($parentId, $db);
            if (!$parent) {
                return null;
            }
        }
        if ($type === 'Raf') {
            $parentId = null;
        }

        $stmt = $db->prepare("INSERT INTO wh_shelf (name, type, parent_id) VALUES (:name, :type, :parent_id)");
        try {
            if ($stmt->execute(['name' => $name, 'type' => $type, 'parent_id' => $parentId])) {
                $id = $db->lastInsertId();
                return static::getById($id, $db);
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }

}
