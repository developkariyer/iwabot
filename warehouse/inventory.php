<?php

require_once('warehouse.php');

include '../_header.php';

// Fetch raf containers
$rafContainers = WarehouseContainer::getContainers('Raf');

// Fetch all products
$products = WarehouseProduct::getAll();

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Envanter Yönetimi</h1>
        <p>Depo envanterini görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">
        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#inventoryAccordion1" aria-expanded="false" aria-controls="inventoryAccordion1">
                    <span><strong>Raf / Koli Bilgisine Göre Envanter</strong></span>
                </button>
            </h2>
            <div id="inventoryAccordion1" class="accordion-collapse collapse" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <?php foreach ($rafContainers as $index => $raf): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRaf<?= $index ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRaf<?= $index ?>" aria-expanded="false" aria-controls="collapseRaf<?= $index ?>">
                                    <span><strong><?= htmlspecialchars($raf->name) ?></strong> (<?= count($raf->getChildren()) ?> Koli, <?= count($raf->getProducts()) ?> Ürün)</span>
                                </button>
                            </h2>
                            <div id="collapseRaf<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingRaf<?= $index ?>" data-bs-parent="#inventoryAccordion1">
                                <div class="accordion-body">
                                    <?= containerInfo($raf) ?>
                                    <?php if (count($raf->getProducts())): ?>
                                        <h4>Rafta Açık Ürünler</h4>
                                        <ul>
                                            <?php foreach ($raf->getProducts() as $product): ?>
                                                <li><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <?php foreach ($raf->getChildren() as $childIndex => $child): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingChild<?= $index ?>-<?= $childIndex ?>">
                                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChild<?= $index ?>-<?= $childIndex ?>" aria-expanded="false" aria-controls="collapseChild<?= $index ?>-<?= $childIndex ?>">
                                                    <span><strong><?= htmlspecialchars($child->name) ?></strong></span>
                                                </button>
                                            </h2>
                                            <div id="collapseChild<?= $index ?>-<?= $childIndex ?>" class="accordion-collapse collapse" aria-labelledby="headingChild<?= $index ?>-<?= $childIndex ?>" data-bs-parent="#collapseRaf<?= $index ?>">
                                                <div class="accordion-body">
                                                    <?= containerInfo($child) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($raf->getChildren())): ?>
                                        <p>Bu raf altında koli bulunmamaktadır.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($rafContainers)): ?>
                        <p>Envanter bilgisi bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#inventoryAccordion2" aria-expanded="false" aria-controls="inventoryAccordion2">
                    <span><strong>Ürün Koduna Göre Envanter</strong></span>
                </button>
            </h2>
            <div id="inventoryAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <input type="text" id="filterInput2" class="form-control mb-3" placeholder="Aramak için bir şeyler yazın...">
                    <?php foreach ($products as $index => $product): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingProduct<?= $index ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProduct<?= $index ?>" aria-expanded="false" aria-controls="collapseProduct<?= $index ?>">
                                    <span><strong><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</strong></span>
                                </button>
                            </h2>
                            <div id="collapseProduct<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingProduct<?= $index ?>" data-bs-parent="#inventoryAccordion2">
                                <div class="accordion-body">
                                    <?= productInfo($product) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                        <p>Ürün bilgisi bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <hr>

    <?= wh_menu() ?>
</div>
<script>
$(document).ready(function() {
    $('#inventoryAccordion2').on('shown.bs.collapse', function () {
        // Only add the filter functionality when the accordion section is shown
        $('#filterInput2').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#inventoryAccordion2 .accordion-item').filter(function() {
                // Only search within the h2 elements of each accordion item
                var headerText = $(this).find('.accordion-header').text().toLowerCase();
                $(this).toggle(headerText.indexOf(value) > -1);
            });
        });
    });
});
</script>


<?php

include '../_footer.php';
