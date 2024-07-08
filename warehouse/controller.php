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
    addMessage('Geçersiz işlem!');
    header("Location: $return_url");
    exit;
}
*/
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
    case 'add_sold_box':
        handleAddSoldBox();
        break;
    case 'add_sold_item':
        handleAddSoldItem();
        break;
    case 'product_info':
        handleProductInfo();
        break;
    case 'barcode_scan':
        handleBarcodeScan();
        break;
    case 'container_info':
        handleContainerInfo();
        break;
    case 'clear_cache':
        WarehouseAbstract::clearAllCache();
        addMessage('Önbellek temizlendi');
        break;
    default:
        addMessage('Geçersiz işlem!');
        break;
}
header("Location: $return_url");
exit;

function handleAddProduct() {
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
function handleAddContainer() {
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

function handleSetParent() {
    $parent = WarehouseContainer::getById(getPostValue('parent_id'));
    if (!$parent) {
        addMessage('set_parent: Geçersiz parametre: parent');
        return;
    }
    $container_id = getPostValue('container_id');
    if (!is_array($container_id)) {
        if (empty($container_id)) {
            addMessage('set_parent: Geçersiz parametre: container_id');
            return;
        }
        $container_id = [$container_id];
    }
    foreach ($container_id as $cid) {
        $container = WarehouseContainer::getById($cid);
        if (!$container) {
            continue;
        }
        if ($container->setParent($parent)) {
            addMessage("Konteyner $container->name, $parent->name altına taşındı");
        } else {
            addMessage("Konteyner $container->name, $parent->name altına taşınamadı");
        }
    }
}

function handleMoveToContainer() {
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $old_container = WarehouseContainer::getById(getPostValue('container_id'));
    $new_container = WarehouseContainer::getById(getPostValue('new_container_id'));
    $count = getPostValue('count');
    if (!$product || !$old_container || !$new_container || !is_numeric($count) || $count < 1) {
        addMessage('move_to_container: Geçersiz parametre: '.getPostValue('product_id').'/'.getPostValue('container_id').'/'.getPostValue('new_container_id').'/'.getPostValue('count'));
        return;
    }
    if ($product->moveToContainer($old_container, $new_container)) {
        addMessage("$product->name, $old_container->name => $new_container->name taşındı");
    } else {
        addMessage("$product->name, $old_container->name => $new_container->name taşınamadı");
    }
}

function handleRemoveFromContainer() {
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

function handlePlaceInContainer() {
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

function handleUpdateContainer() {
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

function handleUpdateProduct() {
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

function handleDeleteContainer() {
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    if (!$container) {
        addMessage('delete_container: Geçersiz parametre!');
        return;
    }
    if ($container->delete()) {
        addMessage("$container->name silindi");
    } else {
        addMessage("$container->name silinemedi");
    }
}

function handleDeleteProduct() {
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

function handleFulfil() {
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $soldItem = getPostValue('sold_id');
    if (!$product || !$container || empty($soldItem) || !isset(WarehouseProduct::getUnfulfilledProducts()[$soldItem])) {
        addMessage('fulfil: Geçersiz parametre!');
        return;
    }
    if ($product->fulfil($soldItem, $container)) {
        addMessage("$product->name için sipariş çıkışı yapıldı");
    } else {
        addMessage("$product->name için sipariş çıkışı yapılamadı");
    }
}

function handleAddSoldItem() {
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $description = getPostValue('description');
    if (!$product || empty($description) || !is_string($description)) {
        addMessage('add_sold_item: Geçersiz parametre!');
        return;
    }
    $product->addSoldItem($description);
    addMessage("$product->name için yeni satış eklendi");
}

function handleFulfilBox() {
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $soldItem = getPostValue('sold_id');
    if (!$container || empty($soldItem) || !isset(WarehouseContainer::getUnfulfilledBoxes()[$soldItem])) {
        addMessage('fulfil_box: Geçersiz parametre!');
        return;
    }
    $container->fulfil($soldItem);
    addMessage("$container->name için koli çıkışı yapıldı");
}

function handleAddSoldBox() {
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $description = getPostValue('description');
    if (!$container || empty($description) || !is_string($description)) {
        addMessage('add_sold_box: Geçersiz parametre!');
        return;
    }
    $container->addSoldBox($description);
    addMessage("$container->name için yeni satış eklendi");
}

function handleProductInfo() {
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

function handleBarcodeScan() {
    header('Content-type: application/json');
    $product = WarehouseProduct::getByField('fnsku', getPostValue('fnsku'));
    if (!$product) {
        die(json_encode([
            'error' => 'Ürün bilgisi bulunamadı.',
        ]));
    }
    die(json_encode($product->getAsArray()));
}

function handleContainerInfo() {
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