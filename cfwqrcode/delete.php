<?php

require_once "../_init.php";
require_once "../_login.php";
require_once 'QrModel.php';

$uniqueCode = $_GET['unique_code'] ?? null;
if ($uniqueCode) {
    $qrModel = new QrModel();
    $record = $qrModel->getQRCodeByUniqueCode($uniqueCode);
    
    if (!$record) {
        header('Location: index.php?status=error&message=Geçersiz QR kodu.');
        exit();
    }
    $qrModel->deleteQRCode($uniqueCode);
    unset($qrModel);
    header('Location: index.php?status=SUCCESS&message=QR kodu silindi.');
} else {
    header('Location: index.php?status=error&message=QR kodu seçilmedi.');
}
exit;
