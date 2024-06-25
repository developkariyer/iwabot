<?php

require_once('_login.php');
require_once('_init.php');

function getProductInfo($productCode) {
    // Hypothetical function to get product information based on barcode
    // Replace with actual database call or API request as needed
    $productInfo = [
        '8690793600321' => 'Product Name: Example Product<br>Description: This is an example product.<br>Price: $10.00',
        '9876543210987' => 'Product Name: Another Product<br>Description: This is another example product.<br>Price: $20.00'
    ];

    return isset($productInfo[$productCode]) ? $productInfo[$productCode] : 'Product information not found.';
}

if (isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];
    $productInfo = getProductInfo($barcode);
    echo $productInfo;
} else {
    echo 'No barcode provided.';
}

?>
