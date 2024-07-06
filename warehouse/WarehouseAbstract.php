<?php


abstract class WarehouseAbstract 
{
    protected static $productJoinTableName = 'warehouse_container_product';

    public $id = null;
    protected $dbValues = [];
    static protected $instances = [];

    public function __construct($id = null, $data = [])
    {
        $this->id = $id;
        $this->setDbValues($data);
        if ($id) {
            static::addInstance($id, $this);
        }
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
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE " . $field . " = :" . $field);
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
            return false;
        }
        $fields = static::getDBFields();
        $set = [];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $this->$field;
            $set[] = $field . ' = :' . $field;
        }
        $values['id'] = $this->id;
        $stmt = $GLOBALS['pdo']->prepare("UPDATE " . static::getTableName() . " SET " . implode(', ', $set) . " WHERE id = :id");
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
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . static::getTableName() . " (" . implode(', ', $set) . ") VALUES (:" . implode(', :', $set) . ")");
        if ($stmt->execute($values)) {
            $this->id = $GLOBALS['pdo']->lastInsertId();
            static::addInstance($this->id, $this);
            return true;
        }
        return false;
    }

    protected function validate($checkId = true)
    {
        if ($checkId && empty($this->id)) {
            return false;
        }
        $fields = static::getDBFields();
        foreach ($fields as $field) {
            if (!$this->validateField($field, $this->$field)) {
                return false;
            }
        }
        return true;
    }

    protected function setDbValues($data)
    {
        $this->dbValues=[];
        if (isset($data['created_at'])) {
            unset($data['created_at']);
        }
        if (isset($data['updated_at'])) {
            unset($data['updated_at']);
        }
        foreach ($data as $field=>$value) {
            if ($this->validateField($field, $value)) {
                $this->dbValues[$field] = $value;
            }
        }
    }

    protected function getField($field)
    {
        if (!in_array($field, static::getDBFields())) {
            throw new Exception("Field not found in database fields");
        }
        if (isset($this->dbValues[$field])) {
            return $this->dbValues[$field];
        }
        if (empty($this->id)) {
            return null;
        }
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM " . static::getTableName() . " WHERE id = :id");
        $stmt->execute(['id' => $this->id]);
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->setDbValues($data);
            return $data[$field];
        }
        return null;
    }

    public function __get($field)
    {
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
        throw new Exception("Field not defined in class");
    }

    public function __set($field, $value)
    {
        if (in_array($field, static::getDBFields())) {
            if ($this->validateField($field, $value)) {
                $this->dbValues[$field] = $value;
            }
            return;
        }
        $methods = static::getCustomMethods();
        if (isset($methods[$field]) && ($value === [] || is_null($value))) {
            $this->$field = $value;
        }
    }

    public function delete()
    {
        if ($this->canDelete()) {
            $stmt = $GLOBALS['pdo']->prepare("DELETE FROM " . static::getTableName() . " WHERE id = ? LIMIT 1");
            return $stmt->execute([$this->id]);
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

    protected function logAction($action, $data)
    {
        $data['id'] = $this->id;
        $data['class'] = get_called_class();
        $data['user'] = $_SESSION['user_id'] ?? 'U047D6QF19D';
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO warehouse_log (action, data) VALUES (:action, :data)");
        return $stmt->execute(['action' => $action, 'data' => json_encode($data)]);
    }

    public static function addNew($data)
    {
        $instance = new static(null, $data);
        if ($instance->save()) {
            $instance->logAction('addNew', $data);
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

    abstract protected static function getCustomMethods();
    abstract public static function getTableName();
    abstract protected static function validateField($field, $value);
    abstract protected function canDelete();
}