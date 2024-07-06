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

    <div class="accordion" id="mainAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion" aria-expanded="true" aria-controls="productAccordion">
                    <span><strong>Çıkış İçin Bekleyen Ürünler (<?= count($unfulfilledProducts) ?> adet)</strong></span>
                </button>
            </h2>
            <div id="productAccordion" class="accordion-collapse collapse" aria-labelledby="headingMain" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0">
                    <?php foreach ($unfulfilledProducts as $index => $product): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                    <span><strong><?= htmlspecialchars($product['product']->name) ?> (<?= htmlspecialchars($product['product']->fnsku) ?>)</strong></span>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#productAccordion">
                                <div class="accordion-body">
                                    <p>
                                        <?= productInfo($product['product']) ?>
                                        <b>Çıkış Yapılacak Raf/Koli Seçin:</b><br>
                                        <?php foreach ($product['product']->getContainers() as $container): ?>
                                            <li><b><?= $container->type ?></b> <?= $container->name ?>: <?= $product['product']->getInContainerCount($container) ?> adet</li>
                                        <?php endforeach; ?>
                                    </p>
                                    <p><b>Açıklama</b><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
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
            </div>
        </div>
    </div>

    <?= wh_menu() ?>
</div>

<?php

include '../_footer.php';
