<?php

require_once('_login.php');
require_once('_init.php');

include '_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>IWA Depo Yönetime hoş geldiniz, <span class="username"><?= $_SESSION['user_info']['name'] ?></span></h1>
            <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
        </center>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Ürün Bul</a>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Kutu Bul </a>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Raftan/Kutudan Ürün Al</a>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Rafa/Kutuya Ürün Koy</a>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Raftan Kutu Al</a>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Rafa Kutu Koy</a>
    </div>
    <div class="m-3">
        <a href="wh_" class="btn btn-primary">Güncel Depo Listesi</a>
    </div>
</div>
<?php



include '_footer.php';