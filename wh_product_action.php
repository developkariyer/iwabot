<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: wh_product_search.php');
    exit;
}

require_once('_login.php');
require_once('_init.php');
require_once('_utils.php');
require_once('wh_include.php');

$productId = $_POST['product_id'] ?? '';
$shelfId = $_POST['shelf_id'] ?? '';
$quantity = $_POST['quantity'] ?? 0;
$newShelfId = $_POST['new_shelf_id'] ?? '';
$action = $_POST['action'] ?? '';

$referrer = $_SERVER['HTTP_REFERER'] ?? 'wh_product.php';

$product = StockProduct::getById($productId, $GLOBALS['pdo']);
$shelf = StockShelf::getById($shelfId, $GLOBALS['pdo']);

if (!$product || !$shelf) {
    addMessage('Geçersiz veri', 'danger');
    header("Location: $referrer");
    exit;
}

switch ($action) {
    case 'send_to_sale':
        if ($product->removeFromShelf($shelf)) {
            addMessage('Ürün satışa gönderildi', 'success');
        } else {
            addMessage('Ürün satışa gönderilemedi', 'danger');
        }
        break;

    case 'add_to_shelf':
        $newShelf = StockShelf::getById($newShelfId, $GLOBALS['pdo']);
        if ($newShelf && $quantity > 0) {
            if ($product->putOnShelf($newShelf, $quantity)) {
                addMessage('Ürün rafa eklendi', 'success');
            } else {
                addMessage('Ürün rafa eklenemedi', 'danger');
            }
        } else {
            addMessage("Tutarsız veri: $quantity, $newShelfId", 'danger');
        }
        break;

    case 'move_to_shelf':
        $newShelf = StockShelf::getById($newShelfId, $GLOBALS['pdo']);
        if ($newShelf) {
            $product->moveBetweenShelves($shelf, $newShelf);
            addMessage('Ürün raf taşındı', 'success');
        } else {
            addMessage('Geçersiz raf', 'danger');
        }
        break;

    default:
        addMessage("Geçersiz işlem: $action", 'danger');
}

header("Location: $referrer");
exit;
