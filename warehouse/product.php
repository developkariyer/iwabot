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

    <div class="accordion mb-3" id="mainAccordion">
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
                                    <p><?= productInfo($product['product']) ?></p>
                                    <p><b>Açıklama</b><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                                    <form action="controller.php" method="post">
                                        <input type="hidden" name="product_id" value="<?= $product['product']->id ?>">
                                        <input type="hidden" name="action" value="fulfil">
                                        <input type="hidden" name="sold_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <select id="Select<?= $index ?>Product<?= $product['product']->id ?>" name="container_id" class="form-select btn-outline-success rounded-pill w-100 py-3">
                                            <option value="">Raf/Koli Seçin</option>
                                            <?= containerOptGrouped($product['product']) ?>
                                        </select>
                                        <button id="Submit<?= $index ?>Product<?= $product['product']->id ?>" type="submit" class="btn btn-success btn-lg rounded-pill w-100 py-3 mt-2" disabled>Ürün Çıkışı Yap</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($unfulfilledProducts) || count($unfulfilledProducts) == 0): ?>
                        <p>Çıkış için bekleyen ürün bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion mb-3" id="mainAccordion2">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion2" aria-expanded="true" aria-controls="productAccordion2">
                    <span><strong>Çıkış İçin Bekleyen Ürünler (<?= count($unfulfilledProducts) ?> adet)</strong></span>
                </button>
            </h2>
            <div id="productAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain" data-bs-parent="#mainAccordion2">
                <div class="accordion-body p-0">
                    <?= productSelect() ?>
                </div>
            </div>
        </div>
    </div>


    <?= wh_menu() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php foreach ($unfulfilledProducts as $index => $product): ?>
        const select<?= $index ?>Element = document.getElementById('Select<?= $index ?>Product<?= $product['product']->id ?>');
        const submit<?= $index ?>Button = document.getElementById('Submit<?= $index ?>Product<?= $product['product']->id ?>');

        select<?= $index ?>Element.addEventListener('change', function () {
            if (select<?= $index ?>Element.value) {
                submit<?= $index ?>Button.disabled = false;
            } else {
                submit<?= $index ?>Button.disabled = true;
            }
        });
    <?php endforeach; ?>
});
</script>

<?php

include '../_footer.php';
