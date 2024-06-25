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
        <a href="wh_shelf_select.php" class="btn btn-primary btn-lg w-100">Sayım / Ürün Yerleştir</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="wh_" class="btn btn-primary btn-lg w-100">Güncel Depo Listesi</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="./" class="btn btn-secondary btn-lg w-100">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg w-100">Logout</a>
    </div>
</div>

<?php



include '_footer.php';