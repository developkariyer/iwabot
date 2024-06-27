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

    <div class="d-grid gap-3 m-3">
        <a href="wh_product_search.php" class="btn btn-primary btn-lg rounded-pill">Ürün Ara</a>
        <a href="wh_product_search.php" class="btn btn-primary btn-lg rounded-pill">Yeni Ürün</a>
    </div>

    <div class="d-grid gap-3 m-3">
        <a href="wh_shelf_search.php" class="btn btn-primary btn-lg rounded-pill">Koli Ara</a>
        <a href="wh_shelf_search.php" class="btn btn-primary btn-lg rounded-pill">Yeni Koli</a>
    </div>

    <div class="d-grid gap-3 m-3">
        <a href="wh_shelf_select.php" class="btn btn-primary btn-lg rounded-pill">Raf Envanteri</a>
        <a href="wh_inventory.php" class="btn btn-primary btn-lg rounded-pill">Depo Envanteri</a>
    </div>

    <div class="d-grid gap-3 m-3">
        <a href="./" class="btn btn-secondary btn-lg rounded-pill">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg rounded-pill">Logout</a>
    </div>
</div>

<?php



include '_footer.php';