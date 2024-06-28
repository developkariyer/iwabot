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
        İşlem yapmak için lütfen aşağıdaki raf ve kolilerden birini seçin.
        <div class="g-3 m-3 mt-5">
            <?php foreach ($product->getShelves() as $shelf): ?>
                <a href="wh_product_action.php?product=<?= $product->fnsku ?>&shelf=<?= $shelf->id ?>" class="btn btn-outline-primary rounded-pill w-100 btn-lg py-3 m-1">
                    <?php
                        if ($shelf->type === 'Raf') {
                            echo "{$shelf->name} rafında {$product->shelfCount($shelf)} açık ürün";
                        } else {
                            echo "{$shelf->parent->name} rafında {$shelf->name} kolisinde {$product->shelfCount($shelf)} adet ürün. {$shelf->type}";
                        }
                    ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="row g-3 m-3 mt-5">
        <div class="col-md-3">
        </div>
        <div class="col-md-6">
            <a href="#" id="put_to_shelf" class="btn btn-outline-primary btn-lg rounded-pill w-100 py-3">Ürünü Rafa Yerleştir</a>
        </div>
    </div>

    <?= wh_menu() ?>
</div>

<?php

include '_footer.php';
?>
