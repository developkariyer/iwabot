<?php

require_once('_login.php');
require_once('_init.php');

if (!isset($_POST['barcode'])) {
    echo json_encode([
        'productInfo' => 'Barkod numarası yok.',
        'stock' => 0
    ]);
}

$shelf = $_POST['shelf'] ?? '';

function productInfoArray($product, $shelf) {

    $stmt = $GLOBALS['pdo']->prepare('SELECT count(*) FROM wh_shelf_product WHERE product_id = :product_id');
    $stmt->execute(['product_id' => $product['id']]);
    $totalStock = $stmt->fetchColumn();

    if (!empty($shelf)) {
        $stmt = $GLOBALS['pdo']->prepare('SELECT count(*) FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id');
        $stmt->execute(['product_id' => $product['id'], 'shelf_id' => $shelf]);
        $stock = $stmt->fetchColumn();
    } else {
        $stock = 0;
    }

    return [
        'productInfo' => "Ürün Adı: {$product['name']}".
                        "<br>Ürün Kodu: {$product['fnsku']}".
                        "<br>Kategori: {$product['category']}".
                        "<br>Ölçüler (metrik): {$product['dimension1']}x{$product['dimension2']}x{$product['dimension3']}cm, {$product['weight']}gr".
                        "<br>Toplam Stok: $totalStock".
                        "<br>Raf Stok: $stock",
        'stock' => $stock,
    ];
}

$barcode = $_POST['barcode'];

$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM wh_product WHERE fnsku = :fnsku LIMIT 1');
$stmt->execute(['fnsku' => $barcode]);

if ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(productInfoArray($product, $shelf));
} else {
    echo json_encode([
        'productInfo' => 'Ürün bilgisi bulunamadı.',
        'stock' => 0]
    );
}

