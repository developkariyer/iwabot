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
    <div class="d-grid gap-2 m-3">
        <a href="wh_find_product.php" class="btn btn-primary btn-lg">Ürün Bul</a>
        <a href="wh_" class="btn btn-primary btn-lg">Kutu Bul</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_" class="btn btn-primary btn-lg">Raftan/Kutudan Ürün Al</a>
        <a href="wh_" class="btn btn-primary btn-lg">Rafa/Kutuya Ürün Koy</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_shelf_select.php" class="btn btn-primary btn-lg">Sayım / Ürün Yerleştir</a>
        <a href="wh_" class="btn btn-primary btn-lg"></a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_" class="btn btn-primary btn-lg">Güncel Depo Listesi</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="./" class="btn btn-secondary btn-lg">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg">Logout</a>
    </div>
</div>

<?php



include '_footer.php';