<?php

require_once('_login.php');
require_once('_init.php');

$productId = $_GET['product'] ?? '';

$shelfs = $GLOBALS['pdo']->query('SELECT * FROM wh_shelf ORDER BY type DESC, name ASC')->fetchAll(PDO::FETCH_ASSOC);
$shelf = [];
foreach ($shelfs as $s) {
    $shelf[$s['id']] = $s;
    if ($s['parent_id'] && isset($shelf[$s['parent_id']])) {
        if (!isset($shelf[$s['parent_id']]['children'])) {
            $shelf[$s['parent_id']]['children'] = [];
        }
        $shelf[$s['parent_id']]['children'][] = $s['id'];
    }
}

$productCounts = [];

foreach ($shelf as $key => $s) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT wsp.product_id AS id, wp.name AS name, wp.fnsku AS fnsku, COUNT(*) AS shelf_count 
    FROM wh_shelf_product wsp 
    JOIN wh_product wp ON wp.id = wsp.product_id
    WHERE wsp.shelf_id = :shelf_id 
    GROUP BY wsp.product_id, wp.name, wp.fnsku
    ORDER BY wp.name ASC');

    $stmt->execute(['shelf_id' => $s['id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $shelf[$key]['products'] = $products;
    foreach ($products as $product) {
        if (!isset($productCounts[$product['id']])) {
            $productCounts[$product['id']] = 0;
        }
        $productCounts[$product['id']] += $product['shelf_count'];
    }
}

/*
                    <?php if (empty($s['products'])): ?>
                        <p><?= $s['type'] ?> boş.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($s['products'] as $product): ?>
                                <?php if ($productId && $product['id'] != $productId) continue; ?>
                                <li>
                                    <?= "{$product['name']} (Kod: {$product['fnsku']}, Stok: {$product['shelf_count']} / Toplam: {$productCounts[$product['id']]})" ?>
                                    <div style="float: right;">
                                        <button class="btn btn-primary">İşlem 1</button>
                                        <button class="btn btn-secondary">İşlem 2</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

*/

function productRow($shelf)
{
    global $productCounts;
    if (empty($shelf['products'])) {
        return "<p>{$shelf['type']} boş.</p>";
    }

    $retval = "<ul>";
    foreach ($shelf['products'] as $product) {
        $retval .= "<li>";
        $retval .= "{$product['name']} (Kod: {$product['fnsku']}, Stok: {$product['shelf_count']} / Toplam: {$productCounts[$product['id']]})";
        $retval .= "<div style='float: right;'>";
        $retval .= "<button class='btn btn-primary'>İşlem 1</button>";
        $retval .= "<button class='btn btn-secondary'>İşlem 2</button>";
        $retval .= "</div>";
        $retval .= "</li>";
    }
    $retval .= "</ul>";
    return $retval;
}

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-4 m-3">
        <h2>Depo Listesi</h2>
        <ul>
            <?php foreach ($shelf as $s): ?>
                <?php if ($s['parent_id']) continue; ?>
                <li>
                    <h3>
                        <a href="wh_shelf_product.php?shelf=<?= $s['id'] ?>">
                            <?= "{$s['name']} / {$s['type']}" ?><?= $s['parent_id'] ? " Raf: {$shelf[$s['parent_id']]['name']}" : "" ?>
                        </a>
                    </h3>
                    <?php if (!empty($s['children'])): ?>
                        <ul>
                            <?php foreach ($s['children'] as $child): ?>
                                <li>
                                    <h4>
                                        <a href="wh_shelf_product.php?shelf=<?= $child ?>">
                                            <?= "{$shelf[$child]['name']} / {$shelf[$child]['type']}" ?>
                                        </a>
                                    </h4>
                                    <?= productRow($shelf[$child]) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?= productRow($s) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="d-grid gap-2 mt-4 m-3">
        <a href="./wh.php" class="btn btn-secondary btn-lg w-100">Depo Yönetim Ana Sayfa</a>
        <a href="./" class="btn btn-secondary btn-lg w-100">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg w-100">Logout</a>
    </div>
</div>

<?php

include '_footer.php';
