<?php

//TODO: koli düzenleme
//TODO: ürün silme/düzenleme
//TODO: envanter hareketleri analizi

require_once('warehouse.php');

if (!userCan(['manage', 'process'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

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
                <button class="accordion-button bg-success text-white w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion" aria-expanded="true" aria-controls="productAccordion">
                    <span><strong>Çıkış İçin Bekleyen Ürünler (<?= count($unfulfilledProducts) ?> adet)</strong></span>
                </button>
            </h2>
            <div id="productAccordion" class="accordion-collapse collapse show" aria-labelledby="headingMain" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0">
                    <?php foreach ($unfulfilledProducts as $index => $product): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                    <span><strong><?= htmlspecialchars($product['product']->name) ?> (<?= htmlspecialchars($product['product']->fnsku) ?>)</strong></span>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#productAccordion">
                                <div class="accordion-body p-5">
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
                                        <button id="Submit<?= $index ?>Product<?= $product['product']->id ?>" type="submit" class="btn btn-primary w-100 py-3 mt-2">Ürün Çıkışı Yap</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($unfulfilledProducts) || count($unfulfilledProducts) == 0): ?>
                        <div class="p-5">
                            <p>Çıkış için bekleyen ürün bulunmamaktadır.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white <?= $product_id ? '' : 'collapsed' ?> w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion2" aria-expanded="<?= $product_id ? 'true' : 'false' ?>" aria-controls="productAccordion2">
                    <span><strong>Kendiniz Ürün Seçin</strong></span>
                </button>
            </h2>
            <div id="productAccordion2" class="accordion-collapse collapse <?= $product_id ? 'show' : '' ?>" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0 w-100 mb-3 p-5">
                    <div class="d-flex justify-content-center w-100 p-5">
                        <a href="barcode.php" class="btn btn-primary py-3 w-100 <?= $product_id ? 'd-none':'' ?>">Barkod Okutun</a>
                    </div>
                    <?= productSelect($product_id) ?>
                    <div id="selectedProduct" class="<?= $product_id ? '' : 'd-none' ?>">
                        <div class="p-3" id="product_info">
                            <?= $product_info ?>
                        </div>
                        <form id="customActionForm" action="controller.php" method="post">
                            <input type="hidden" name="product_id" id="hidden_product_id" value="<?= $product_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="m-3">
                                <label for="dynamic_container_list" class="form-label">Ürünün Halihazırdaki Raf/Kolileri</label>
                                <select id="dynamic_container_list" name="container_id" class="form-select w-100">
                                    <option value="">Mevcut Raf/Koli Seçin</option>
                                    <?= $product_containers ?>
                                </select>
                            </div>
                            <div class="m-3">
                                <label for="count" class="form-label">Ürün İçin Yeni Raf/Koli</label>
                                <select id="dynamic_new_container" name="new_container_id" class="form-select w-100">
                                    <option value="">Yeni Raf/Koli Seçin</option>
                                    <?= containerOptGrouped() ?>
                                </select>
                            </div>
                            <div class="m-3">
                                <label for="count" class="form-label">İşlem Yapılacak Ürün Adedi</label>
                                <input type="number" name="count" min="1" class="form-control w-100" placeholder="Ürün Adedi">
                            </div>
                            <button name="action" value="remove_from_container" type="submit" class="btn btn-primary w-100 py-3 mt-2">Depo Çıkışı Yap</button>
                            <button name="action" value="move_to_container" type="submit" class="btn btn-primary w-100 py-3 mt-2">Depo İçinde Taşı</button>
                            <button name="action" value="place_in_container" type="submit" class="btn btn-primary w-100 py-3 mt-2">Depo Girişi Yap</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#productAccordion3" aria-expanded="false" aria-controls="productAccordion3">
                    <span><strong>Kataloğa Yeni Ürün Ekleyin</strong></span>
                </button>
            </h2>
            <div id="productAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5 w-100">
                    <form action="controller.php" method="POST">
                        <input type="hidden" name="action" value="add_product">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Ürün Adı</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="fnsku" class="form-label">FNSKU</label>
                            <input type="text" class="form-control" id="fnsku" name="fnsku" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" required>
                        </div>
                        <div class="mb-3">
                            <label for="iwasku" class="form-label">IWASKU</label>
                            <input type="text" class="form-control" id="iwasku" name="iwasku">
                        </div>
                        <div class="mb-3">
                            <label for="dimension1" class="form-label">Ölçüler (cm)</label>
                            <div class="row">
                                <div class="col-3">
                                    <input type="text" class="form-control" id="dimension1" name="dimension1" placeholder="Dimension 1">
                                </div>
                                <div class="col-1 text-center">
                                    <span class="form-control-plaintext">x</span>
                                </div>
                                <div class="col-3">
                                    <input type="text" class="form-control" id="dimension2" name="dimension2" placeholder="Dimension 2">
                                </div>
                                <div class="col-1 text-center">
                                    <span class="form-control-plaintext">x</span>
                                </div>
                                <div class="col-3">
                                    <input type="text" class="form-control" id="dimension3" name="dimension3" placeholder="Dimension 3">
                                </div>
                                <div class="col-1 text-center">
                                    <span class="form-control-plaintext">cm</span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="weight" class="form-label">Ağırlık (gr)</label>
                            <input type="text" class="form-control" id="weight" name="weight">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Yeni Ürün Ekle</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <hr>

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
        var newContainerId = $('#dynamic_new_container').val();
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
