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
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    $shelf[$key]['products'] = $products;
    foreach ($products as $product) {
        if (!isset($productCounts[$product['id']])) {
            $productCounts[$product['id']] = 0;
        }
        $productCounts[$product['id']] += $product['shelf_count'];
    }
}

function productRow($shelf)
{
    global $productCounts;
    $collapseId = "collapse-child{$shelf['id']}";
    $retval = "<li><h4><a href='wh_shelf_product.php?shelf={$shelf['id']}'>";
    if ($shelf['type'] === 'Raf') {
        $retval .= "Raftaki Açık Ürünler</a>";
    } else {
        $retval .= "{$shelf['name']}</a> / {$shelf['type']}";
    }
    $retval .= "<span class='badge bg-secondary float-end small' data-bs-toggle='collapse' data-bs-target='#$collapseId' aria-expanded='false' aria-controls='$collapseId' style='cursor: pointer;'>".count($shelf['products'])." Ürün</span>";
    $retval .= "</h4><ul class='collapse' id='$collapseId'>";
    if (empty($shelf['products'])) {
        $retval .= "<p>{$shelf['type']} boş.</p>";
    } else {
        foreach ($shelf['products'] as $product) {
            $retval .= "<li>";
            $retval .= "<a href='wh_shelf_product.php?shelf={$shelf['id']}&fnsku={$product['fnsku']}'>";
            $retval .= "{$product['name']}";
            $retval .= "</a> <small><span style='white-space: nowrap;'>{$product['fnsku']}, {$product['shelf_count']}/{$productCounts[$product['id']]}</span></small>";
            $retval .= "</li>";
        }
    }
    $retval .= "</ul></li>";
    return $retval;
}

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-4 m-3">
        <h2>Depo Listesi</h2>
        <ul>
            <?php foreach ($shelf as $sIndex => $s): ?>
                <?php if ($s['parent_id']) continue; ?>
                <li>
                    <h3>
                        <a href="wh_shelf_product.php?shelf=<?= $s['id'] ?>"><?= $s['name'] ?></a>
                        / <?= $s['type'] ?>
                        <span class="badge bg-primary float-end small" data-bs-toggle="collapse" data-bs-target="#collapse-box<?= $sIndex ?>" aria-expanded="false" aria-controls="collapse-box<?= $sIndex ?>" style="cursor: pointer;">
                            <?= empty($s['children']) ? 0 : count($s['children']) ?> Koli
                        </span>
                    </h3>
                    <ul class='collapse' id='collapse-box<?= $sIndex ?>'>
                        <?php if (!empty($s['children'])): ?>
                            <?php foreach ($s['children'] as $child): ?>
                                <?= productRow($shelf[$child]) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?= productRow($s) ?>
                    </ul>
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
