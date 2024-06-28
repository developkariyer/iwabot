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

<div class="container mt-5">
    <div class="mt-5">
        <h2><?= $product->name ?></h2>
        <p><?= $product->productInfo() ?></p>
        <p>Toplam Stok: <?= $product->getTotalStock() ?></p>

        <h3>Ürünün Bulunduğu Yerler</h3>
        <?php foreach ($product->shelves as $shelf): ?>
            <a href="wh_product_action.php?product=<?= $product->fnsku ?>&shelf=<?= $shelf->id ?>" class="btn btn-outline-primary rounded-pill w-100 py-3"></a>
        <?php endforeach; ?>
    </div>
    <?= wh_menu() ?>
</div>




<?php

include '_footer.php';