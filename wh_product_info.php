<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$productId = $_GET['product'] ?? '';
if ($productId) {
    $product = StockProduct::getById($productId, $GLOBALS['pdo']);
}

if (empty($product)) {
    die(json_encode([
        'productInfo' => 'Ürün bilgisi bulunamadı.',
        'stock' => 0
    ]));
}

$shelfId = $_GET['shelf'] ?? '';
if ($shelfId) {
    $shelf = StockShelf::getById($shelfId, $GLOBALS['pdo']);
} else {
    $shelf = null;
}

function metricToImp($inp, $conv=0.393700787) {
    return number_format($inp * conv, 2);
}

$stock = $shelf ? $product->shelfCount($shelf) : 0;

return [
    'productInfo' => "Ürün Adı: {$product->name}".
                    "<br>Ürün Kodu: {$product->fnsku}".
                    "<br>Kategori: {$product->category}".
                    "<br>Ölçüler (metrik): {$product->dimension1}x{$product->dimension2}x{$product->dimension3}cm, {$product->weight}gr".
                    "<br>Ölçüler (imperial): ".metricToImp($product->dimension1)."x".metricToImp($product->dimension2)."x".metricToImp($product->dimension3)."inch, ".metricToImp($product->weight,0.0352739619)."oz".
                    "<br>Toplam Stok: {$product->getTotalStock()}".
                    "<br>Raf Stok: {$stock}",
    'stock' => $stock,
];
