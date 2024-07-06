<?php

require_once('warehouse.php');

include '../_header.php';

$unfulfilledProducts = WarehouseProduct::getUnfulfilledProducts();
?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Ürün İşlemleri</h1>
        <p>İşlem yapmak istediğiniz ürünü seçiniz. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>

    <button class="btn btn-primary mb-3" data-bs-toggle="collapse" data-bs-target="#productAccordion" aria-expanded="true" aria-controls="productAccordion">
        Çıkış İçin Bekleyen Ürünler <small>(<?= count($unfulfilledProducts) ?> adet) <i>Görmek için basınız</i></small>
    </button>
    <div class="accordion collapse" id="productAccordion">
        <?php foreach ($unfulfilledProducts as $index => $product): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $index ?>">
                    <button class="accordion-button btn-success collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                        <span><strong><?= htmlspecialchars($product['product']->name) ?> (<?= htmlspecialchars($product['product']->fnsku) ?>)</strong></span>
                    </button>
                </h2>
                <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#productAccordion">
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($product['product']->getProductInfo())) ?></p>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        <a href="wh_product.php?product=<?= urlencode($product['product']->id) ?>" class="btn btn-outline-success btn-lg rounded-pill w-100 py-3 mt-2">Seç</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($unfulfilledProducts) || count($unfulfilledProducts) == 0): ?>
            <div class="accordion-item">
                <div class="accordion-body">
                    <p>Çıkış için bekleyen ürün bulunmamaktadır.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?= wh_menu() ?>
</div>
<?php

include '../_footer.php';