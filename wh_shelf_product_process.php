<?php

require_once('_login.php');
require_once('_init.php');

if (!isset($_POST['shelf'])) {
    error_log('No shelf');
    header('Location: wh_shelf_select.php');
    exit;
}

// check shelf exists
$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM wh_shelf WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_POST['shelf']]);
if (!$shelf = $stmt->fetch(PDO::FETCH_ASSOC)) {
    error_log('Shelf not found');
    header('Location: wh_shelf_select.php');
    exit;
}

if (
        !isset($_POST['barcode']) || 
        !isset($_POST['stock']) || 
        !is_numeric($_POST['stock']) ||
        !isset($_POST['actionType']) ||
        !in_array($_POST['actionType'], ['take', 'put'])
    ) {
        error_log('Invalid post data');
    header('Location: wh_shelf_product.php?shelf='.urlencode($_POST['shelf']));
    exit;
}

// check product exists
$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM wh_product WHERE fnsku = :fnsku LIMIT 1');
$stmt->execute(['fnsku' => $_POST['barcode']]);
if (!$product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    error_log('Product not found');
    header('Location: wh_shelf_product.php?shelf='.urlencode($_POST['shelf']));
    exit;
}

// if take, check if value is valid
if ($_POST['actionType'] == 'take') {
    $stmt = $GLOBALS['pdo']->prepare('SELECT count(*) FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id');
    $stmt->execute(['product_id' => $product['id'], 'shelf_id' => $shelf['id']]);
    $stock = $stmt->fetchColumn();
    if ($stock < $_POST['stock']) {
        error_log('Not enough stock');
        header('Location: wh_shelf_product.php?shelf='.urlencode($_POST['shelf']));
        exit;
    }
}

if ($_POST['actionType'] == 'take') {
    error_log('Taking stock');
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM wh_shelf_product WHERE product_id = :product_id AND shelf_id = :shelf_id LIMIT :stock');
    $stmt->execute(['product_id' => $product['id'], 'shelf_id' => $shelf['id'], 'stock' => $_POST['stock']]);
} else {
    error_log('Putting stock');
    $stmt = $GLOBALS['pdo']->prepare('INSERT INTO wh_shelf_product (product_id, shelf_id) VALUES (:product_id, :shelf_id)');
    for ($t=0;$t<$_POST['stock'];$t++) {
        $stmt->execute(['product_id' => $product['id'], 'shelf_id' => $shelf['id']]);
    }
}

header('Location: wh_shelf_product.php?shelf='.urlencode($_POST['shelf']));