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
    protected function getTransientFields(): array
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
    protected function validateField($field, $value): bool
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
     * @throws Exception If a database error occurs.
     */
    public static function getByFnsku(string $fnsku, PDO $db)
    {
        try {
            $stmt = $db->prepare("SELECT * FROM " . static::$tableName . " WHERE fnsku = :fnsku");
            $stmt->execute(['fnsku' => $fnsku]);
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $instance = new static($data['id'], $db);
                $instance->cachedData = $data;
                return $instance;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            error_log("Database error in getByFnsku: " . $e->getMessage());
            throw new Exception("Failed to retrieve product by FNSKU.");
        }
    }

    /**
     * Get the shelves containing this product.
     *
     * @return array The list of shelves.
     * @throws Exception If a database error occurs.
     */
    public function getShelves(): array
    {
        if (!empty($this->shelvesArray)) {
            return $this->shelvesArray;
        }
        try {
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
        } catch (PDOException $e) {
            error_log("Database error in getShelves: " . $e->getMessage());
            throw new Exception("Failed to retrieve shelves containing the product.");
        }
    }

    /**
     * Get the total count of this product across all shelves.
     *
     * @return int The total count.
     * @throws Exception If a database error occurs.
     */
    public function getTotalStock(): int
    {
        if (isset($this->totalStock)) {
            return $this->totalStock;
        }
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM wh_shelf_product WHERE product_id = :product_id");
            $stmt->execute(['product_id' => $this->id]);
            $this->totalStock = (int) $stmt->fetchColumn();
            return $this->totalStock;
        } catch (PDOException $e) {
            error_log("Database error in getTotalStock: " . $e->getMessage());
            throw new Exception("Failed to retrieve total stock.");
        }
    }

    /**
     * Put product on a shelf.
     * 
     * @param StockShelf $shelf The shelf object.
     * @param int $count The number of products to put on the shelf.
     * @param bool $log Whether to log the action.
     * @return bool True if successful, false otherwise.
     * @throws Exception If a database error occurs.
     */
    public function putOnShelf(StockShelf $shelf, int $count = 1, bool $log = true): bool
    {
        if ($log) $this->logAction(func_get_args());
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO wh_shelf_product (product_id, shelf_id) VALUES (:product_id, :shelf_id)");
            for ($i = 0; $i < $count; $i++) {
                $stmt->execute(['product_id' => $this->id, 'shelf_id' => $shelf->id]);
            }
            $this->db->commit();
            $this->shelvesArray = []; // Invalidate cached shelves
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in putOnShelf: " . $e->getMessage());
            throw new Exception("Failed to put product on shelf.");
        }
    }

    /**
     * Remove product from a shelf.
     * 
     * @param StockShelf $shelf The shelf object.
     * @param bool $log Whether to log the action.
     * @return bool True if successful, false otherwise.
     * @throws Exception If a database error occurs.
     */
    public function removeFromShelf(StockShelf $shelf, bool $log = true): bool
    {
        if ($log) $this->logAction(func_get_args());
        if ($this->shelfCount($shelf)) {
            try {
                $stmt = $this->db->prepare("DELETE FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id LIMIT 1");
                $this->shelvesArray = []; // Invalidate cached shelves
                return $stmt->execute(['product_id' => $this->id, 'shelf_id' => $shelf->id]);
            } catch (PDOException $e) {
                error_log("Database error in removeFromShelf: " . $e->getMessage());
                throw new Exception("Failed to remove product from shelf.");
            }
        }
        return false;
    }

    /**
     * Move product from one shelf to another.
     * 
     * @param StockShelf $fromShelf The source shelf object.
     * @param StockShelf $toShelf The destination shelf object.
     * @return bool True if successful, false otherwise.
     * @throws Exception If a database error occurs.
     */
    public function moveBetweenShelves(StockShelf $fromShelf, StockShelf $toShelf): bool
    {
        $this->logAction(func_get_args());
        if ($this->removeFromShelf($fromShelf, log:false)) {
            return $this->putOnShelf($toShelf, log:false);
        }
        return false;
    }

    /**
     * Get product count in a shelf.
     * 
     * @param StockShelf $shelf The shelf object.
     * @return int The count of the product in the specified shelf.
     * @throws Exception If a database error occurs.
     */
    public function shelfCount(StockShelf $shelf): int
    {
        if (empty($this->countArray[$shelf->id])) {
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id");
                $stmt->execute(['product_id' => $this->id, 'shelf_id' => $shelf->id]);
                $this->countArray[$shelf->id] = (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("Database error in shelfCount: " . $e->getMessage());
                throw new Exception("Failed to retrieve shelf count.");
            }
        }
        return $this->countArray[$shelf->id];
    }

    /**
     * Get product information as a formatted string.
     * 
     * @return string The product information.
     */
    public function productInfo(): string
    {
        return "Ürün Adı: {$this->name}\n".
                    "Ürün Kodu: {$this->fnsku}\n".
                    "Kategori: {$this->category}\n".
                    "Ölçüler (metrik): {$this->dimension1}x{$this->dimension2}x{$this->dimension3}cm, {$this->weight}gr\n".
                    "Ölçüler (imperial): ".metricToImp($this->dimension1)."x".metricToImp($this->dimension2)."x".metricToImp($this->dimension3)."inch, ".metricToImp($this->weight,0.0352739619)."oz\n".
                    "Toplam Stok: {$this->getTotalStock()}\n";
    }

    /**
     * Get all products.
     * 
     * @param PDO $db The database connection.
     * @return array The list of all products.
     * @throws Exception If a database error occurs.
     */
    public static function allProducts(PDO $db): array
    {
        try {
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
        } catch (PDOException $e) {
            error_log("Database error in allProducts: " . $e->getMessage());
            throw new Exception("Failed to retrieve all products.");
        }
    }
}
