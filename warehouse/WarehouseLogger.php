<?php

class WarehouseLogger
{
    protected static $logTableName = 'warehouse_log';
    public $id = null;
    public $action = null;
    public $data = [];
    public $created_at = null;
    public $object = null;

    public static function logAction($action, $data, $object = null)
    {
        if (!is_array($data)) {
            throw new Exception('logAction: Data must be an array');
        }
        $data['user'] = $_SESSION['user_id'];
        if ($object) {
            $data['class'] = get_class($object);
            $data['id'] = $object->id;
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . self::$logTableName . " (action, data) VALUES (:action, :data)");
        return $stmt->execute([
            'action' => $action,
            'data' => json_encode($data)
        ]);
    }

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->action = $data['action'];
        $this->data = json_decode($data['data'], true);
        $this->created_at = $data['created_at'];
        if (isset($this->data['class']) && isset($this->data['id']) && in_array($this->data['class'], ['WarehouseProduct', 'WarehouseContainer', 'WarehouseSold'])) {
            $this->object = $this->data['class']::getById($this->data['id']);
        }
    }

    public static function findLogs($filter = [], $limit = 0)
    {
        if (!is_array($filter)) {
            throw new Exception('findLogs: Filter must be an array');
        }
        $sql = "SELECT * FROM " . self::$logTableName;
        $where = [];
        $params = [];
        $logs = [];
        foreach ($filter as $key => $value) {
            switch($key) {
                case 'id':
                case 'action':
                    $where[] = "$key = :$key";
                    $params[$key] = $value;
                    break;
                case 'product_id':
                    $where[] = "data->>'$.id' = :$key";
                    $where[] = "data->>'$.class' = 'WarehouseProduct'";
                    $params[$key] = $value;
                    break;
                case 'container_id':
                    $where[] = "data->>'$.id' = :$key";
                    $where[] = "data->>'$.class' = 'WarehouseContainer'";
                    $params[$key] = $value;
                    break;
                case 'sold_id':
                    $where[] = "data->>'$.$key' = :$key";
                    $params[$key] = $value;
                    break;
                default:
                    error_log("findLogs: Unknown filter key $key");
                    break;
            }
        }
        if (count($where) > 0) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY created_at ASC";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $GLOBALS['pdo']->prepare($sql);
        error_log("SQL: $sql        PARAMS:".json_encode($params));
        $stmt->execute($params);
        while ($row = $stmt->fetch()) {
            if ($log = new WarehouseLogger($row)) {
                $logs[] = $log;
            }
        }        
        return $logs;
    }

    public static function findLog($filter = [])
    {
        $logs = self::findLogs($filter, limit: 1);
        return count($logs) > 0 ? $logs[0] : null;
    }

    public function username()
    {
        return username($this->data['user']);
    }

}