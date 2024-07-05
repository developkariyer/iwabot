<?php

require_once('warehouse.php');

include '../_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>IWA Depo Yönetim</h1>
        <p><span class="username"><?= $_SESSION['user_info']['name'] ?></span></p>
        <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
    </div>
    <div class="row g-3 m-3">
        <?= button('product.php', 'Ürün İşlem') ?>
        <?= button('container.php', 'Koli/Raf İşlem') ?>
    </div>
    <div class="row g-3 m-3 mt-5">
        <?= button('inventory.php', 'Depo Envanteri') ?>
        <?= button('transfers.php', 'Hareket Raporu') ?>
    </div>
    <div class="row g-3 m-3 mt-5">
        <?= button('wh_new_transfer.php', 'Yeni Sipariş') ?>
        <?= button('wh_new_transfer.php', 'Sipariş Sil') ?>
    </div>

    <?= wh_menu() ?>

</div>

<?php

include '../_footer.php';
