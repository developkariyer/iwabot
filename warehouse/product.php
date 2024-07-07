<?php

//TODO: koli taşıma/ekleme/silme/düzenleme
//TODO: ürün ekleme/silme/düzenleme
//TODO: envanter listesi
//TODO: envanter hareketleri analizi

require_once('warehouse.php');

include '../_header.php';

$unfulfilledProducts = WarehouseProduct::getUnfulfilledProducts();

$product_info=$product_containers='';
$product_id = null;
if (isset($_GET['product_id']) && !empty($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product = WarehouseProduct::getById($_GET['product_id']);
    if ($product) {
        $product_info = productInfo($product);
        if ($product_info) {
            $product_id = $product->id;
            $product_containers = containerOptGrouped($product);
        }
    }
}

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Ürün İşlemleri</h1>
        <p>İşlem yapmak istediğiniz ürünü seçiniz. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">
        <!-- First Main Accordion Item -->
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
                                        <select id="Select<?= $index ?>Product<?= $product['product']->id ?>" name="container_id" class="form-select btn-outline-success rounded-pill w-100 py-3" required>
                                            <option value="">Raf/Koli Seçin</option>
                                            <?= containerOptGrouped($product['product']) ?>
                                        </select>
                                        <button id="Submit<?= $index ?>Product<?= $product['product']->id ?>" type="submit" class="btn btn-primary rounded-pill w-100 py-3 mt-2">Ürün Çıkışı Yap</button>
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
        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white <?= $product_id ? '' : 'collapsed' ?> w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion2" aria-expanded="true" aria-controls="productAccordion2">
                    <span><strong>Kendiniz Ürün Seçin</strong></span>
                </button>
            </h2>
            <div id="productAccordion2" class="accordion-collapse collapse <?= $product_id ? 'show' : '' ?>" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0 w-100">
                    <a href="barcode.php" class="btn btn-success py-3 m-3 rounded-pill <?= $product_id ? 'd-none':'' ?>">Barkod Okutun</a>
                    <?= productSelect($product_id) ?>
                    <div id="selectedProduct" class="<?= $product_id ? '' : 'd-none' ?>">
                        <div class="p-3" id="product_info">
                            <?= $product_info ?>
                        </div>
                        <form id="customActionForm" action="controller.php" method="post">
                            <input type="hidden" name="product_id" id="hidden_product_id" value="<?= $product_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <select id="dynamic_container_list" name="container_id" class="form-select btn-outline-success rounded-pill w-100 py-3">
                                <option value="">Mevcut Raf/Koli Seçin</option>
                                <?= $product_containers ?>
                            </select>
                            <select name="new_container_id" class="form-select btn-outline-success rounded-pill w-100 py-3">
                                <option value="">Yeni Raf/Koli Seçin</option>
                                <?= containerOptGrouped() ?>
                            </select>
                            <input type="number" name="count" class="form-control btn-outline-success rounded-pill w-100 py-3" placeholder="Ürün Adedi">
                            <button name="action" value="remove_from_container" type="submit" class="btn btn-primary rounded-pill w-100 py-2 mt-1">Depo Çıkışı Yap</button>
                            <button name="action" value="move_to_container" type="submit" class="btn btn-primary rounded-pill w-100 py-2 mt-1">Depo İçinde Taşı</button>
                            <button name="action" value="place_in_container" type="submit" class="btn btn-primary rounded-pill w-100 py-2 mt-1">Depo Girişi Yap</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= wh_menu() ?>
</div>
<script defer>
$(document).ready(function() {
    $('#product_select').on('change', function() {
        var productId = $(this).val();
        if (productId) {
            $.ajax({
                url: 'controller.php',
                method: 'POST',
                data: { product_id: productId , action: 'product_info', csrf_token: '<?= $_SESSION['csrf_token'] ?>'},
                success: function(response) {
                    $('#product_info').html(response.info);
                    $('#selectedProduct').removeClass('d-none');
                    $('#hidden_product_id').val(response.id);
                    $('#dynamic_container_list').html('<option value="">Mevcut Raf/Koli Seçin</option>' + response.container);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product information:', error);
                }
            });
        } else {
            $('#selectedProduct').addClass('d-none');
        }
    });
});

$(document).ready(function() {
    $('#customActionForm').on('submit', function(event) {
        var action = $(document.activeElement).val(); // Get the value of the clicked button
        var containerId = $('#dynamic_container_list').val();
        var newContainerId = $('[name="new_container_id"]').val();
        var count = $('[name="count"]').val();
        if (count < 1) {
            alert('Ürün adedi 1\'den küçük olamaz.');
            event.preventDefault();
            return;
        }
        var valid = true;
        
        if (action === 'remove_from_container' && !containerId) {
            valid = false;
            alert('Ürünün olduğu raf/kolilerden birini seçin.');
        } else if (action === 'move_to_container' && (!containerId || !newContainerId)) {
            valid = false;
            alert('Hem mevcut hem de yeni raf/koliyi seçin.');
        } else if (action === 'place_in_container' && !newContainerId) {
            valid = false;
            alert('Ürünün yerleştirileceği raf/koliyi seçin.');
        }

        if (!valid) {
            event.preventDefault(); // Prevent the form from submitting if validation fails
        }
    });
});

</script>

<?php

include '../_footer.php';
