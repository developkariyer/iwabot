<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once('warehouse.php');

$action = $_POST['action'] ?? null;
$product_id = $_POST['product_id'] ?? null;
$container_id = $_POST['container_id'] ?? null;
$old_container_id = $_POST['old_container_id'] ?? null;
$new_container_id = $_POST['new_container_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$name = $_POST['name'] ?? null;
$fnsku = $_POST['fnsku'] ?? null;
$category = $_POST['category'] ?? null;
$iwasku = $_POST['iwasku'] ?? null;
$serial_number = $_POST['serial_number'] ?? null;
$dimension1 = $_POST['dimension1'] ?? null;
$dimension2 = $_POST['dimension2'] ?? null;
$dimension3 = $_POST['dimension3'] ?? null;
$weight = $_POST['weight'] ?? null;
$type = $_POST['type'] ?? null;
$parent_id = $_POST['parent_id'] ?? null;
$warehouse = $_POST['warehouse'] ?? null;
$return_url = $_SERVER['HTTP_REFERER'] ?? './';

switch ($action) {
    case 'add_product':
        $product = WarehouseProduct::addNew([
            'name' => $name,
            'fnsku' => $fnsku,
            'category' => $category,
            'iwasku' => $iwasku,
            'serial_number' => $serial_number,
            'dimension1' => $dimension1,
            'dimension2' => $dimension2,
            'dimension3' => $dimension3,
            'weight' => $weight,
        ]);
        if ($product) {
            addMessage("$name ürün kataloğuna eklendi");
        } else {
            addMessage("$name eklenemedi");
        }
        break;
    case 'add_container':
        $container = WarehouseContainer::addNew([
            'name' => $name,
            'type' => $type,
            'warehouse' => $warehouse,
            'parent_id' => $parent_id,
        ]);
        if ($container) {
            addMessage("$name $type olarak eklendi");
        } else {
            addMessage("$name eklenemedi");
        }
    case 'set_parent':
        $container = WarehouseContainer::getById($container_id);
        $parent = WarehouseContainer::getById($parent_id);
        if (!$container || !$parent) {
            addMessage('');
        }
    default:
        addMessage('Geçersiz işlem!');
        break;
}
header("Location: $return_url");
exit;
