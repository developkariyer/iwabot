<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: wh_shelf_search.php');
    exit;
}

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$shelfId = $_POST['shelf_id'] ?? '';
$action = $_POST['action'] ?? '';
$newShelfId = $_POST['new_shelf_id'] ?? '';

$referrer = $_SERVER['HTTP_REFERER'] ?? 'wh_shelf_search.php';

$shelf = StockShelf::getById($shelfId, $GLOBALS['pdo']);

if (!$shelf) {
    addMessage('Geçersiz veri', 'danger');
    header("Location: $referrer");
    exit;
}

switch ($action) {
    case 'delete_shelf':
        if ($shelf) {
            if (empty($shelf->getChildren())) {
                if (empty($shelf->getProducts())) {
                    if ($shelf->delete()) {
                        addMessage('Raf/koli başarıyla silindi', 'success');
                    } else {
                        addMessage('Raf/koli silinemedi', 'danger');
                    }
                } else {
                    addMessage('Rafın/kolinin altında ürünler var, önce ürünleri taşımalısınız', 'danger');
                }
            } else {
                addMessage('Rafın/kolinin altında raflar/koliler var, önce onları taşımalı veya silmelisiniz', 'danger');
            }
        } else {
            addMessage('Geçersiz raf', 'danger');
        }
        break;
    case 'move_box_to_shelf':
        $newShelf = StockShelf::getById($newShelfId, $GLOBALS['pdo']);
        if ($shelf) {
            if ($newShelf) {
                if ($newShelf->moveBoxToShelf($shelf)) {
                    addMessage('Koli başarıyla taşındı', 'success');
                } else {
                    addMessage('Koli taşınamadı', 'danger');
                }
            } else {
                addMessage('Geçersiz raf', 'danger');
            }
        } else {
            addMessage('Geçersiz koli', 'danger');
        }
        break;
    default:
        addMessage('Geçersiz işlem', 'danger');
        break;
}

header("Location: $referrer");
exit;