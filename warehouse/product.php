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
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion2" aria-expanded="true" aria-controls="productAccordion2">
                    <span><strong>Kendiniz Ürün Seçin</strong></span>
                </button>
            </h2>
            <div id="productAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0 w-100">
                    <?= productSelect() ?>
                    <div id="selectedProduct" class="d-none">
                        <div class="p-3" id="product_info">
                            <p>Ürün Bilgileri</p>
                        </div>
                        <!-- First Nested Accordion -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFirst">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFirst" aria-expanded="false" aria-controls="collapseFirst">
                                    <span><strong>Depoya Giriş Yapın</strong></span>
                                </button>
                            </h2>
                            <div id="collapseFirst" class="accordion-collapse collapse" aria-labelledby="headingFirst" data-bs-parent="#selectedProduct">
                                <div class="accordion-body">
                                    <form action="controller.php" method="post">
                                        <!-- Your form fields for Action 1 -->
                                        <input type="hidden" name="action" value="action1">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <!-- Add other form fields as needed -->
                                        <button type="submit" class="btn btn-primary">Submit Action 1</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Second Nested Accordion -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSecond">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSecond" aria-expanded="false" aria-controls="collapseSecond">
                                    <span><strong>Depo İçinde Taşıyın</strong></span>
                                </button>
                            </h2>
                            <div id="collapseSecond" class="accordion-collapse collapse" aria-labelledby="headingSecond" data-bs-parent="#selectedProduct">
                                <div class="accordion-body">
                                    <form action="controller.php" method="post">
                                        <!-- Your form fields for Action 2 -->
                                        <input type="hidden" name="action" value="action2">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <!-- Add other form fields as needed -->
                                        <button type="submit" class="btn btn-primary">Submit Action 2</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Third Nested Accordion -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThird">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThird" aria-expanded="false" aria-controls="collapseThird">
                                    <span><strong>Depodan Çıkış Yapın</strong></span>
                                </button>
                            </h2>
                            <div id="collapseThird" class="accordion-collapse collapse" aria-labelledby="headingThird" data-bs-parent="#selectedProduct">
                                <div class="accordion-body">
                                    <form action="controller.php" method="post">
                                        <!-- Your form fields for Action 3 -->
                                        <input type="hidden" name="action" value="action3">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <!-- Add other form fields as needed -->
                                        <button type="submit" class="btn btn-primary">Submit Action 3</button>
                                    </form>
                                </div>
                            </div>
                        </div>
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
            $('#selectedProduct').removeClass('d-none');
            /*
            // Make the AJAX request
            $.ajax({
                url: 'your_api_endpoint.php', // Replace with your actual endpoint
                method: 'GET',
                data: { product_id: productId },
                success: function(response) {
                    // Assuming the response is a JSON object with product information
                    var product = response.product;
                    
                    // Set hidden fields
                    $('#hiddenField1').val(product.field1); // Replace with actual field IDs and response fields
                    $('#hiddenField2').val(product.field2);

                    // Update visible fields or divs
                    $('#product_name').text(product.name);
                    $('#product_fnsku').text(product.fnsku);
                    $('#product_info').removeClass('d-none');
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product information:', error);
                }
            });*/
        } else {
            // Hide the product_info div and clear hidden fields
            $('#selectedProduct').addClass('d-none');
        }
    });
});
</script>

<?php

include '../_footer.php';
