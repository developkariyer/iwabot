<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$shelfList = StockShelf::allShelves($GLOBALS['pdo']);
$productList = StockProduct::allProducts($GLOBALS['pdo']);

function dumpProducts($shelf) {
    $retval = '';
    foreach ($shelf->getProducts() as $product) {
        $retval .= '<li>' . htmlspecialchars($product->name) . ' ('.htmlspecialchars($product->fnsku).') : ' . htmlspecialchars($product->shelfCount($shelf)) . ' adet</li>';
    }
    return $retval;
}

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-5">
        <h2>Depo Envanteri (Rafa Göre)</h2>
        <ul>
            <?php foreach ($shelfList as $shelf): ?>
                <li>
                    <strong><?= htmlspecialchars($shelf->name) ?></strong>
                    <ul>
                        <li>Rafta Açık
                            <ul>
                                <?= dumpProducts($shelf) ?>
                            </ul>
                        </li>
                        <?php foreach ($shelf->getChildren() as $child): ?>
                            <li><?= htmlspecialchars($child->name) ?>/<?= htmlspecialchars($child->type) ?>
                                <ul>
                                    <?= dumpProducts($child) ?>
                                </ul>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
        <h2>Depo Envanteri (Ürüne Göre)</h2>
        <ul>
            <?php foreach ($productList as $product): ?>
                <li>
                    <strong><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</strong>
                    <ul>
                        <?php foreach ($product->getShelves() as $shelf): ?>
                            <li><?= htmlspecialchars($shelf->name) ?>/<?= htmlspecialchars($shelf->type) ?>: <?= htmlspecialchars($product->shelfCount($shelf)) ?> adet</li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
    </div>
    <?= wh_menu() ?>
</div>

<?php

include '_footer.php';

?>
