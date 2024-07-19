<?php

require_once('warehouse.php');

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
$return_url = $_SERVER['HTTP_REFERER'] ?? './';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($action, ['add_product', 'add_container', 'set_parent', 'move_to_container', 'remove_from_container', 'place_in_container', 'update_container', 'update_product', 'delete_container', 'delete_product', 'fulfil', 'add_sold_item'])) {
    header('Location: ./');
    exit;
}
/*
if (empty($action) || empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    addMessage('Geçersiz işlem: token!');
    header("Location: $return_url");
    exit;
}
*/
error_log("Action: $action, User: ".username($_SESSION['user_id']));

switch($action) {
    case 'add_product':
        handleAddProduct();
        break;
    case 'add_container':
        handleAddContainer();
        break;
    case 'set_parent':
        handleSetParent();
        break;
    case 'move_to_container':
        handleMoveToContainer();
        break;
    case 'remove_from_container':
        handleRemoveFromContainer();
        break;
    case 'place_in_container':
        handlePlaceInContainer();
        break;
    case 'update_container':
        handleUpdateContainer();
        break;
    case 'update_product':
        handleUpdateProduct();
        break;
    case 'delete_container':
        handleDeleteContainer();
        break;
    case 'delete_product':
        handleDeleteProduct();
        break;
    case 'fulfil':
        handleFulfil();
        break;
    case 'fulfil_box':
        handleFulfilBox();
        break;
    case 'fulfil_box_update':
    case 'fulfil_box_delete':
        handleFulfilBoxUpdateDelete();
        break;
    case 'fulfil_update':
    case 'fulfil_delete':
        handleFulfilUpdateDelete();
        break;
    case 'add_sold_box':
        handleAddSoldBox();
        break;
    case 'add_sold_item':
        handleAddSoldItem();
        break;
    case 'product_info':
        handleProductInfo();
        break;
    case 'product_log':
        handleProductLog();
        break;
    case 'barcode_scan':
        handleBarcodeScan();
        break;
    case 'flush_box':
        handleFlushBox();
        break;
    case 'container_info':
        handleContainerInfo();
        break;
    case 'handle_log':
        WarehouseLogger::handleLog(getPostValue('offset'));
        break;
    case 'empty_containers':
        header('Content-Type: text/html');
        die(WarehouseContainer::getEmptyContainers(ajax:true));
    case 'user_info':
        header('Content-Type: text/html');
        die(WarehouseLogger::getUserLogs(true));
    case 'containers_in_ship':
        header('Content-Type: text/html');
        die(WarehouseContainer::getContainersInShip(true));
    case 'clear_cache':
        WarehouseAbstract::clearAllCache();
        addMessage('Önbellek temizlendi');
        break;
    case 'permission_view_add':
    case 'permission_manage_add':
    case 'permission_order_add':
    case 'permission_process_add':
    case 'permission_view_remove':
    case 'permission_manage_remove':
    case 'permission_order_remove':
    case 'permission_process_remove':
        handlePermissionChange($action);
        break;
    default:
        addMessage('Geçersiz işlem!');
        break;
}
header("Location: $return_url");
exit;

function handlePermissionChange($action) {
    $actions = explode('_', $action);
    $permType = $actions[1];
    if (!in_array($permType, ['view', 'manage', 'order', 'process'])) {
        addMessage("PermissionChange Geçersiz parametre: $action");
        return;
    }
    $addRemove = $actions[2];
    if (!in_array($addRemove, ['add', 'remove'])) {
        addMessage("PermissionChange Geçersiz parametre: $action");
        return;
    }
    $target_id = getPostValue('target_id');
    if (empty($target_id)) {
        addMessage('Geçersiz parametre: target_id');
        return;
    }
    if (!is_array($target_id)) {
        $target_id = [$target_id];
    }
    $userList = slackUsers();
    $channelList = slackChannels();
    loadPermissions();
    $sql = ($addRemove === 'add') ? 'INSERT INTO warehouse_user (user_id, permission) VALUES (:user_id, :permission)' : 'DELETE FROM warehouse_user WHERE user_id = :user_id AND permission = :permission';
    $stmt = $GLOBALS['pdo']->prepare($sql);

    $flag = false;
    foreach ($target_id as $id) {
        if ($permType === 'view') {
            if (!isset($channelList[$id]) || ($addRemove === 'remove' && !in_array($id, $GLOBALS['permissions']['view_channels']))) {
                continue;
            }
            $flag = $flag || $stmt->execute(['user_id' => $id, 'permission' => 'view']);
        } else {
            if (!isset($userList[$id]) || ($addRemove === 'remove' && !in_array($id, $GLOBALS['permissions'][$permType]))) {
                continue;
            }
            $flag = $flag || $stmt->execute(['user_id' => $id, 'permission' => $permType]);
        }
    }
    if ($flag) {
        addMessage('Yetkilendirme değişiklikleri kaydedildi');
        WarehouseLogger::logAction('permissionChange', ['permissionAction' => $action, 'target_id' => $target_id]);
    } else {
        addMessage('Yetkilendirme değişiklikleri kaydedilemedi');
    }
}

function handleAddProduct() { //add_product
    $product = WarehouseProduct::addNew([
        'name' => getPostValue('name'),
        'fnsku' => getPostValue('fnsku'),
        'category' => getPostValue('category'),
        'iwasku' => getPostValue('iwasku'),
        'serial_number' => getPostValue('serial_number'),
        'dimension1' => getPostValue('dimension1'),
        'dimension2' => getPostValue('dimension2'),
        'dimension3' => getPostValue('dimension3'),
        'weight' => getPostValue('weight'),
    ]);
    if ($product) {
        addMessage(getPostValue('name')." ürün kataloğuna eklendi");
    } else {
        addMessage(getPostValue('name')." eklenemedi");
    }
}

function handleAddContainer() { //add_container
    $container = WarehouseContainer::addNew([
        'name' => getPostValue('name'),
        'type' => getPostValue('type'),
        'warehouse' => getPostValue('warehouse'),
        'parent_id' => getPostValue('parent_id'),
    ]);
    if ($container) {
        addMessage(getPostValue('name') . " " . getPostValue('type') . " olarak eklendi");
    } else {
        addMessage(getPostValue('name') . " eklenemedi");
    }
}

function handleFlushBox() { //flush_box
    // if parent is not set, move products to parent of the box
    $parent = WarehouseContainer::getById(getPostValue('parent_id'));
    $container_id = getPostValue('container_id');
    if (!is_array($container_id)) {
        if (!is_numeric($container_id)) {
            addMessage('flush_box: Geçersiz parametre: container_id');
            return;
        }
        $container_id = [$container_id];
    }
    foreach ($container_id as $cid) {
        $container = WarehouseContainer::getById($cid);
        $flush_to = $parent ?? $container->parent;
        if (!$container || !$flush_to) {
            continue;
        }
        foreach ($container->getProducts() as $product) {
            $product->moveToContainer($container, $flush_to, $product->getInContainerCount($container), true);
        }
        if ($container->delete()) {
            addMessage("{$container->name} içindeki ürünler {$flush_to->name} altına taşındı ve {$container->name} silindi");
        } else {
            addMessage("$container->name silinemedi");
        }
    }
}

function ultraTrim($inText) {
    // trim all characters other than numbers and -
    return preg_replace('/[^0-9-]/', '', $inText);
}

function handleSetParent() { //set_parent
    $parent = WarehouseContainer::getById(getPostValue('parent_id'));
    if (!$parent) {
        addMessage('set_parent: Geçersiz parametre: parent');
        return;
    }
    /*
    // TEMPORARY SOLUTION
    $tempTextArea = getPostValue('tempTextArea');
    if (!empty($tempTextArea)) {
        $container_names = explode("\n", $tempTextArea);
        error_log("tempTextArea detected: ".json_encode($container_names));
        foreach ($container_names as $container_name) {
            $container = WarehouseContainer::getByField('name', ultraTrim($container_name));
            if ($container) {
                $old_parent = $container->parent;
                if ($old_parent->type !== 'Gemi') {
                    addMessage("Koli $container->name, $old_parent->name rafında olduğu için $parent->name altına taşınmadı!");
                } else {
                    if ($container->setParent($parent)) {
                        addMessage("Konteyner $container->name, $old_parent->name rafından $parent->name altına taşındı");
                    } else {
                        addMessage("Konteyner $container->name, $parent->name altına taşınamadı");
                    }
                }
            } else {
                addMessage("Konteyner $container_name bulunamadı");
            }
        }
        return;
    } 
    */
    $container_id = getPostValue('container_id');
    if (!is_array($container_id)) {
        if (empty($container_id)) {
            addMessage('set_parent: Geçersiz parametre: container_id');
            return;
        }
        $container_id = [$container_id];
    }
    foreach ($container_id as $cid) {
        $container = WarehouseContainer::getById(trim($cid));
        if (!$container) {
            error_log("Invalid container: $cid");
            continue;
        }
        if ($container->setParent($parent)) {
            addMessage("Konteyner $container->name, $parent->name altına taşındı");
        } else {
            addMessage("Konteyner $container->name, $parent->name altına taşınamadı");
        }
    }
}

function handleMoveToContainer() { //move_to_container
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $old_container = WarehouseContainer::getById(getPostValue('container_id'));
    $new_container = WarehouseContainer::getById(getPostValue('new_container_id'));
    $count = getPostValue('count');
    if (!$product || !$old_container || !$new_container || !is_numeric($count) || $count < 1) {
        addMessage('move_to_container: Geçersiz parametre: '.getPostValue('product_id').'/'.getPostValue('container_id').'/'.getPostValue('new_container_id').'/'.getPostValue('count'));
        return;
    }
    if ($product->moveToContainer($old_container, $new_container, $count)) {
        addMessage("$count adet $product->name, $old_container->name => $new_container->name taşındı");
    } else {
        addMessage("$count adet $product->name, $old_container->name => $new_container->name taşınamadı");
    }
}

function handleRemoveFromContainer() { //remove_from_container
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    if (!$product || !$container || !getPostValue('count')) {
        addMessage('remove_from_container: Geçersiz parametre!');
        return;
    }
    if (($realcount = $product->getInContainerCount($container)) < getPostValue('count')) {
        addMessage("$product->name, $container->name raf/kolisinde $realcount adet bulunmaktadır, ".getPostValue('count')." adet işlem talep edilmiştir");
        return;
    }
    if ($product->removeFromContainer($container, getPostValue('count'))) {
        addMessage("$product->name, $container->name raf/kolisinden alındı");
    } else {
        addMessage("$product->name, $container->name raf/kolisinden alınamadı");
    }
}

function handlePlaceInContainer() { //place_in_container
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $container = WarehouseContainer::getById(getPostValue('new_container_id'));
    if (!$product || !$container || !getPostValue('count')) {
        addMessage('place_in_container: Geçersiz parametre!');
        return;
    }
    if ($product->placeInContainer($container, getPostValue('count'))) {
        addMessage("$product->name, $container->name raf/kolisine yerleştirildi");
    } else {
        addMessage("$product->name, $container->name raf/kolisine yerleştirilemedi");
    }
}

function handleUpdateContainer() { //update_container
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    if (!$container) {
        addMessage('update_container: Geçersiz parametre!');
        return;
    }
    if ($container->update([
        'name' => getPostValue('name'),
        'type' => getPostValue('type'),
        'warehouse' => getPostValue('warehouse'),
        'parent_id' => getPostValue('parent_id'),
    ])) {
        addMessage("$container->name güncellendi");
    } else {
        addMessage("$container->name güncellenemedi");
    }
}

function handleUpdateProduct() { //update_product
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    if (!$product) {
        addMessage('update_product: Geçersiz parametre!');
        return;
    }
    if ($product->update([
        'name' => getPostValue('name'),
        'fnsku' => getPostValue('fnsku'),
        'category' => getPostValue('category'),
        'iwasku' => getPostValue('iwasku'),
        'serial_number' => getPostValue('serial_number'),
        'dimension1' => getPostValue('dimension1'),
        'dimension2' => getPostValue('dimension2'),
        'dimension3' => getPostValue('dimension3'),
        'weight' => getPostValue('weight'),
    ])) {
        addMessage("$product->name güncellendi");
    } else {
        addMessage("$product->name güncellenemedi");
    }
}

function handleDeleteContainer() { // delete_container
    $container_id = getPostValue('container_id');
    if (!is_array($container_id)) {
        if (empty($container_id)) {
            addMessage('delete_container: Geçersiz parametre: container_id');
            return;
        }
        $container_id = [$container_id];
    }
    foreach ($container_id as $cid) {
        $container = WarehouseContainer::getById($cid);
        if (!$container) {
            continue;
        }
        if ($container->getChildren(noCache:true)) {
            addMessage("$container->name içinde alt koliler bulunmaktadır, silinemedi");
            return;
        }
        if ($container->getProducts(noCache:true)) {
            addMessage("$container->name içinde ürün bulunmaktadır, silinemedi");
            return;
        }
        if ($container->delete()) {
            addMessage("$container->name silindi");
        } else {
            addMessage("$container->name silinemedi");
        }
    }
}

function handleDeleteProduct() { // delete_product
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    if (!$product) {
        addMessage('delete_product: Geçersiz parametre!');
        return;
    }
    if ($product->delete()) {
        addMessage("$product->name silindi");
    } else {
        addMessage("$product->name silinemedi");
    }
}

function handleFulfil() { // fulfil
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $soldItem = WarehouseSold::getById(getPostValue('sold_id'));
    if (!$product || !$container || !$soldItem || !empty($soldItem->fulfilled_at)) {
        addMessage('fulfil: Geçersiz parametre!');
        return;
    }
    if ($soldItem->fulfil($product, $container)) {
        addMessage("$product->name için sipariş çıkışı yapıldı");
    } else {
        addMessage("$product->name için sipariş çıkışı yapılamadı");
    }
}

function handleAddSoldItem() { // add_sold_item
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $description = getPostValue('description');
    if (!$product || empty($description) || !is_string($description)) {
        addMessage('add_sold_item: Geçersiz parametre!');
        return;
    }
    WarehouseSold::addNewSoldItem($product, $description);
    addMessage("$product->name için yeni satış kaydı eklendi");
}

function handleFulfilBox() { // fulfil_box
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $soldItem = WarehouseSold::getById(getPostValue('sold_id'));
    if (!$container || !$soldItem || !empty($soldItem->fulfilled_at)) {
        addMessage('fulfil_box: Geçersiz parametre!');
        return;
    }
    $soldItem->fulfil($container);
    addMessage("$container->name için koli çıkışı yapıldı");
}

function handleFulfilBoxUpdateDelete() { // fulfil_box_update fulfil_box_delete
    $soldItem = WarehouseSold::getById(getPostValue('sold_id'));
    if (!$soldItem || $soldItem->fulfilled_at || $soldItem->deleted_at) {
        addMessage('fulfil_box_update_delete: Geçersiz parametre: sold_id!');
        return;
    }
    if ($_POST['action'] === 'fulfil_box_update') {
        $description = getPostValue('description');
        if (empty($description) || !is_string($description)) {
            addMessage('fulfil_box_update: Geçersiz parametre: description!');
            return;
        }
        if ($soldItem->update(['description' => $description])) {
            addMessage("Koli açıklaması güncellendi");
        } else {
            addMessage("Koli açıklaması güncellenemedi");
        }
    } elseif ($_POST['action'] === 'fulfil_box_delete') {
        if ($soldItem->delete()) {
            addMessage("Koli sipariş kaydı silindi");
        } else {
            addMessage("Koli sipariş kaydı silinemedi");
        }
    }
}

function handleFulfilUpdateDelete() { // fulfil_update fulfil_delete
    $soldItem = WarehouseSold::getById(getPostValue('sold_id'));
    if (!$soldItem || $soldItem->fulfilled_at || $soldItem->deleted_at) {
        addMessage('fulfil_update_delete: Geçersiz parametre: sold_id!');
        return;
    }
    if ($_POST['action'] === 'fulfil_update') {
        $description = getPostValue('description');
        if (empty($description) || !is_string($description)) {
            addMessage('fulfil_update: Geçersiz parametre: description!');
            return;
        }
        if ($soldItem->update(['description' => $description])) {
            addMessage("Sipariş açıklaması güncellendi");
        } else {
            addMessage("Sipariş açıklaması güncellenemedi");
        }
    } elseif ($_POST['action'] === 'fulfil_delete') {
        if ($soldItem->delete()) {
            addMessage("Sipariş kaydı silindi");
        } else {
            addMessage("Sipariş kaydı silinemedi");
        }
    }
}

function handleAddSoldBox() { // add_sold_box
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $description = getPostValue('description');
    if (!$container || empty($description) || !is_string($description) || $container->deleted_at) { // || $container->isPreviouslyOrdered() ) {
        addMessage('add_sold_box: Geçersiz parametre!', 'alert-danger');
        return;
    }
    WarehouseSold::addNewSoldItem($container, $description);
    addMessage("$container->name için yeni satış eklendi");
}

function handleProductInfo() { // product_info
    header('Content-Type: application/json');
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    if (!$product) {
        die(json_encode([
            'error' => 'Ürün bilgisi bulunamadı.',
        ]));
    }
    $retval = $product->getAsArray();
    if ($container) {
        $retval['container'] = $container->name;
        $retval['stock'] = $product->getInContainerCount($container);
    } else {
        $retval['container'] = containerOptGrouped($product);
    }
    $retval['info'] = productInfo($product);
    die(json_encode($retval));
}

function handleProductLog() { // product_log
    header('Content-Type: application/json');
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    if (!$product) {
        die(json_encode([
            'error' => 'Ürün bilgisi bulunamadı.',
        ]));
    }
    $logs = WarehouseLogger::findLogs(['product_id' => $product->id]);
    $logjson = [];
    foreach ($logs as $log) {
        switch ($log->action) {
            case 'addNew':
                $tr = [
                    "Katalog",
                    "{$log->data['name']} ($log->data['fnsku']) ürünü kataloğa eklendi",
                ];
                break;
            case 'removeFromContainer':
                $container = WarehouseContainer::getById($log->data['container_id']);
                $container_name = $container ? $container->name : 'Bilinmeyen';
                $tr = [
                    "Çıkış",
                    "{$log->data['count']} adet \"{$log->object->name}\" \"{$container_name}\" rafından alındı",
                ];
                break;
            case 'placeInContainer':
                $container = WarehouseContainer::getById($log->data['container_id']);
                $container_name = $container ? $container->name : 'Bilinmeyen';
                $tr = [
                    "Giriş",
                    "{$log->data['count']} adet \"{$log->object->name}\" \"{$container_name}\" rafına yerleştirildi",
                ];
                break;
            case 'addSoldItem':
                $tr = [
                    "Sipariş",
                    "\"{$log->object->name}\" için sipariş kaydı oluşturuldu (".substr($log->data['description'], 0, 10)."...)",
                ];
                break;
            case 'fulfilSoldItem':
                $tr = [
                    "Sipariş",
                    "\"{$log->object->name}\" siparişinin çıkışı yapıldı",
                ];
                break;
            case 'deleteSoldItem':
                $tr = [
                    "Sipariş",
                    "{$log->object->name} için sipariş kaydı silindi (".substr($log->data['description'], 0, 10)."...)",
                ];
                break;
            case 'updateSoldItem':
                $tr = [
                    "Sipariş",
                    "{$log->object->name} için sipariş kaydı güncellendi (".substr($log->data['description'], 0, 10)."...)",
                ];
                break;
            case 'delete':
                $tr = [
                    "Katalog",
                    "{$log->object->name} ürünü katalogdan silindi",
                ];
                break;
            case 'update':
                $tr = [
                    "Katalog",
                    "{$log->object->name} ürünü güncellendi",
                ];
                break;
        }
        $tr[] = $log->username();
        $tr[] = $log->created_at;
        $logjson[] = $tr;
    }
    die(json_encode([
        'info' => productInfo($product),
        'log' => $logjson,    
    ], JSON_PRETTY_PRINT));
}

function handleBarcodeScan() { // 
    header('Content-type: application/json');
    $product = WarehouseProduct::getByField('fnsku', getPostValue('fnsku'));
    if (!$product) {
        die(json_encode([
            'error' => 'Ürün bilgisi bulunamadı.',
        ]));
    }
    die(json_encode($product->getAsArray()));
}

function handleContainerInfo() { //container_info
    header('Content-Type: application/json');
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    if (!$container) {
        die(json_encode([
            'error' => 'Konteyner bilgisi bulunamadı.',
        ]));
    }
    $data = $container->getAsArray();
    $data['info'] = containerInfo($container);
    die(json_encode($data));
}

function getPostValue($key, $default = null, $filter = []) {
    $retval = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (is_array($filter)) {
        foreach ($filter as $f) {
            switch ($f) {
                case 'int':
                    $retval = intval($retval);
                    break;
                case 'float':
                    $retval = floatval($retval);
                    break;
                case 'string':
                    $retval = strval($retval);
                    break;
                case 'array':
                    $retval = (array)$retval;
                    break;
                case 'bool':
                    $retval = boolval($retval);
                    break;
                case 'html':
                    $retval = htmlspecialchars($retval);
                    break;
                case 'trim':
                    $retval = trim($retval);
                    break;
                case 'strip':
                    $retval = strip_tags($retval);
                    break;
                case 'notnull':
                    if (is_null($retval)) {
                        $retval = $default;
                    }
                    break;
            }
        }
    }
    return $retval;
}