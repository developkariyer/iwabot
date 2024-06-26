<?php

/**
 * Class representing a product stock item.
 */
class StockProduct extends AbstractStock
{
    protected static $tableName = 'wh_product';
    protected $shelvesArray = [];
    protected $countArray = [];

    /**
     * Get the list of transient fields.
     *
     * @return array The list of transient fields.
     */
    protected function getTransientFields()
    {
        return ['totalStock'];
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
            $this->shelvesArray[$shelfData['id']] = $shelfData['shelf_count'];
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
    public function putOnShelf(StockShelf $shelf, $count = 1, bool $log = true)
    {
        if ($log) $this->logAction(func_get_args());
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
        return true;
    }

    /**
     * Remove product from a shelf.
     * 
     * @param int $shelfId The shelf ID.
     * @return bool True if successful, false otherwise.
     */
    public function removeFromShelf(StockShelf $shelf, bool $log = true): bool
    {
        if ($log) $this->logAction(func_get_args());
        if ($this->shelfCount($shelf)) {
            $stmt = $this->db->prepare("DELETE FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id LIMIT 1");
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
        $this->logAction(func_get_args());
        if ($this->removeFromShelf($fromShelf, log:false)) {
            return $this->putOnShelf($toShelf, log:false);
        }
        return false;
    }

    /**
     * Get product count in shelf
     * 
     * @param StockShelf $shelf The shelf
     */
    public function shelfCount(StockShelf $shelf)
    {
        if (empty($this->countArray[$shelf->id])) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id");
            $stmt->execute(['product_id' => $this->id, 'shelf_id' => $shelf->id]);
            $this->countArray[$shelf->id] = $stmt->fetchColumn();
        }
        return $this->countArray[$shelf->id];
    }

    public function productInfo() 
    {
        return "Ürün Adı: {$this->name}\n".
                    "Ürün Kodu: {$this->fnsku}\n".
                    "Kategori: {$this->category}\n".
                    "Ölçüler (metrik): {$this->dimension1}x{$this->dimension2}x{$this->dimension3}cm, {$this->weight}gr\n".
                    "Ölçüler (imperial): ".metricToImp($this->dimension1)."x".metricToImp($this->dimension2)."x".metricToImp($this->dimension3)."inch, ".metricToImp($this->weight,0.0352739619)."oz\n".
                    "Toplam Stok: {$this->getTotalStock()}\n";
    }

    public static function allProducts($db) {
        $stmt = $db->prepare("SELECT id, fnsku FROM wh_product ORDER BY fnsku");
        $stmt->execute();
        $products = [];
        while ($productData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = StockProduct::getById($productData['id'], $db);
            if ($product) {
                $products[$productData['id']] = $product;
            }
        }
        return $products;
    }
}
