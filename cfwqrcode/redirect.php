<?php

require_once '../_init.php';
require_once 'QrModel.php';

$uniqueCode = $_GET['code'] ?? null;

if ($uniqueCode) {
    $uniqueCode = trim($uniqueCode, '/');
    $qrModel = new QrModel();
    $link = $qrModel->getLinkByUniqueCode($uniqueCode);
    unset($qrModel);
    if ($link) {
        header("Location: $link");
        exit();
    } else {
        echo "Geçersiz QR kodu.";
    }
} else {
    echo "Kod belirtilmedi.";
}
