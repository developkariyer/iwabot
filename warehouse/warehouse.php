<?php

require_once('../_login.php');
require_once('../_init.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once('WarehouseAbstract.php');
require_once('WarehouseProduct.php');
require_once('WarehouseContainer.php');

function button($url, $text, $color='primary') {
    return '<div class="col-md-6"><a href="'.$url.'" class="btn btn-'.$color.' btn-lg rounded-pill w-100 py-3">'.$text.'</a></div>';
}

function wh_menu() {
    return 
    '<div class="row g-3 m-1">'.
        button('product.php', 'Ürün İşlem').
        button('container.php', 'Koli/Raf İşlem').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('inventory.php', 'Depo Envanteri').
        button('transfers.php', 'Hareket Raporu').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('order.php', 'Yeni Sipariş').
        button('order.php', 'Sipariş Sil').
    '</div><div class="row g-3 m-1 mt-1">'.
        button('./', 'Depo Ana Sayfa', 'secondary').
        button('../', 'Ana Sayfa', 'secondary').
    '</div><div class="row g-3 m-1 mt-1">'.
        '<div class="col-md-3"></div>'.
        button('../?logout=1', 'Çıkış', 'danger').
    '</div>';
}

function metricToImp($inp, $conv=0.393700787) {
    return number_format($inp * $conv, 2);
}

function productInfo($product) {
    if (!$product instanceof WarehouseProduct) {
        return "Ürün bilgisi alınamadı: Geçersiz ürün";
    }
    return "
    <b>Ürün Adı:</b> {$product->name}<br>
    <b>FNSKU:</b> {$product->fnsku}<br>
    <b>Kategori:</b> {$product->category}<br>
    <b>IWASKU:</b> {$product->iwasku}<br>
    <b>Özellikler (metrik):</b><br>{$product->dimension1}x{$product->dimension2}x{$product->dimension3}cm, {$product->weight}gr<br>
    <b>Özellikler (imperyal):</b><br>".metricToImp($product->dimension1)."x".metricToImp($product->dimension2)."x".metricToImp($product->dimension3)."in, ".metricToImp($product->weight, 0.00220462)."lbs<br>
    <b>Toplam Depo Stoğu:</b> {$product->getTotalCount()} adet";
    //    <b>Seri Numarası:</b> {$product->serial_number}<br>

}
