<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

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
        <h5>Ürün Bilgileri</h5>
        <p><?= $product->productInfo() ?></p>
        <h5>Ürünün Bulunduğu Yerler</h5>
        <div class="row g-3 m-3 mt-5">
            <?php foreach ($product->getShelves() as $shelf): ?>
                <a href="wh_product_action.php?product=<?= $product->fnsku ?>&shelf=<?= $shelf->id ?>" class="btn btn-outline-primary rounded-pill w-100 py-3">
                    <?= $shelf->name ?> / <?= $shelf->type ?><?= $shelf->parent ? ' / '.$shelf->parent->name : '' ?>
                    <br>Raf Mevcudu: <?= $product->shelfCount($shelf) ?> adet
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?= wh_menu() ?>
</div>

<?php

include '_footer.php';
?>
