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
    return '
    <div class="row g-3 m-3 mt-5">'
        .button('./', 'Depo Ana Sayfa', 'secondary')
        .button('../', 'Ana Sayfa', 'secondary').'
    </div>

    <div class="row g-3 m-3 mt-5">
        <div class="col-md-3"></div>'.
        button('../?logout=1', 'Çıkış', 'danger').'
    </div>';
}

function metricToImp($inp, $conv=0.393700787) {
    return number_format($inp * $conv, 2);
}
