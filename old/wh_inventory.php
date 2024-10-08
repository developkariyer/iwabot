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
        <h2>Depo Envanteri</h2>
        <p>
            Bu sayfada depoda yer alan tüm raf, koli ve ürünlerin dökümü yapılmaktadır.<br>
            İlk kısımda raflar listelenmekte, raf üzerine tıklandığında o rafta açık bulunan ürünler 
            ile raftaki kolilerde yer alan ürünler listelenmektedir.<br>
            İkinci kısımda ise depoda stoğu olan ürünler listelenmekte ve bu ürünlerin nerelerde olduğu ürün
            üzerine tıklanınca görülebilmektedir.
        </p>
        <h3>Depo Envanteri (Rafa Göre)</h3>
        <ul>
            <?php foreach ($shelfList as $index => $shelf): ?>
                <li>
                    <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#shelf<?= $index ?>" aria-expanded="false" aria-controls="shelf<?= $index ?>">
                        <strong><?= htmlspecialchars($shelf->name) ?></strong>
                    </button>
                    <div id="shelf<?= $index ?>" class="collapse">
                        <ul>
                            <li>
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#openShelf<?= $index ?>" aria-expanded="false" aria-controls="openShelf<?= $index ?>">
                                    Rafta Açık (<?= count($shelf->getProducts()) ?>)
                                </button>
                                <div id="openShelf<?= $index ?>" class="collapse">
                                    <ul>
                                        <?= dumpProducts($shelf) ?>
                                    </ul>
                                </div>
                            </li>
                            <?php foreach ($shelf->getChildren() as $childIndex => $child): ?>
                                <?php if (count($child->getProducts()) > 0): ?>
                                    <li>
                                        <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#child<?= $index ?>_<?= $childIndex ?>" aria-expanded="false" aria-controls="child<?= $index ?>_<?= $childIndex ?>">
                                            <?= htmlspecialchars($child->name) ?>/<?= htmlspecialchars($child->type) ?> (<?= count($child->getProducts()) ?>)
                                        </button>
                                        <div id="child<?= $index ?>_<?= $childIndex ?>" class="collapse">
                                            <ul>
                                                <?= dumpProducts($child) ?>
                                            </ul>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <h3>Depo Envanteri (Ürüne Göre)</h3>
        <ul>
            <?php foreach ($productList as $productIndex => $product): ?>
                <?php 
                $totalAmount = array_reduce($product->getShelves(), function($carry, $shelf) use ($product) {
                    return $carry + $product->shelfCount($shelf);
                }, 0);
                ?>
                <?php if ($totalAmount > 0): ?>
                    <li>
                        <button class="btn btn-link text-start w-100" data-bs-toggle="collapse" data-bs-target="#product<?= $productIndex ?>" aria-expanded="false" aria-controls="product<?= $productIndex ?>">
                            <strong><?= htmlspecialchars($product->name) ?></strong> (<?= htmlspecialchars($product->fnsku) ?>) (<?= $totalAmount ?> adet)
                        </button>
                        <div id="product<?= $productIndex ?>" class="collapse">
                            <ul>
                                <?php foreach ($product->getShelves() as $shelf): ?>
                                    <?php $shelfCount = $product->shelfCount($shelf); ?>
                                    <?php if ($shelfCount > 0): ?>
                                        <li><?= htmlspecialchars($shelf->name) ?>/<?= htmlspecialchars($shelf->type) ?>: <?= $shelfCount ?> adet</li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <?= wh_menu() ?>
</div>


<?php

include '_footer.php';

?>
