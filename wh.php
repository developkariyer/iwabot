<?php

require_once('_login.php');
require_once('_init.php');

include '_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>IWA Depo Yönetim</h1>
        <p><span class="username"><?= $_SESSION['user_info']['name'] ?></span></p>
        <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
    </div>

    <div class="row g-3 m-3">
        <div class="col-md-6">
            <a href="wh_product_search.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Ürün Bul</a>
        </div>
        <div class="col-md-6">
            <a href="wh_shelf_search.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Koli/Raf Bul</a>
        </div>
    </div>

    <div class="row g-3 m-3 mt-5">
        <div class="col-md-6">
            <a href="wh_product_action.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Ürün Taşı</a>
        </div>
        <div class="col-md-6">
            <a href="wh_shelf_action.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Koli/Raf Taşı</a>
        </div>
    </div>

    <div class="row g-3 m-3 mt-5">
        <div class="col-md-6">
            <a href="wh_inventory.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Depo Envanteri</a>
        </div>
        <div class="col-md-6">
            <a href="wh_transfers.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Hareket Raporu</a>
        </div>
    </div>

    <div class="row g-3 m-3 mt-5">
        <div class="col-md-6">
            <a href="wh_new_product.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Yeni Ürün</a>
        </div>
        <div class="col-md-6">
            <a href="wh_new_shelf.php" class="btn btn-primary btn-lg rounded-pill w-100 py-3">Yeni Koli/Raf</a>
        </div>
    </div>

    <?= wh_menu() ?>

</div>

<?php

include '_footer.php';