<?php

require_once "../_init.php";
require_once "../_login.php";
require_once 'QrModel.php';

if (isset($_GET['code'])) {
    $qrModel = new QrModel();
    $uniqueCode = $_GET['code'];
    $base64Image = $qrModel->getQRCodeBase64($uniqueCode);
    unset($qrModel);
    if ($base64Image) {
        $base64Image = str_replace('data:image/png;base64,', '', $base64Image);
        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            echo "Base64 verisi çözülemedi.";
            exit;
        }
        header('Content-Type: image/png');
        header("Content-Disposition: attachment; filename=\"$uniqueCode.png\"");
        header('Content-Length: ' . strlen($imageData));
        echo $imageData;
        exit;
    } else {
        echo "Resim bulunamadı.";
    }
} else {
    echo "Kod belirtilmedi.";
}
