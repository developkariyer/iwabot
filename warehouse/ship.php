<?php


require_once 'warehouse.php';

if (!userCan(['manage'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'ship') {
    $container_list = array_filter(array_map(function ($line) {
        $line = array_map('trim', explode("\t", $line));
        if (count($line) !== 4) {
            return null;
        }
        return [
            'container_name' => $line[0],
            'content' => $line[1],
            'fnsku' => $line[2],
            'count' => $line[3],
        ];
    }, explode("\n", $_POST['container_list'])));

    $msg = "Starting to process " . count($container_list) . " containers.\n";
    $containers = [];
    foreach ($container_list as $container) {
        if (!isset($containers[$container['container_name']])) {
            $containers[$container['container_name']] = [];
        }
        $containers[$container['container_name']][] = [
            'content' => $container['content'],
            'fnsku' => $container['fnsku'],
            'count' => $container['count'],
        ];
    }

    foreach ($containers as $cname=>$contents) {
        $msg .= 'Processing '. $cname .' with '. implode(',', array_column($contents, 'fnsku'))."\n";
        $containerObject = WarehouseContainer::getByField('name', $cname);
        if ($containerObject) {
            $msg .= "    Container {$cname} already exists, skipping.\n";
            continue;
        }
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO warehouse_containers (name, type, parent_id) VALUES (?, 'Gemi', 7226)");

        $stmt->execute([$cname]);
        $container_id = $GLOBALS['pdo']->lastInsertId();

        $msg .= "    Container {$cname} created with id $container_id.\n";
        foreach ($contents as $content) {
            $product = WarehouseProduct::getByField('fnsku', $content['fnsku']);
            if (!$product) {
                $msg .= "    Product {$content['fnsku']} not found, creating.\n";
                $stmt = $GLOBALS['pdo']->prepare("INSERT INTO warehouse_products (name, category, fnsku) VALUES (?, ?, ?)");
                $stmt->execute([
                    $content['content'],
                    match(substr($cname, 0 ,1)) {
                        '0' => 'AHŞAP-IWA',
                        '1' => 'METAL-IWA',
                        '2' => 'CAM-IWA',
                        '3' => 'HARİTA',
                        '4' => 'MOBİLYA',
                        '5' => 'Paket',
                        default => 'Diğer'
                    },
                    $content['fnsku']
                ]);
                $product_id = $GLOBALS['pdo']->lastInsertId();                
            } else {
                $product_id = $product->id;
            }
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO warehouse_container_contents (container_id, product_id, count) VALUES (?, ?, ?)");
            for ($t=0;$t<$content['count'];$t++) {
                $stmt->execute([$container_id, $product_id, 1]);
            }
            $msg .= "    Product {$content['fnsku']} x {$content['count']} added to container {$cname}.\n";
        }
    }
    $msg .= "All containers processed.";
}

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Ürün İşlemleri</h1>
        <p>Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>

    <?php if (isset($msg)) { ?>
        <div class="alert alert-info">
            <pre><?= $msg ?></pre>
        </div>
    <?php } ?>

    <div>
        <form action="ship.php" method="post">
            <input type="hidden" name="action" value="ship">
            <div class="form-group">
                <label for="container_list">Koli Listesi</label>
                <textarea class="form-control" name="container_list" id="container_list" rows="10" placeholder="Koli listesini TAB aralıklı giriniz."><?= $_POST['container_list'] ?? '' ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Gönder</button>
        </form>
    </div>

    <hr>

    <?= wh_menu() ?>
</div>


<?php

include '../_footer.php';
