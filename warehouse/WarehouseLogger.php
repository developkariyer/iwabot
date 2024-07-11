<?php

class WarehouseLogger
{
    protected static $logTableName = 'warehouse_log';
    public $id = null;
    public $action = null;
    public $data = [];
    private $created_at = null;
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

    public function __get($field)
    {
        if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
            if (empty($this->$field)) {
                return null;
            }
            $timezone = 'Europe/Istanbul';
            if (isset($_COOKIE['timezone'])) {
                $timezone = $_COOKIE['timezone'];
            }
            $date = new DateTime($this->$field);
            $date->setTimezone(new DateTimeZone($timezone));
            return $date->format('Y-m-d H:i:s T');

        }
        return $this->$field || null;
    }

    public static function getLogCount()
    {
        $stmt = $GLOBALS['pdo']->prepare("SELECT count(*) as count FROM " . self::$logTableName);
        $stmt->execute();
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $data['count'];
        }
        return 0;
    }

    public static function findLogs($filter = [], $limit = 0, $offset = 0)
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
                case 'class':
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
        $sql .= " ORDER BY created_at DESC, id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        $stmt = $GLOBALS['pdo']->prepare($sql);
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

    public function aciklama()
    {
        switch($this->action) {
            case 'placeInContainer':
                $container_id = $this->data['container_id'];
                if ($container = WarehouseContainer::getById($container_id)) {
                    $container_name = $container->name;
                    $container_type = $container->type === 'Raf' ? 'rafına' : 'kolisine';
                } else {
                    $container_name = 'Bilinmeyen';
                    $container_type = 'yerine';
                }
                return "{$this->data['count']} adet <strong>{$this->object->name}</strong> ürünü <strong>{$container_name}</strong> $container_type yerleştirildi";
            case 'removeFromContainer':
                $container_id = $this->data['container_id'];
                if ($container = WarehouseContainer::getById($container_id)) {
                    $container_name = $container->name;
                    $container_type = $container->type === 'Raf' ? 'rafından' : 'kolisinden';
                } else {
                    $container_name = 'Bilinmeyen';
                    $container_type = 'yerine';
                }
                return "{$this->data['count']} adet <strong>{$this->object->name}</strong> ürünü <strong>{$container_name}</strong> $container_type alındı";
            case 'setParent':
                $newContainer = WarehouseContainer::getById($this->data['new_parent_id']);
                $oldContainer = WarehouseContainer::getById($this->data['old_parent_id']);
                $newContainerName = $newContainer ? $newContainer->name : 'Bilinmeyen';
                $oldContainerName = $oldContainer ? $oldContainer->name : 'Bilinmeyen';
                return "<strong>{$this->object->name}</strong> kolisi <strong>{$oldContainerName}</strong> rafından <strong>{$newContainerName}</strong> rafına taşındı";
            case 'fulfilSoldItem':
                return 'Sipariş karşılanma';
            case 'addSoldItem':
                $tip = ($this->object instanceof WarehouseProduct) ? "ürünü" : "kolisi";
                return "<strong>{$this->object->name}</strong> $tip için yeni sipariş kaydı girildi.";
            case 'deleteSoldItem':
                $tip = ($this->object instanceof WarehouseProduct) ? "ürün" : "koli";
                return "<strong>{$this->object->name}</strong> için $tip sipariş kaydı silindi.";
            case 'permissionChange':
                $permissionList = [
                    'manage' => 'IWA Depo Yönetme',
                    'order' => 'Sipariş Oluşturma',
                    'process' => 'Depo İşletme',
                    'view' => 'Envanter Görüntüleme',
                ];
                $permParam = explode('_', $this->data['permissionAction']);
                $permType = $permParam[1];
                $permAction = $permParam[2];
                $content = "<strong>";
                $content.= ($permType === 'view') ? channelIdToName($this->data['target_id'][0])."</strong> kanalı" : username($this->data['target_id'][0])."</strong> kullanıcısı";
                $content.= " için <strong>{$permissionList[$permType]}</strong> yetkisi ";
                $content.= ($permAction === 'add') ? 'verildi' : 'kaldırıldı';
                return $content;
            case 'addNew':
                if ($this->object) {
                    return (get_class($this->object) === 'WarehouseProduct') ? "<strong>{$this->object->name}</strong> ürünü eklendi" : "<strong>{$this->object->name}</strong> kolisi eklendi";
                }
                return 'Yeni ürün/koli eklendi';
            default:
                return 'Bilinmeyen işlem';
        }
    }

    public static function handleLog($offset)
    {
        header('Content-Type: text/html');
        $logStep = 20;
        $logCount = self::getLogCount();
        $content = '<nav aria-label="Page navigation example">';
        $content.= '    <ul class="pagination">';
        $content.= '        <li class="page-item"><button class="page-link" onclick="loadLogs(0)">&lt;&lt;</button></li>';
        $content.= '        <li class="page-item ' . ($offset ? '' : 'disabled') . '"><button class="page-link" onclick="loadLogs(' . max(0, $offset-$logStep) . ')">&lt;</button></li>';
        $content.= '        <li class="page-item ' . ($logCount<=$offset+$logStep ? 'disabled' : '') . '"><button class="page-link" onclick="loadLogs(' . ($offset+$logStep) . ')">&gt;</button></li>';
        $content.= '        <li class="page-item ' . ($logCount<=$offset ? 'disabled' : '') . '"><button class="page-link" onclick="loadLogs(' . (floor($logCount / $logStep) * $logStep) . ')">&gt;&gt;</button></li>';
        $content.= '    </ul>';
        $content.= '</nav>';
        $content.= '<table class="table table-striped-columns table-sm table-hover">';
        $content.= '    <thead>';
        $content.= '        <tr class="table-dark">';
        $content.= '            <th scope="col">#</th>';
        $content.= '            <th scope="col">İşlem</th>';
        $content.= '            <th scope="col">Açıklama</th>';
        $content.= '            <th scope="col">Kullanıcı</th>';
        $content.= '            <th scope="col">Zaman</th>';
        $content.= '        </tr>';
        $content.= '    </thead>';
        $content.= '    <tbody>';
        if ($logs = self::findLogs([], 20, $offset)) {
            foreach ($logs as $logIndex=>$log) {
                $content.= '        <tr>';
                $content.= '            <td>' . $log->id . '</td>';
                $content.= '            <td>' . htmlspecialchars($log->action) . '</td>';
                $content.= '            <td>' . $log->aciklama() . '</td>';
                $content.= '            <td>' . htmlspecialchars($log->username()) . '</td>';
                $content.= '            <td>' . htmlspecialchars($log->__get('created_at')) . '</td>';
                $content.= '        </tr>';
            }
        } else {
            $content.= '        <tr>';
            $content.= '            <td colspan="5" class="text-center">İşlem kaydı bulunmamaktadır.</td>';
            $content.= '        </tr>';
        }
        $content.= '    </tbody>';
        $content.= '</table>';
        die($content);
   }

}