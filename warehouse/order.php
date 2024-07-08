<?php

require_once('warehouse.php');

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Sipariş İşlemleri</h1>
        <p>Sipariş/ürün çıkış işlemleri aşağıdan seçiniz. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">
        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion1" aria-expanded="false" aria-controls="orderAccordion1">
                    <span><strong>İşlem Bekleyen Siparişler</strong></span>
                </button>
            </h2>
            <div id="orderAccordion1" class="accordion-collapse collapse" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <!-- Dynamic content for pending orders will go here -->
                </div>
            </div>
        </div>

        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion2" aria-expanded="false" aria-controls="orderAccordion2">
                    <span><strong>Yeni Sipariş/Ürün Çıkış Kaydı Gir</strong></span>
                </button>
            </h2>
            <div id="orderAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <form action="controller.php" method="post">
                        <input type="hidden" name="action" value="add_sold_item">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <?= productSelect() ?>
                        <textarea rows="5" name="description" class="form-control btn-outline-success w-100 py-3 mt-2" placeholder="İsim - Adres - Açıklama - Ürün Kodları" required></textarea>
                        <button id="submitbutton" type="submit" class="btn btn-primary rounded-pill w-100 py-3 mt-2">Yeni Çıkış Kaydı Ekle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion3" aria-expanded="false" aria-controls="orderAccordion3">
                    <span><strong>Tamamlanmış İşlemler</strong></span>
                </button>
            </h2>
            <div id="orderAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <!-- Dynamic content for completed orders will go here -->
                </div>
            </div>
        </div>

    </div>

    <hr>

    <?= wh_menu() ?>
</div>

<?php

include '../_footer.php';

?>
