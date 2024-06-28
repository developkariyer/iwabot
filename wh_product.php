<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

// list all places of this product in the warehouse

$productId = $_GET['product'] ?? '';

if ($productId) {
    $product = StockProduct::getByFnsku($productId, $GLOBALS['pdo']);
} else {
    $product = null;
}

if (empty($product)) {
    addMessage("Ürün bilgisi bulunamadı. ($productId)", 'danger');
    header('Location: wh_product_search.php');
    exit;
}

include '_header.php';

?>






<?php

include '_footer.php';