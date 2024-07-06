<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once('warehouse.php');

$action = $_POST['action'] ?? null;
$token = $_POST['csrf_token'] ?? null;

if (empty($action) || empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    addMessage('Geçersiz işlem!');
    header("Location: $return_url");
    exit;
}

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
    case 'product_info':
        handleProductInfo();
        break;
    case 'container_info':
        handleContainerInfo();
        break;
    default:
        addMessage('Geçersiz işlem!');
        break;
}
header("Location: $return_url");
exit;

function getPostValue($key, $default = null, $filter = []) {
    $retval = $_POST[$key] ?? $default;
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
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    $parent = WarehouseContainer::getById(getPostValue('parent_id'));
    if (!$container || !$parent) {
        addMessage('set_parent: Geçersiz parametre!');
        return;
    }
    if ($container->setParent($parent)) {
        addMessage("Konteyner $container->name, $parent->name altına taşındı");
    } else {
        addMessage("Konteyner $container->name, $parent->name altına taşınamadı");
    }
}

function handleMoveToContainer() {
    $product = WarehouseProduct::getById(getPostValue('product_id'));
    $old_container = WarehouseContainer::getById(getPostValue('old_container_id'));
    $new_container = WarehouseContainer::getById(getPostValue('new_container_id'));
    if (!$product || !$old_container || !$new_container) {
        addMessage('move_to_container: Geçersiz parametre!');
        return;
    }
    if ($product->moveToContainer($old_container, $new_container, getPostValue('quantity'))) {
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
    $container = WarehouseContainer::getById(getPostValue('container_id'));
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

function handleProductInfo() {
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
    }
    die(json_encode($retval));
}

function handleContainerInfo() {
    $container = WarehouseContainer::getById(getPostValue('container_id'));
    if (!$container) {
        die(json_encode([
            'error' => 'Konteyner bilgisi bulunamadı.',
        ]));
    }
    die(json_encode($container->getAsArray()));
}
