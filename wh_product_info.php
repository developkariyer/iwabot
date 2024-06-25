<?php

require_once('_login.php');
require_once('_init.php');

// Simulate getting product info based on the barcode
function getProductInfo($barcode) {
    // Hypothetical product data for demonstration
    $productInfo = [
        '8690793600321' => [
            'productInfo' => 'Product Name: Example Product<br>Description: This is an example product.<br>Price: $10.00',
            'stock' => 10
        ],
        '9876543210987' => [
            'productInfo' => 'Product Name: Another Product<br>Description: This is another example product.<br>Price: $20.00',
            'stock' => 0
        ]
    ];

    return isset($productInfo[$barcode]) ? $productInfo[$barcode] : ['productInfo' => 'Product information not found.', 'stock' => 0];
}

if (isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];
    $productInfo = getProductInfo($barcode);
    echo json_encode($productInfo);
} else {
    echo json_encode(['productInfo' => 'No barcode provided.', 'stock' => 0]);
}
