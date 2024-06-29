<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$shelfList = StockShelf::getAll($GLOBALS['pdo']);
$productList = StockProduct::getAll($GLOBALS['pdo']);

function dumpProducts($shelf) {
    $retval = '';
    foreach ($shelf->getProducts() as $product) {
        $retval.= '<ul><li>' . $product->name . ': ' . $product->shelfCount($shelf) . ' adet</li></ul>';
    }
    return $retval;
}


include '_header.php';

?>
<div class="container mt-5">
    <div class="mt-5">
        <h2>Depo Envanteri (Rafa Göre)</h2>
        <?php foreach ($shelfList as $shelf): ?>
            <h3><?= $shelf->name ?></h3>
            <h5>Rafta Açık</h5>
            <?= dumpProducts($shelf) ?>
            <?php foreach ($shelf->getChildren() as $child): ?>
                <h5><?= $child->name ?>/<?= $child->type ?></h5>
                <?= dumpProducts($child) ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <h2>Depo Envanteri (Ürüne Göre)</h2>


    <?= wh_menu() ?>
</div>


<?php

include '_footer.php';