<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$productId = $_POST['product'] ?? '';
if ($productId) {
    error_log("Getting product info for $productId");
    $product = StockProduct::getByFnsku($productId, $GLOBALS['pdo']);
}

error_log("Product info: $productId");

if (empty($product)) {
    die(json_encode([
        'productInfo' => 'Ürün bilgisi bulunamadı.',
        'stock' => 0
    ]));
}

$shelfId = $_POST['shelf'] ?? '';
if ($shelfId) {
    $shelf = StockShelf::getById($shelfId, $GLOBALS['pdo']);
} else {
    $shelf = null;
}

$retval = [
    'productId' => $product->id,
    'productInfo' => $product->productInfo(),
];

if ($shelf) {
    $stock = $product->shelfCount($shelf);
    $retval['productInfo'].= "<br>Raf Mevcudu: {$shelf->name} rafında $stock adet";
    $retval['stock'] = $stock;
} else {
    $retval['stock'] = 0;
}

echo json_encode($retval);
