<?php

require_once('warehouse.php');

if (!userCan(['view'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

$icon = [
    'Gemi' => '🚢', //\u{1F6A2}
    'Raf' => '🗄️', // \u{1F5C4}
    'Koli' => '📦', //\u{1F4E6}
];

include '../_header.php';

// Fetch raf containers
$rafContainers = WarehouseContainer::getContainers('Raf');

// Fetch all products
$categories = WarehouseProduct::getAllCategorized();

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Envanter Yönetimi</h1>
        <p>Depo envanterini görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">
        
        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#inventoryAccordion2" aria-expanded="false" aria-controls="inventoryAccordion2">
                    <span><strong>Ürün Bilgisine Göre Envanter</strong></span>
                </button>
            </h2>
            <div id="inventoryAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <?php foreach ($categories as $category => $products): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingCategory<?= $category ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCategory<?= $category ?>" aria-expanded="false" aria-controls="collapseCategory<?= $category ?>">
                                    <span><strong><?= htmlspecialchars($category) ?></strong></span>
                                </button>
                            </h2>
                            <div id="collapseCategory<?= $category ?>" class="accordion-collapse collapse" aria-labelledby="headingCategory<?= $category ?>" data-bs-parent="#inventoryAccordion2">
                                <div class="accordion-body">
                                    <?php foreach ($products as $index => $product): ?>
                                        <?php if ($product->getTotalCount() == 0) continue; ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingProduct<?= $category . $index ?>">
                                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProduct<?= $category . $index ?>" aria-expanded="false" aria-controls="collapseProduct<?= $category . $index ?>">
                                                    <span><strong><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</strong> (Toplam: <?= $product->getTotalCount() ?> adet)</span>
                                                </button>
                                            </h2>
                                            <div id="collapseProduct<?= $category . $index ?>" class="accordion-collapse collapse" aria-labelledby="headingProduct<?= $category . $index ?>" data-bs-parent="#collapseCategory<?= $category ?>">
                                                <div class="accordion-body">
                                                    <p>
                                                        <?= productInfo($product) ?>
                                                    </p>
                                                    <h4>Ürünün Bulunduğu Raflar ve Koli Bilgileri</h4>
                                                    <ul>
                                                        <?= empty($product->getContainers()) ? "<p>Bu ürün hiçbir raf veya koli içinde bulunmamaktadır.</p>" : "" ?>
                                                        <?php foreach ($product->getContainers() as $container): ?>
                                                            <li><?= $icon[$container->type] ?> <?= $container->name ?> (<?= $container->type === 'Raf' ? 'Rafta açık' : $container->parent->name ?>) (<?= $product->getInContainerCount($container) ?> adet)</li>
                                                        <?php endforeach; ?>
                                                    </ul>
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
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

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
                        <?php if (count($raf->getChildren()) == 0 && count($raf->getProducts()) == 0) continue; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRaf<?= $index ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRaf<?= $index ?>" aria-expanded="false" aria-controls="collapseRaf<?= $index ?>">
                                    <span><strong><?= $icon['Raf'] ?> <?= htmlspecialchars($raf->name) ?></strong> (<?= count($raf->getChildren()) ?> koli, <?= count($raf->getProducts()) ?> açık ürün)</span>
                                </button>
                            </h2>
                            <div id="collapseRaf<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingRaf<?= $index ?>" data-bs-parent="#inventoryAccordion1">
                                <div class="accordion-body">
                                    <?= containerInfo($raf) ?><br>
                                    <strong>Raftaki Koliler:</strong><br>
                                    <?php foreach ($raf->getChildren() as $childIndex => $child): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header box-h2" id="headingChild<?= $index ?>-<?= $childIndex ?>">
                                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChild<?= $index ?>-<?= $childIndex ?>" aria-expanded="false" aria-controls="collapseChild<?= $index ?>-<?= $childIndex ?>">
                                                    <span><strong><?= $icon['Koli'] ?> <?= htmlspecialchars($child->name) ?> (<?= htmlspecialchars($child->getTotalCount()) ?> ürün)</strong></span>
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

    </div>

    <hr>

    <?= wh_menu() ?>
</div>
<script>
$(document).ready(function() {
    $('#inventoryAccordion2').on('shown.bs.collapse', function () {
        $('#filterInput2').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#inventoryAccordion2 .accordion-item').each(function() {
                var headerText = $(this).find('.accordion-header').text().toLowerCase();
                if (value === "") {
                    $(this).hide();
                } else {
                    $(this).toggle(headerText.indexOf(value) > -1);
                }
            });
        });
    });
});
</script>

<?php

include '../_footer.php';
