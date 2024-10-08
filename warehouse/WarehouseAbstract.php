<?php


abstract class WarehouseAbstract 
{
    protected static $productJoinTableName = 'warehouse_container_product';
    protected static $containerSignatureTableName = 'warehouse_view_container_signatures';
    protected static $containerTableName = 'warehouse_container';
    protected static $productTableName = 'warehouse_product';

    public $id = null;
    protected $dbValues = [];
    static protected $instances = [];
    protected static $allObjects = [];
    static protected $predis = null;

    public function __construct($id = null, $data = [])
    {
        $this->id = $id;
        $this->setDbValues($data);
        if ($id) {
            static::addInstance($id, $this);
        }
    }

    public static function getCache($key)
    {
        if (is_null(static::$predis)) {
            static::$predis = new Predis\Client();
        }
        return static::$predis->get($key);
    }

    public static function setCache($key, $value)
    {
        if (is_null(static::$predis)) {
            static::$predis = new Predis\Client();
        }
        return static::$predis->set($key, $value);
    }

    protected static function addInstance($id, $instance)
    {
        $class = get_called_class();
        if (!isset(static::$instances[$class])) {
            static::$instances[$class] = [];
        }
        static::$instances[$class][$id] = $instance;
    }

    protected static function getInstance($id, $class=null)
    {
        if (is_null($class)) {
            $class = get_called_class();
        }
        if (!in_array($class, ['WarehouseContainer','WarehouseProduct'])) {
            throw new Exception("Class not found in getInstance");
        }
        if (isset(static::$instances[$class][$id])) {
            return static::$instances[$class][$id];
        }
        return null;
    }

    public static function getById($id)
    {
        if (empty($id)) {
            return null;
        }
        $instance = static::getInstance($id);
        if ($instance) {
            return $instance;
        }
        return static::getByField('id', $id, false);
    }

    public static function getByField($field, $value, $check=true)
    {
        if (empty($value)) {
            return null;
        }
        if ($check && !in_array($field, static::getDBFields())) {
            throw new Exception("Field not found in database fields");
        }
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE " . $field . " = :" . $field ." LIMIT 1");
        $stmt->execute([$field => $value]);
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = static::getInstance($data['id']);
            if ($instance) {
                return $instance;
            }
            return new static($data['id'], $data);
        }
        return null;
    }

    public function save()
    {
        if ($this->id) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    protected function update()
    {
        if (!$this->validate()) {
            throw new Exception("Validation failed for ".get_called_class()."->{$this->id}");
        }
        $fields = static::getDBFields();
        $set = [];
        $values['id'] = $this->id;
        foreach ($fields as $field) {
            if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            $values[$field] = $this->$field;
            $set[] = "$field  = :$field";
        }
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . static::getTableName() . " SET " . implode(', ', $set) . " WHERE id = :id");
        static::clearAllCache();
        return $stmt->execute($values);    
    }

    protected function insert()
    {
        if (!$this->validate(false)) {
            return false;
        }
        $fields = static::getDBFields();
        $set = [];
        $values = [];
        foreach ($fields as $field) {
            $set[] = $field;
            $values[$field] = $this->$field;
            if (in_array($field, ['dimension1', 'dimension2', 'dimension3', 'weight']) && !is_numeric($values[$field])) {
                $values[$field] = '0';
            }
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . static::getTableName() . " (" . implode(', ', $set) . ") VALUES (:" . implode(', :', $set) . ")");
        if ($stmt->execute($values)) {
            $this->id = $GLOBALS['pdo']->lastInsertId();
            static::addInstance($this->id, $this);
            $this->clearAllCache();
            return true;
        }
        return false;
    }

    public static function clearAllCache()
    {
        if (is_null(static::$predis)) {
            static::$predis = new Predis\Client();
        }
        return static::$predis->flushdb();
    }

    protected function validate($checkId = true)
    {
        if ($checkId && empty($this->id)) {
            return false;
        }
        $fields = static::getDBFields();
        foreach ($fields as $field) {
            if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if (!$this->validateField($field, $this->$field)) {
                return false;
            }
        }
        return true;
    }

    protected function setDbValues($data)
    {
        $this->dbValues=[];
        foreach ($data as $field=>$value) {
            if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if ($this->validateField($field, $value)) {
                $this->dbValues[$field] = $value;
            }
        }
    }

    protected function getField($field)
    {
        if ($field === 'warehouse') { //hack solution for now
            return '';
        }
        if (!in_array($field, static::getDBFields())) {
            throw new Exception("Field not found in database fields");
        }
        if (isset($this->dbValues[$field])) {
            return $this->dbValues[$field];
        }
        if (empty($this->id)) {
            return null;
        }
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $this->id]);
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->setDbValues($data);
            return $data[$field];
        }
        return null;
    }

    public function __get($field)
    {
        if ($field === 'warehouse') { //hack solution for now
            return '';
        }
        if (in_array($field, static::getDBFields())) {
            if (isset($this->dbValues[$field])) {
                return $this->dbValues[$field];
            }
            return $this->getField($field);
        }
        $methods = static::getCustomMethods();
        if (isset($methods[$field])) {
            $methodName = $methods[$field];
            return $this->$methodName();
        }
        throw new Exception("Field $field not defined in class ".get_called_class());
    }

    public function __set($field, $value)
    {
        if (in_array($field, static::getDBFields())) {
            if ($this->validateField($field, $value)) {
                $this->dbValues[$field] = $value;
                return;
            } else {
                throw new Exception("Invalid value ($value) for field $field");
            }
        }
        $methods = static::getCustomMethods();
        if (isset($methods[$field]) && ($value === [] || is_null($value))) {
            $this->$field = $value;
        }
    }

    public function delete()
    {
        if ($this->canDelete()) {
            $stmt = $GLOBALS['pdo']->prepare("UPDATE " . static::getTableName() . " SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            static::clearAllCache();
            if ($stmt->execute([$this->id])) {
                WarehouseLogger::logAction('delete', ['id'=>$this->id], $this);
                return true;
            }
            return false;
        }
        throw new Exception("Cannot delete object");
    }

    protected static function getDbFields()
    {
        $class = get_called_class();
        if (empty($class::$dbFields)) {
            $class::$dbFields = [];
            $stmt = $GLOBALS['pdo']->prepare("SHOW COLUMNS FROM " . $class::getTableName());
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (in_array($row['Field'], ['id', 'created_at', 'updated_at'])) {
                    continue;
                }
                $class::$dbFields[] = $row['Field'];
            }            
        }
        return $class::$dbFields;
    }

    public static function getLogs($action, $data)
    {
        if (!is_array($data)) {
            $data = [];
        }
        return WarehouseLogger::findLogs(array_merge(['action'=>$action], $data));
    }

    public static function addNew($data)
    {
        error_log("Adding new ".get_called_class());
        $instance = new static(null, $data);
        if ($instance->save()) {
            WarehouseLogger::logAction('addNew', $data, $instance);
            $instance->clearAllCache();
            return $instance;
        }
        return null;
    }

    public function getAsArray()
    {
        $json = [];
        $json['id'] = $this->id;
        $json['class'] = get_called_class();
        $json = array_merge($json, $this->dbValues);
        return $json;
    }

    public static function getAll()
    {
        if (empty(static::$allObjects)) {
            $cache = unserialize(static::getCache(get_called_class()."getAll"));
            if (is_array($cache)) {
                static::$allObjects = $cache;
            } else {
                $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName(). " WHERE deleted_at IS NULL ORDER BY name ASC");
                $stmt->execute();
                $objects = [];
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $instance = static::getInstance($data['id']);
                    if (!$instance) {
                        $instance = new static($data['id'], $data);
                    }
                    $objects[] = $instance;
                }
                static::$allObjects = $objects;
                static::setCache(get_called_class()."getAll", serialize(static::$allObjects));
            }
        }
        return static::$allObjects;
    }

    abstract protected static function getCustomMethods();
    abstract public static function getTableName();
    abstract protected static function validateField($field, $value);
    abstract protected function canDelete();
}