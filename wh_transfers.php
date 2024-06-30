<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$logactions = $GLOBALS['pdo']->query('SELECT * FROM wh_log ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

function logdecode($log) {
    $operation = json_decode($log['operation'], true);

    $object = $operation['object']::getById($operation['id'], $GLOBALS['pdo']);
    switch ($operation['method']) {
        case 'putOnShelf':
            $shelf = StockShelf::getById($operation['parameters']['shelf']['id'], $GLOBALS['pdo']);
            return "{$object->name} ({$object->fnsku}) ürününden {$operation['parameters']['count']} adet {$shelf->name} rafına konuldu.";
        case 'removeFromShelf':
            $shelf = StockShelf::getById($operation['parameters']['shelf']['id'], $GLOBALS['pdo']);
            return "{$object->name} ({$object->fnsku})ürünü {$shelf->name} rafından alındı.";
        case 'moveBetweenShelves':
            $fromShelf = StockShelf::getById($operation['parameters']['fromShelf']['id'], $GLOBALS['pdo']);
            $toShelf = StockShelf::getById($operation['parameters']['toShelf']['id'], $GLOBALS['pdo']);
            return "{$object->name} ({$object->fnsku}) ürünü {$fromShelf->name} rafından {$toShelf->name} rafına taşındı.";
        case 'newShelf':
            return "{$object['name']} isimli yeni raf oluşturuldu.";
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
            <?= $log['user'] ?> tarafından
            <?= logdecode($log) ?>
        </li>
    <?php endforeach; ?>

    <?= wh_menu() ?>
</div>

<?php

include '_footer.php';