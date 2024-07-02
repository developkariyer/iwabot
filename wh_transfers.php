<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$logactions = $GLOBALS['pdo']->query('SELECT * FROM wh_log ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

function logdecode($log) {
    $operation = json_decode($log['operation'], true);
    $log = json_encode($log);

    $object = $operation['object']::getById($operation['id'], $GLOBALS['pdo']);
    switch ($operation['method']) {
        case 'putOnShelf':
            $shelf = StockShelf::getById($operation['parameters']['shelf']['id'], $GLOBALS['pdo']);
            return "<strong>{$object->name}</strong> ({$object->fnsku}) ürününden <strong>{$operation['parameters']['count']}</strong> adet <strong>{$shelf->name}</strong> rafına konuldu.";
        case 'removeFromShelf':
            $shelf = StockShelf::getById($operation['parameters']['shelf']['id'], $GLOBALS['pdo']);
            return "<strong>{$object->name}</strong> ({$object->fnsku}) ürünü <strong>{$shelf->name}</strong> rafından alındı.";
        case 'moveBetweenShelves':
            $fromShelf = StockShelf::getById($operation['parameters']['fromShelf']['id'], $GLOBALS['pdo']);
            $toShelf = StockShelf::getById($operation['parameters']['toShelf']['id'], $GLOBALS['pdo']);
            return "<strong>{$object->name}</strong> ({$object->fnsku}) ürünü <strong>{$fromShelf->name}</strong> rafından <strong>{$toShelf->name}</strong> rafına taşındı.";
        case 'newShelf':
            return "<strong>{$object['name']}</strong> isimli yeni raf oluşturuldu.";
        case 'moveBoxToShelf':
            $toShelf = StockShelf::getById($operation['parameters']['shelf']['id'], $GLOBALS['pdo']);
            return "<strong>{$object->name}</strong> kutusu <strong>{$toShelf->name}</strong> rafına taşındı.";
        default:
            return "Bilinmeyen işlem";
    }
}

include '_header.php';

?>

<div class="container mt-5">
    <h2>Depo Hareketleri</h2>
    <?php foreach ($logactions as $log): ?>
        <li>
            <?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?>:
            <strong><?= username($log['user_id']) ?></strong> (<?= $log['user_id'] ?>) tarafından
            <?= logdecode($log) ?>
        </li>
    <?php endforeach; ?>

    <?= wh_menu() ?>
</div>

<?php

include '_footer.php';