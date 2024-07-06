<?php

require_once('warehouse.php');

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Ürün İşlemleri</h1>
        <p>İşlem yapmak istediğiniz ürünü seçiniz. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>










    <div class="row g-3 m-1">
        <?= button('product.php', 'Ürün İşlem') ?>
        <?= button('container.php', 'Koli/Raf İşlem') ?>
    </div>
    <div class="row g-3 m-1 mt-1">
        <?= button('inventory.php', 'Depo Envanteri') ?>
        <?= button('transfers.php', 'Hareket Raporu') ?>
    </div>
    <div class="row g-3 m-1 mt-1">
        <?= button('order.php', 'Yeni Sipariş') ?>
        <?= button('order.php', 'Sipariş Sil') ?>
    </div>
    <?= wh_menu() ?>

</div>
<?php

include '../_footer.php';