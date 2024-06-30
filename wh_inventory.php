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
        <ul class="list-unstyled">
            <?php foreach ($shelfList as $index => $shelf): ?>
                <li>
                    <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#shelf<?= $index ?>" aria-expanded="false" aria-controls="shelf<?= $index ?>">
                        <strong><?= htmlspecialchars($shelf->name) ?></strong>
                    </button>
                    <div id="shelf<?= $index ?>" class="collapse">
                        <ul class="list-unstyled ms-4">
                            <li>
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#openShelf<?= $index ?>" aria-expanded="false" aria-controls="openShelf<?= $index ?>">
                                    Rafta Açık
                                </button>
                                <div id="openShelf<?= $index ?>" class="collapse">
                                    <ul class="list-unstyled ms-4">
                                        <?= dumpProducts($shelf) ?>
                                    </ul>
                                </div>
                            </li>
                            <?php foreach ($shelf->getChildren() as $childIndex => $child): ?>
                                <li>
                                    <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#child<?= $index ?>_<?= $childIndex ?>" aria-expanded="false" aria-controls="child<?= $index ?>_<?= $childIndex ?>">
                                        <?= htmlspecialchars($child->name) ?>/<?= htmlspecialchars($child->type) ?>
                                    </button>
                                    <div id="child<?= $index ?>_<?= $childIndex ?>" class="collapse">
                                        <ul class="list-unstyled ms-4">
                                            <?= dumpProducts($child) ?>
                                        </ul>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <h2>Depo Envanteri (Ürüne Göre)</h2>
        <ul class="list-unstyled">
            <?php foreach ($productList as $productIndex => $product): ?>
                <li>
                    <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#product<?= $productIndex ?>" aria-expanded="false" aria-controls="product<?= $productIndex ?>">
                        <strong><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</strong>
                    </button>
                    <div id="product<?= $productIndex ?>" class="collapse">
                        <ul class="list-unstyled ms-4">
                            <?php foreach ($product->getShelves() as $shelf): ?>
                                <li><?= htmlspecialchars($shelf->name) ?>/<?= htmlspecialchars($shelf->type) ?>: <?= htmlspecialchars($product->shelfCount($shelf)) ?> adet</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?= wh_menu() ?>
</div>

<?php

include '_footer.php';

?>
