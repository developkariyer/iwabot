<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: wh_product_search.php');
    exit;
}

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$productId = $_POST['product_id'] ?? '';
$shelfId = $_POST['shelf_id'] ?? '';
$quantity = $_POST['quantity'] ?? 0;
$newShelfId = $_POST['new_shelf_id'] ?? '';
$action = $_POST['action'] ?? '';

$referrer = $_SERVER['HTTP_REFERER'] ?? 'wh_product.php';

$product = StockProduct::getById($productId, $GLOBALS['pdo']);
$shelf = StockShelf::getById($shelfId, $GLOBALS['pdo']);

if (!$product) {
    addMessage('Geçersiz veri', 'danger');
    header("Location: $referrer");
    exit;
}

switch ($action) {
    case 'send_to_sale':
        if ($shelf) {
            if ($product->removeFromShelf($shelf)) {
                addMessage('Ürün satışa gönderildi', 'success');
            } else {
                addMessage('Ürün satışa gönderilemedi', 'danger');
            }
        } else {
            addMessage('Geçersiz raf', 'danger');
        }
        break;

    case 'add_to_shelf':
        if ($shelfId === 'new_shelf') {
            $newShelfName = $_POST['new_shelf_name'] ?? '';
            $newShelfType = $_POST['new_shelf_type'] ?? '';
            $parentShelfId = $_POST['parent_shelf_id'] ?? null;

            if ($newShelfName && $newShelfType) {
                $newShelf = StockShelf::newShelf($GLOBALS['db'], $newShelfName, $newShelfType, $parentShelfId);
                if ($newShelf) {
                    addMessage('Yeni raf başarıyla oluşturuldu', 'success');
                    $shelf = $newShelf;
                } else {
                    addMessage('Yeni raf oluşturulamadı', 'danger');
                }
            } else {
                addMessage('Yeni raf oluşturmak için gerekli bilgiler eksik', 'danger');
            }
        }

        if ($shelf) {
            if ($quantity > 0) {
                if ($product->putOnShelf($shelf, $quantity)) {
                    addMessage('Ürün rafa eklendi', 'success');
                } else {
                    addMessage('Ürün rafa eklenemedi', 'danger');
                }
            } else {
                addMessage("Tutarsız veri: $quantity", 'danger');
            }
        } else {
            addMessage('Geçersiz raf', 'danger');
        }
        break;

    case 'move_to_shelf':
        if ($shelf) {
            $newShelf = StockShelf::getById($newShelfId, $GLOBALS['pdo']);
            if ($newShelf) {
                if ($product->moveBetweenShelves($shelf, $newShelf)) {
                    addMessage('Ürün raf taşındı', 'success');
                } else {
                    addMessage('Ürün raf taşınamadı', 'danger');
                }
            } else {
                addMessage('Geçersiz hedef raf', 'danger');
            }
        } else {
            addMessage('Geçersiz raf', 'danger');
        }
        break;

    default:
        addMessage("Geçersiz işlem: $action", 'danger');
}

header("Location: $referrer");
exit;
?>
