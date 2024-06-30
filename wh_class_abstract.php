<?php

/**
 * Abstract class representing a generic stock item.
 * Provides common functionality for all stock item types.
 */
abstract class AbstractStock 
{
    public $id = null;
    protected static $tableName = '';
    protected $cachedData = [];
    protected $transientData = [];
    protected $lazy = false;
    protected PDO $db;
    public $parent = null;

    protected static $instances = [];

    /**
     * Constructor to initialize a stock item.
     *
     * @param int $id The ID of the stock item.
     * @param PDO $db The database connection.
     * @param int|null $parentId The parent ID, if any.
     * @param bool $lazy Whether to use lazy loading.
     */
    protected function __construct($id, $db, $parentId = null, $lazy = false)
    {
        $this->id = $id;
        $this->db = $db;
        $this->lazy = $lazy;
        if ($parentId) {
            $this->parent = static::getById($parentId, $db);
        }
    }

    /**
     * Get a stock item by ID.
     *
     * @param int $id The ID of the stock item.
     * @param PDO $db The database connection.
     * @return static|null The stock item instance or null if not found.
     */
    public static function getById($id, $db)
    {
        if (empty(static::$tableName)) {
            return null;
        }

        if (isset(self::$instances[static::class][$id])) {
            return self::$instances[static::class][$id];
        }

        $stmt = $db->prepare("SELECT * FROM " . static::$tableName . " WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $parentId = $data['parent_id'] ?? null;
            $instance = new static($id, $db, $parentId);
            $instance->cachedData = $data;
            self::$instances[static::class][$id] = $instance;
            return $instance;
        } else {
            return null;
        }
    }

    /**
     * Load the stock item data from the database.
     */
    private function load()
    {
        if (!$this->id) {
            return;
        }
        error_log("Lazy load " . static::class . " with ID " . $this->id . " and lazy " . $this->lazy);
        if ($this->lazy) {
            $stmt = $this->db->prepare("SELECT * FROM " . static::$tableName . " WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
            $this->cachedData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (isset($this->cachedData['parent_id']) && $this->cachedData['parent_id']) {
                $this->parent = static::getById($this->cachedData['parent_id'], $this->db);
            }
            $this->lazy = false;
        }
    }

    /**
     * Save the stock item to the database.
     *
     * @throws Exception If the table name is not set.
     */
    public function save(): bool
    {
        if (empty(static::$tableName)) {
            throw new Exception("Table name not set.");
        }
        if ($this->id) {
            return $this->update(static::$tableName, $this->cachedData);
        } else {
            return $this->insert(static::$tableName, $this->cachedData);
        }
    }

    /**
     * Delete the stock item from the database.
     *
     * @throws Exception If the table name is not set.
     */
    public function delete(): bool
    {
        if (empty(static::$tableName)) {
            throw new Exception("Table name not set.");
        }
        return $this->db->prepare("DELETE FROM " . static::$tableName . " WHERE id = :id")->execute(['id' => $this->id]);
    }

    /**
     * Update the stock item in the database.
     *
     * @param string $table The table name.
     * @param array $fields The fields to update.
     */
    protected function update($table, $fields): bool
    {
        $sql = "UPDATE {$table} SET ";
        $sql .= implode(',', array_map(function($field) {
            return "{$field} = :{$field}";
        }, array_keys($fields)));
        $sql .= " WHERE id = :id";
        return $this->db->prepare($sql)->execute(array_merge($fields, ['id' => $this->id]));
    }

    /**
     * Insert the stock item into the database.
     *
     * @param string $table The table name.
     * @param array $fields The fields to insert.
     */
    protected function insert($table, $fields): bool
    {
        $sql = "INSERT INTO {$table} (";
        $sql .= implode(',', array_keys($fields));
        $sql .= ") VALUES (";
        $sql .= implode(',', array_map(function($field) {
            return ":{$field}";
        }, array_keys($fields)));
        $sql .= ")";
        if ($this->db->prepare($sql)->execute($fields)) {
            $this->id = $this->db->lastInsertId();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a field value.
     *
     * @param string $field The field name.
     * @return mixed|null The field value or null if not found.
     */
    protected function getField($field)
    {
        error_log("Getting cachedData field " . $field . " from " . static::class . " with ID " . $this->id);
        $this->load();
        return $this->cachedData[$field] ?? null;
    }

    /**
     * Set a field value.
     *
     * @param string $field The field name.
     * @param mixed $value The field value.
     * @throws Exception If the value is invalid.
     */
    public function setField($field, $value)
    {
        if ($field === 'cachedData') {
            $this->cachedData = $value;
            return;
        }
        if ($this->validateField($field, $value)) {
            $this->load();
            if (isset($this->cachedData[$field])) {
                $this->cachedData[$field] = $value;
            } else {
                throw new Exception("Invalid field {$field}");
            }
        } else {
            throw new Exception("Invalid value for {$field}");
        }
    }

    /**
     * Magic getter for fields and related data.
     *
     * @param string $field The field name.
     * @return mixed The field value or related data.
     */
    public function __get($field)
    {
        error_log("Magic getter for " . $field . " from " . static::class . " with ID " . $this->id);
        if (array_key_exists($field, $this->transientData)) {
            if (isset($this->transientData[$field])) {
                return $this->transientData[$field];
            }
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if (method_exists($this, $getter)) {
                return $this->$getter();
            }
        }
        return $this->getField($field);
    }

    /**
     * Magic setter for fields.
     *
     * @param string $field The field name.
     * @param mixed $value The field value.
     * @throws Exception If the value is invalid.
     */
    public function __set($field, $value)
    {
        error_log("Magic setter for " . $field . " from " . static::class . " with ID " . $this->id);
        if (in_array($field, $this->getTransientFields())) {
            $this->transientData[$field] = $value;
        } else {
            $this->setField($field, $value);
        }
    }

    /**
     * Get all fields, including transient fields.
     *
     * @return array The combined fields.
     */
    public function getAllFields()
    {
        $this->load();
        return array_merge($this->cachedData, $this->transientData);
    }

    /**
     * Get the list of transient fields.
     *
     * @return array The list of transient fields.
     */
    protected function getTransientFields()
    {
        return [];
    }

    protected function logAction(array $args) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $method = $backtrace['function'];
        $parameters = $this->getMethodParameters($method, $args);
        $user = $_SESSION['user_id'] ?? 'unknown';
        $stmt = $this->db->prepare('INSERT INTO wh_log (user_id, operation) VALUES (?, ?)');
        $stmt->execute([$user, json_encode([
            'object' => get_called_class(),
            'id' => $this->id,
            'method' => $method,
            'parameters' => $parameters,
        ])]);
    }

    private function getMethodParameters($method, $args) {
        $reflector = new ReflectionMethod($this, $method);
        $params = $reflector->getParameters();
        $parameters = [];

        foreach ($params as $index => $param) {
            $parameters[$param->name] = $args[$index];
        }

        return $parameters;
    }

    /**
     * Validate a field value.
     *
     * @param string $field The field name.
     * @param mixed $value The field value.
     * @return bool True if valid, false otherwise.
     */
    abstract protected function validateField($field, $value);
}

