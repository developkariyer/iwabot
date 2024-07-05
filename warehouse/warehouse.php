<?php

require_once('../_login.php');
require_once('../_init.php');
//require_once('wh_class_abstract.php');
//require_once('WarehouseContainer.php');
//require_once('warehouse_class_product.php');

function wh_menu() {
    return '    <div class="row g-3 m-3 mt-5">
        <div class="col-md-6">
            <a href="./" class="btn btn-secondary btn-lg rounded-pill w-100 py-3">Depo Ana Sayfa</a>
        </div>
        <div class="col-md-6">
            <a href="../" class="btn btn-secondary btn-lg rounded-pill w-100 py-3">Ana Sayfa</a>
        </div>
    </div>

    <div class="row g-3 m-3 mt-5">
        <div class="col-md-3">
        </div>
        <div class="col-md-6">
            <a href="../?logout=1" class="btn btn-danger btn-lg rounded-pill w-100 py-3">Çıkış</a>
        </div>
    </div>
';
}

function metricToImp($inp, $conv=0.393700787) {
    return number_format($inp * $conv, 2);
}

